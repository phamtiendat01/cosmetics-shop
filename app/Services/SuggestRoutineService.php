<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gợi ý routine từ metrics + TÌM SẢN PHẨM TRONG KHO
 * - Ưu tiên SKU có ảnh, giá gần ngân sách step
 * - Lọc theo skin_types, concerns (JSON) nếu có
 * - Fallback: card "guideline" khi thiếu hàng
 */
class SuggestRoutineService
{
    public function fromMetrics(array $m, ?int $budget = null): array
    {
        $o = (float)($m['oiliness']   ?? 0);
        $d = (float)($m['dryness']    ?? 0);
        $r = (float)($m['redness']    ?? 0);
        $a = (float)($m['acne_score'] ?? 0);

        // 1) Suy luận skin type
        $type = 'combination';
        if ($r > 0.6 && $o < 0.6 && $d < 0.6)        $type = 'sensitive';
        elseif (($o - $d) > 0.15)                    $type = 'oily';
        elseif (($d - $o) > 0.15)                    $type = 'dry';

        // 2) Chia ngân sách theo step
        $budget = max(0, (int)$budget);
        $bucket = $budget > 0 ? [
            'Cleanser'    => (int)round($budget * 0.20),
            'Treatment'   => (int)round($budget * 0.35),
            'Moisturizer' => (int)round($budget * 0.25),
            'Sunscreen'   => (int)round($budget * 0.20),
        ] : null;

        // 3) Rules diễn giải + hint (khi thiếu hàng)
        $rules = [
            'oily' => [
                ['step' => 'Cleanser',    'reason' => 'Làm sạch bã nhờn',                'hint' => 'Gel/salicylic (BHA)'],
                ['step' => 'Treatment',   'reason' => 'Giảm mụn & bóng dầu',             'hint' => 'BHA/Niacinamide'],
                ['step' => 'Moisturizer', 'reason' => 'Giữ ẩm nhẹ, không bí',            'hint' => 'Gel-cream, oil-free'],
                ['step' => 'Sunscreen',   'reason' => 'Chống nắng khô thoáng',           'hint' => 'Gel/Hybrid, không bóng'],
            ],
            'dry' => [
                ['step' => 'Cleanser',    'reason' => 'Làm sạch dịu nhẹ',                 'hint' => 'Sữa rửa mặt pH ~5.5'],
                ['step' => 'Treatment',   'reason' => 'Phục hồi hàng rào',                'hint' => 'Ceramide/HA/Panthenol'],
                ['step' => 'Moisturizer', 'reason' => 'Khoá ẩm sâu',                      'hint' => 'Cream, shea/butter'],
                ['step' => 'Sunscreen',   'reason' => 'SPF dưỡng ẩm',                     'hint' => 'Cream sunscreen'],
            ],
            'sensitive' => [
                ['step' => 'Cleanser',    'reason' => 'Giảm kích ứng khi rửa',           'hint' => 'Không hương liệu, SLS-free'],
                ['step' => 'Treatment',   'reason' => 'Làm dịu đỏ',                       'hint' => 'Centella/Allantoin'],
                ['step' => 'Moisturizer', 'reason' => 'Bảo vệ hàng rào',                  'hint' => 'Ceramide, cholesterol'],
                ['step' => 'Sunscreen',   'reason' => 'Không gây kích ứng',               'hint' => 'Mineral/Non-nano'],
            ],
            'combination' => [
                ['step' => 'Cleanser',    'reason' => 'Làm sạch cân bằng',                'hint' => 'Gel dịu nhẹ'],
                ['step' => 'Treatment',   'reason' => 'Kiểm dầu T-zone, cấp ẩm U-zone',   'hint' => 'BHA + HA'],
                ['step' => 'Moisturizer', 'reason' => 'Cân bằng ẩm',                      'hint' => 'Gel-cream'],
                ['step' => 'Sunscreen',   'reason' => 'Chống nắng nhẹ mặt',               'hint' => 'Gel/Hybrid'],
            ],
        ];

        // 4) Concerns ưu tiên từ metrics
        $concerns = $this->topConcerns($m); // ví dụ: ['acne','redness','oiliness','dryness']

        // 5) Build routine
        $routine = [];
        foreach ($rules[$type] as $r0) {
            $cap = $bucket[$r0['step']] ?? null;

            $products = $this->pickProductsForStep(
                step: $r0['step'],
                skinType: $type,
                concerns: $concerns,
                priceCap: $cap,
                limit: 3
            );

            if (empty($products)) {
                $products = [[
                    'slug'  => null,
                    'name'  => $r0['step'] . ' — ' . $r0['hint'],
                    'price' => $cap,
                    'url'   => '#',
                    'image' => 'https://placehold.co/600x600?text=Cosme',
                ]];
            }

            $routine[] = [
                'step'     => $r0['step'],
                'reason'   => $r0['reason'],
                'products' => $products,
            ];
        }

        return [
            'type'    => $type,
            'routine' => $routine,
        ];
    }

    /**
     * Chọn sản phẩm theo step → category slug map,
     * lọc skin_types/concerns (JSON), ưu tiên có ảnh & giá gần priceCap.
     */
    protected function pickProductsForStep(string $step, string $skinType, array $concerns, ?int $priceCap, int $limit = 3): array
    {
        // Map step -> category slug trong DB
        $catSlugMap = [
            'Cleanser'    => ['sua-rua-mat'],
            'Treatment'   => ['serum', 'treatment', 'essence'],
            'Moisturizer' => ['duong-am', 'kem-duong'],
            'Sunscreen'   => ['kem-chong-nang'],
        ];
        $catSlugs = $catSlugMap[$step] ?? [];

        $catIds = [];
        if ($catSlugs) {
            $catIds = DB::table('categories')->whereIn('slug', $catSlugs)->pluck('id')->all();
        }

        $q = DB::table('products as p')
            ->leftJoin('product_variants as v', function ($j) {
                $j->on('v.product_id', '=', 'p.id')->where('v.is_active', 1);
            })
            ->where('p.is_active', 1)
            ->when($catIds, fn($q) => $q->whereIn('p.category_id', $catIds))
            ->where(function ($q) {
                $q->whereNotNull('p.thumbnail')->orWhereNotNull('p.image');
            })
            ->selectRaw('p.id, p.slug, p.name, COALESCE(p.thumbnail, p.image) as image, MIN(v.price) as price_min, p.sold_count')
            ->groupBy('p.id', 'p.slug', 'p.name', 'p.thumbnail', 'p.image', 'p.sold_count');

        // JSON skin_types chứa $skinType
        if ($skinType) {
            $q->whereRaw("JSON_CONTAINS(COALESCE(p.skin_types, JSON_ARRAY()), JSON_QUOTE(?))", [$skinType]);
        }

        // JSON concerns chứa 1–2 concern top
        $topConcerns = array_slice($concerns, 0, 2);
        if ($topConcerns) {
            $q->where(function ($qq) use ($topConcerns) {
                foreach ($topConcerns as $c) {
                    $qq->orWhereRaw("JSON_CONTAINS(COALESCE(p.concerns, JSON_ARRAY()), JSON_QUOTE(?))", [$c]);
                }
            });
        }

        if ($priceCap) {
            $q->orderByRaw('ABS(COALESCE(MIN(v.price), 999999) - ?) asc', [$priceCap]);
        }
        $q->orderByDesc('p.sold_count');

        $rows = $q->limit($limit)->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'slug'  => $r->slug,
                'name'  => $r->name,
                'price' => $r->price_min ? (int)round($r->price_min) : null,
                'url'   => url('/product/' . $r->slug),
                'image' => $this->toImageUrl($r->image),
            ];
        }

        // Fallback nới lỏng (bỏ lọc concerns/type) nếu chưa đủ số lượng
        if (count($out) < $limit && $catIds) {
            $need = $limit - count($out);

            $q2 = DB::table('products as p')
                ->leftJoin('product_variants as v', function ($j) {
                    $j->on('v.product_id', '=', 'p.id')->where('v.is_active', 1);
                })
                ->where('p.is_active', 1)
                ->whereIn('p.category_id', $catIds)
                ->where(function ($q) {
                    $q->whereNotNull('p.thumbnail')->orWhereNotNull('p.image');
                })
                ->selectRaw('p.id, p.slug, p.name, COALESCE(p.thumbnail, p.image) as image, MIN(v.price) as price_min, p.sold_count')
                ->groupBy('p.id', 'p.slug', 'p.name', 'p.thumbnail', 'p.image', 'p.sold_count')
                ->when($priceCap, fn($qq) => $qq->orderByRaw('ABS(COALESCE(MIN(v.price), 999999) - ?) asc', [$priceCap]))
                ->orderByDesc('p.sold_count')
                ->limit($need);

            foreach ($q2->get() as $r) {
                $out[] = [
                    'slug'  => $r->slug,
                    'name'  => $r->name,
                    'price' => $r->price_min ? (int)round($r->price_min) : null,
                    'url'   => url('/product/' . $r->slug),
                    'image' => $this->toImageUrl($r->image),
                ];
            }
        }

        return $out;
    }

    /** Xếp hạng concerns từ metrics */
    protected function topConcerns(array $m): array
    {
        $pairs = [
            'oiliness' => (float)($m['oiliness'] ?? 0),
            'dryness'  => (float)($m['dryness']  ?? 0),
            'redness'  => (float)($m['redness']  ?? 0),
            'acne'     => (float)($m['acne_score'] ?? 0),
        ];
        arsort($pairs);
        return array_keys($pairs);
    }

    /** Chuẩn hoá đường dẫn ảnh → URL tuyệt đối, an toàn */
    protected function toImageUrl(?string $path): string
    {
        if (!$path) return 'https://placehold.co/600x600?text=Cosme';
        $path = trim($path);

        // đã là URL tuyệt đối
        if (Str::startsWith($path, ['http://', 'https://', '//'])) return $path;

        // /storage/... → thêm domain hiện tại
        if (Str::startsWith($path, ['storage/', '/storage/'])) {
            return url(Str::startsWith($path, '/') ? $path : '/' . $path);
        }

        // coi như file trên disk public
        try {
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return url('/storage/' . $path);
        }
    }
}
