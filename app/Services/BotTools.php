<?php
// app/Services/BotTools.php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BotTools
{
    /* ================= Router ================= */
    public static function call(string $name, array $args): array
    {
        return match ($name) {
            'topByPrice'        => ['result' => self::topByPrice(...self::args($args, [
                'direction' => 'desc',
                'category_slug' => null,
                'brand_slug' => null,
                'limit' => 6
            ]))],
            'searchProducts'    => ['result' => self::searchProducts(...self::args($args, [

                'query' => null,
                'skin_types' => [],
                'concerns' => [],
                'price_min' => null,
                'price_max' => null,
                'category_slug' => null,
                'brand_slug' => null,
                'limit' => 8
            ]))],
            'pickProducts'      => ['result' => self::pickProducts(...self::args($args, ['limit' => 8]))],
            'resolveProduct'    => ['result' => self::resolveProduct(...self::args($args, ['query' => '']))],
            'getProductInfo'    => ['result' => self::getProductInfo(...self::args($args, ['slugOrId' => '']))],
            'checkAvailability' => ['result' => self::checkAvailability(...self::args($args, ['slugOrId' => '']))],
            'compareProducts'   => ['result' => self::compareProducts(...self::args($args, ['idsOrSlugs' => []]))],
            'getOrderStatus'    => ['result' => self::getOrderStatus(...self::args($args, ['code' => '']))],
            'validateCoupon'    => ['result' => self::validateCoupon(...self::args($args, ['code' => '', 'cart' => [], 'subtotal' => 0]))],
            'getPolicy'         => ['result' => self::getPolicy(...self::args($args, ['topic' => 'shipping']))],
            default             => ['error' => 'Unknown tool: ' . $name]
        };
    }
    private static function args(array $in, array $defaults): array
    {
        return array_values(array_merge($defaults, $in));
    }

    /* ================= ẢNH: lấy & chuẩn hoá URL ================= */
    private static function firstImageFromProduct($p): ?string
    {
        $candidates = [];
        foreach (['image', 'thumbnail', 'images', 'gallery', 'media', 'pictures'] as $field) {
            if (isset($p->{$field}) && $p->{$field}) $candidates[] = $p->{$field};
        }
        foreach ($candidates as $val) {
            if (is_string($val) && strlen($val) > 2 && ($val[0] === '[' || $val[0] === '{')) {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    $first = is_array(reset($decoded)) ? ($decoded[0]['url'] ?? $decoded[0]['path'] ?? null) : ($decoded[0] ?? null);
                    if ($first) return self::normalizeImageUrl($first);
                }
            }
            if (is_array($val)) {
                $first = $val[0] ?? null;
                if (is_array($first)) $first = ($first['url'] ?? $first['path'] ?? null);
                if ($first) return self::normalizeImageUrl($first);
            }
            if (is_string($val)) return self::normalizeImageUrl($val);
        }
        return null;
    }
    private static function normalizeImageUrl(?string $path): string
    {
        if (!$path) return asset('images/placeholder.png');
        $path = trim($path);
        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) return $path;
        if (Str::startsWith($path, ['/', 'storage/', 'uploads/', 'images/'])) return url(ltrim($path, '/'));
        try {
            return url(ltrim(Storage::url($path), '/'));
        } catch (\Throwable $e) {
            return asset(ltrim($path, '/'));
        }
    }

    /* ================= Search theo từ khoá/giá ================= */
    public static function searchProducts(
        ?string $query = null,
        array $skin_types = [],
        array $concerns = [],
        $price_min = null,
        $price_max = null,
        ?string $category_slug = null,
        ?string $brand_slug = null,
        int $limit = 8
    ): array {
        // chặn query quá ngắn/1 ký tự
        if ($query && mb_strlen(Str::slug($query)) < 2) $query = null;

        $q = Product::query()
            ->with(['variants' => fn($v) => $v->where('is_active', 1)])
            ->where('is_active', 1);

        if ($query) {
            $q->where(function ($w) use ($query) {
                $w->where('name', 'like', '%' . $query . '%')
                    ->orWhere('short_desc', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%');
            });
        }
        if ($category_slug) $q->whereHas('category', fn($c) => $c->where('slug', $category_slug));
        if ($brand_slug)    $q->whereHas('brand', fn($b) => $b->where('slug', $brand_slug));

        $q->withMin('variants', 'price');
        if ($price_min !== null) $q->having('variants_min_price', '>=', (float)$price_min);
        if ($price_max !== null) $q->having('variants_min_price', '<=', (float)$price_max);
        $q->orderBy('variants_min_price')->limit($limit);

        return self::mapCards($q->get());
    }
    /** Đoán nhanh category/brand từ câu hỏi (nếu có bảng) */
    public static function guessFilters(string $text): array
    {
        $cat = null;
        $brand = null;
        $needle = Str::slug($text, ' ');

        if (Schema::hasTable('categories')) {
            $cats = DB::table('categories')->select('slug', 'name')->get();
            foreach ($cats as $c) {
                $nameSlug = Str::slug($c->name, ' ');
                if (Str::contains($needle, $nameSlug) || Str::contains($nameSlug, $needle)) {
                    $cat = $c->slug;
                    break;
                }
            }
        }
        if (Schema::hasTable('brands')) {
            $brands = DB::table('brands')->select('slug', 'name')->get();
            foreach ($brands as $b) {
                $nameSlug = Str::slug($b->name, ' ');
                if (Str::contains($needle, $nameSlug) || Str::contains($nameSlug, $needle)) {
                    $brand = $b->slug;
                    break;
                }
            }
        }
        return [$cat, $brand];
    }

    /** Lấy danh sách sản phẩm rẻ/đắt nhất (theo min price của variants) */
    public static function topByPrice(string $direction = 'desc', ?string $category_slug = null, ?string $brand_slug = null, int $limit = 6): array
    {
        $dir = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $q = Product::query()
            ->where('is_active', 1)
            ->with(['variants' => fn($v) => $v->where('is_active', 1)]);

        if ($category_slug) $q->whereHas('category', fn($c) => $c->where('slug', $category_slug));
        if ($brand_slug)    $q->whereHas('brand', fn($b) => $b->where('slug', $brand_slug));

        $q->withMin('variants', 'price'); // -> variants_min_price
        $q->orderBy('variants_min_price', $dir)->limit($limit);

        return self::mapCards($q->get());
    }


    /* ================= Gợi ý “bất kỳ” sản phẩm ================= */
    public static function pickProducts(int $limit = 8): array
    {
        $pCols = Schema::getColumnListing('products');
        $vCols = Schema::getColumnListing('product_variants');

        $q = Product::query()->where('is_active', 1)
            ->with(['variants' => fn($v) => $v->where('is_active', 1)]);

        $qtyCol = null;
        foreach (['stock', 'quantity', 'qty', 'inventory', 'inventory_qty'] as $c) if (in_array($c, $vCols)) {
            $qtyCol = $c;
            break;
        }
        if ($qtyCol) $q->whereHas('variants', fn($v) => $v->where($qtyCol, '>', 0));

        if (in_array('is_featured', $pCols)) $q->orderByDesc('is_featured');
        elseif (in_array('featured', $pCols)) $q->orderByDesc('featured');
        if (in_array('sold_count', $pCols)) $q->orderByDesc('sold_count');
        elseif (in_array('sold', $pCols))   $q->orderByDesc('sold');
        elseif (in_array('views', $pCols))  $q->orderByDesc('views');
        if (in_array('created_at', $pCols)) $q->orderByDesc('created_at');
        else $q->orderByDesc('id');

        $q->withMin('variants', 'price')->orderBy('variants_min_price');
        $items = $q->limit($limit)->get();
        if ($items->isEmpty()) $items = Product::query()->where('is_active', 1)->inRandomOrder()->limit($limit)->get();

        return self::mapCards($items);
    }

    /* ================= Map -> card UI ================= */
    private static function mapCards($items): array
    {
        return $items->map(function ($p) {
            $minVariant = optional($p->variants)->sortBy('price')->first();
            $price   = $minVariant->price ?? 0;
            $compare = $minVariant->compare_at_price ?? null;
            $discount = ($compare && $compare > 0 && $price > 0) ? round((1 - $price / $compare) * 100) : null;

            $url = app('router')->has('product.show') ? route('product.show', $p->slug) : url('/products/' . $p->slug);
            $img = self::firstImageFromProduct($p) ?: asset('images/placeholder.png');

            return [
                'url' => $url,
                'img' => $img,
                'name' => $p->name,
                'price' => number_format($price) . '₫',
                'compare' => $compare ? number_format($compare) . '₫' : null,
                'discount' => $discount
            ];
        })->values()->all();
    }

    /* ================= Stopwords + Resolver an toàn ================= */
    private static function vnStopwords(): array
    {
        return [
            'a',
            'à',
            'á',
            'ạ',
            'ơi',
            'này',
            'kia',
            'vậy',
            'thế',
            'là',
            'làm',
            'cái',
            'con',
            'của',
            'về',
            'với',
            'cho',
            'tôi',
            'mình',
            'bạn',
            'muốn',
            'hỏi',
            'xem',
            'coi',
            'nữa',
            'không',
            'ko',
            'k',
            'còn',
            'hết',
            'rồi',
            'đi',
            'nhanh',
            'giúp',
            'sp',
            'sản',
            'phẩm',
            'đó',
            'đấy',
            'nhé',
            'nhỉ',
            'thôi',
            'shop',
            'xin',
            'chào',
            'hi',
            'hello'
        ];
    }

    /** Resolver tên sản phẩm — BẢN AN TOÀN */
    public static function resolveProduct(string $query): array
    {
        $raw = trim($query);
        if ($raw === '') return ['found' => false];

        $clean = Str::of($raw)->lower()
            ->replaceMatches('/[^a-z0-9\s\-]+/u', ' ')
            ->squish()->toString();
        $tokens = array_values(array_filter(explode(' ', $clean)));

        $stop = array_flip(self::vnStopwords());
        $tokens = array_values(array_filter($tokens, function ($t) use ($stop) {
            if (isset($stop[$t])) return false;
            if (mb_strlen($t) < 2) return false;
            if (ctype_digit($t)) return false;
            return true;
        }));
        if (!$tokens) return ['found' => false];

        $strong = array_values(array_filter($tokens, fn($t) => mb_strlen($t) >= 4));

        $slug = Str::slug(implode(' ', $tokens));
        if (mb_strlen($slug) >= 6) {
            $bySlug = Product::query()->where('is_active', 1)
                ->where(fn($q) => $q->where('slug', $slug)->orWhere('slug', 'like', '%' . $slug . '%'))
                ->first(['id', 'slug', 'name']);
            if ($bySlug) return ['found' => true, 'id' => $bySlug->id, 'slug' => $bySlug->slug, 'name' => $bySlug->name, '_score' => 0.9];
        }

        $cands = Product::query()->where('is_active', 1)
            ->where(function ($w) use ($tokens) {
                foreach ($tokens as $t) $w->orWhere('name', 'like', '%' . $t . '%');
            })
            ->limit(20)->get(['id', 'slug', 'name']);

        $best = null;
        $bestScore = 0;
        $bestStrong = 0;
        foreach ($cands as $p) {
            $name = Str::lower($p->name);
            $match = 0;
            $strongMatch = 0;
            foreach ($tokens as $t) {
                if (Str::contains($name, $t)) {
                    $match++;
                    if (mb_strlen($t) >= 4) $strongMatch++;
                }
            }
            $coverage = $match / max(1, count($tokens));
            $strongCov = $strong ? ($strongMatch / count($strong)) : 0;
            $score = 0.7 * $coverage + 0.3 * $strongCov;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $p;
                $bestStrong = $strongMatch;
            }
        }

        $ok = ($bestScore >= 0.55 && $bestStrong >= 1) || (count($tokens) == 1 && mb_strlen($tokens[0]) >= 5 && $bestScore >= 0.7);
        return ($ok && $best) ? ['found' => true, 'id' => $best->id, 'slug' => $best->slug, 'name' => $best->name, '_score' => $bestScore] : ['found' => false];
    }

    /* ================= Product detail ================= */
    private static array $productCols = [];
    private static function productSelectCols(): array
    {
        if (!self::$productCols) {
            $cols = Schema::getColumnListing('products');
            $want = ['id', 'name', 'slug'];
            foreach (['short_desc', 'description', 'long_desc', 'thumbnail', 'image', 'images', 'gallery', 'skin_types', 'concerns'] as $c) {
                if (in_array($c, $cols)) $want[] = $c;
            }
            self::$productCols = $want;
        }
        return self::$productCols;
    }

    public static function getProductInfo(string $slugOrId): array
    {
        $p = Product::query()
            ->when(is_numeric($slugOrId), fn($q) => $q->where('id', (int)$slugOrId), fn($q) => $q->where('slug', $slugOrId))
            ->with(['variants' => fn($v) => $v->where('is_active', 1)->select('id', 'product_id', 'price', 'compare_at_price')])
            ->first(self::productSelectCols());
        if (!$p) return ['found' => false, 'message' => 'Không thấy sản phẩm'];

        $min = optional($p->variants)->min('price');
        $img = self::firstImageFromProduct($p) ?: asset('images/placeholder.png');
        $long = (string)($p->long_desc ?? $p->description ?? '');

        return [
            'found' => true,
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'price_min' => (float)$min,
            'img' => $img,
            'short_desc' => (string)($p->short_desc ?? ''),
            'long_desc' => mb_strimwidth(strip_tags($long), 0, 1200, '…', 'UTF-8'),
            'skin_types' => $p->skin_types ?? null,
            'concerns' => $p->concerns ?? null,
        ];
    }

    /* ================= Stock ================= */
    public static function checkAvailability(string $slugOrId): array
    {
        $vCols = Schema::getColumnListing('product_variants');
        $p = Product::query()
            ->when(is_numeric($slugOrId), fn($q) => $q->where('id', (int)$slugOrId), fn($q) => $q->where('slug', $slugOrId))
            ->with(['variants' => fn($v) => $v->select('*')])
            ->first(self::productSelectCols());
        if (!$p) return ['found' => false, 'message' => 'Không thấy sản phẩm'];

        $qtyCol = null;
        foreach (['stock', 'quantity', 'qty', 'inventory', 'inventory_qty'] as $c) if (in_array($c, $vCols)) {
            $qtyCol = $c;
            break;
        }
        $flagCol = null;
        foreach (['in_stock', 'is_in_stock'] as $c) if (in_array($c, $vCols)) {
            $flagCol = $c;
            break;
        }

        $inStock = null;
        if ($flagCol) $inStock = $p->variants->contains(fn($v) => (bool)$v->{$flagCol});
        if ($inStock === null && $qtyCol) $inStock = ($p->variants->sum($qtyCol) > 0);
        if ($inStock === null && in_array('is_active', $vCols)) $inStock = $p->variants->contains(fn($v) => (int)$v->is_active === 1);

        $status = $inStock === true ? 'in_stock' : ($inStock === false ? 'out_of_stock' : 'unknown');
        return ['found' => true, 'slug' => $p->slug, 'name' => $p->name, 'status' => $status, 'in_stock' => $inStock];
    }

    /* ================= Order/Coupon/Policy/Compare ================= */
    public static function getOrderStatus(string $code): array
    {
        $code = strtoupper(ltrim($code, '#'));
        $o = Order::where('code', $code)->first();
        if (!$o) return ['found' => false, 'message' => 'Không tìm thấy đơn ' . $code];
        return [
            'found' => true,
            'code' => $o->code,
            'status' => $o->status,
            'payment_status' => $o->payment_status,
            'payment_method' => $o->payment_method,
            'grand_total' => (float)($o->grand_total ?? 0),
            'placed_at' => optional($o->placed_at)->toDateTimeString(),
        ];
    }

    public static function validateCoupon(string $code, array $cart = [], float $subtotal = 0): array
    {
        $c = DB::table('coupons')->where('code', strtoupper($code))->first();
        if (!$c) return ['valid' => false, 'reason' => 'Mã không tồn tại'];
        $now = now();
        if (property_exists($c, 'starts_at') && $c->starts_at && $now->lt($c->starts_at)) return ['valid' => false, 'reason' => 'Mã chưa đến thời gian áp dụng'];
        if (property_exists($c, 'ends_at')   && $c->ends_at && $now->gt($c->ends_at))   return ['valid' => false, 'reason' => 'Mã đã hết hạn'];
        if (property_exists($c, 'is_active') && !$c->is_active)                         return ['valid' => false, 'reason' => 'Mã đang tạm khoá'];

        $min = property_exists($c, 'min_order_total') ? (float)$c->min_order_total : 0;
        if ($min > 0 && $subtotal < $min) return ['valid' => false, 'reason' => 'Chưa đạt giá trị tối thiểu'];

        $discount = 0.0;
        if ($subtotal > 0) {
            if (($c->discount_type ?? '') === 'percent') $discount = round($subtotal * ((float)$c->discount_value) / 100, 2);
            else $discount = min($subtotal, (float)($c->discount_value ?? 0));
        }
        return [
            'valid' => true,
            'code' => $c->code,
            'discount' => $discount,
            'summary' => (($c->discount_type ?? '') === 'percent' ? (($c->discount_value ?? 0) . '%') : (number_format(($c->discount_value ?? 0)) . '₫')) . ' áp dụng thành công'
        ];
    }

    public static function getPolicy(string $topic): array
    {
        $row = DB::table('settings')->where('key', 'policy.' . Str::slug($topic))->first();
        return $row ? ['topic' => $topic, 'content' => (string)$row->value] : ['topic' => $topic, 'content' => "Thông tin đang cập nhật"];
    }

    public static function compareProducts(array $idsOrSlugs): array
    {
        $norm = array_values(array_unique(array_filter($idsOrSlugs)));
        if (!$norm) return ['items' => []];

        $items = Product::query()
            ->where(function ($qq) use ($norm) {
                foreach ($norm as $x) {
                    is_numeric($x) ? $qq->orWhere('id', (int)$x) : $qq->orWhere('slug', $x);
                }
            })
            ->with(['variants' => fn($v) => $v->where('is_active', 1)->select('id', 'product_id', 'price', 'compare_at_price')])
            ->take(3)->get(self::productSelectCols());

        return [
            'items' => $items->map(function ($p) {
                $min = optional($p->variants)->min('price');
                $long = (string)($p->long_desc ?? $p->description ?? '');
                return [
                    'id' => $p->id,
                    'slug' => $p->slug,
                    'name' => $p->name,
                    'price_min' => (float)$min,
                    'skin_types' => $p->skin_types ?? null,
                    'concerns' => $p->concerns ?? null,
                    'short_desc' => (string)($p->short_desc ?? ''),
                    'long_desc' => mb_strimwidth(strip_tags($long), 0, 800, '…', 'UTF-8'),
                ];
            })->values()->all()
        ];
    }
}
