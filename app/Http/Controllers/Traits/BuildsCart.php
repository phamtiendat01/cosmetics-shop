<?php

namespace App\Http\Controllers\Traits;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Schema;

trait BuildsCart
{
    /**
     * Build giỏ hàng từ session.
     * @param array|null $onlyKeys
     * @return array{items: array<int,array>, subtotal:int, shipping_fee:int}
     */
    private function buildCart(array $onlyKeys = null): array
    {
        $raw = (array) session('cart.items', []);

        if ($onlyKeys && is_array($onlyKeys)) {
            $raw = array_intersect_key($raw, array_flip($onlyKeys));
        }

        if (empty($raw)) {
            return [
                'items'        => [],
                'subtotal'     => 0,
                'shipping_fee' => (int) session('cart.shipping_fee', 0),
            ];
        }

        $pids = collect($raw)->pluck('product_id')->unique()->values()->all();

        // ==== CHỌN CỘT VARIANT MỘT CÁCH AN TOÀN (không bắt buộc có 'image') ====
        $variantCols = ['id', 'product_id', 'name', 'price', 'compare_at_price'];
        foreach (['image', 'images', 'thumbnail', 'thumbnail_url', 'photo', 'photo_url'] as $col) {
            if (Schema::hasColumn('product_variants', $col)) {
                $variantCols[] = $col;
            }
        }

        $products = Product::query()
            ->whereIn('id', $pids)
            ->with(['variants' => function ($q) use ($variantCols) {
                $q->select($variantCols);
            }])
            ->get()
            ->keyBy('id');

        $items    = [];
        $subtotal = 0;

        foreach ($raw as $rowKey => $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($pid <= 0) continue;

            /** @var Product|null $p */
            $p = $products->get($pid);
            if (!$p) continue;

            $variantId = $it['variant_id'] ?? null;
            /** @var ProductVariant|null $variant */
            $variant = $variantId ? $p->variants->firstWhere('id', (int)$variantId) : null;

            if ($variant) {
                $price       = (int) $variant->price;
                $compare     = $variant->compare_at_price ? (int)$variant->compare_at_price : null;
                $variantName = $variant->name ?? null;
            } else {
                $price   = (int) (optional($p->variants)->min('price') ?? ($p->price ?? 0));
                $compare = optional($p->variants)->min('compare_at_price');
                $compare = $compare ? (int)$compare : null;
                $variantName = null;
            }

            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            // Ảnh: ưu tiên variant -> thumbnail -> image -> images
            $imgPath = $this->pickImagePath($p, $variant);
            $imgUrl  = $this->fullImageUrl($imgPath);

            $items[] = [
                'row_key'       => is_string($rowKey) ? $rowKey : null,
                'product_id'    => $pid,
                'variant_id'    => $variantId ? (int)$variantId : null,
                'name'          => (string) ($p->name ?? ''),
                'variant_name'  => $variantName,
                'slug'          => (string) ($p->slug ?? ''),
                'url'           => $p->slug ? route('product.show', $p->slug) : null,

                // 2 key ảnh để view nào cũng đọc được
                'image'         => $imgUrl,
                'img'           => $imgUrl,

                'qty'           => $qty,
                'price'         => $price,
                'compare'       => $compare,
                'line_total'    => $lineTotal,

                'brand_id'      => $p->brand_id ?? null,
                'category_id'   => $p->category_id ?? null,
            ];
        }

        return [
            'items'        => $items,
            'subtotal'     => $subtotal,
            'shipping_fee' => (int) session('cart.shipping_fee', 0),
        ];
    }

    /* ============================ Helpers ============================ */

    protected function pickImagePath(Product $p, ?ProductVariant $v = null): ?string
    {
        $candidates = [];

        if ($v) {
            // tuỳ DB bạn cột nào có thì cái đó sẽ có data
            $candidates[] = $v->image ?? null;
            $candidates[] = $v->images ?? null;
            $candidates[] = $v->thumbnail ?? null;
            $candidates[] = $v->thumbnail_url ?? null;
            $candidates[] = $v->photo ?? null;
            $candidates[] = $v->photo_url ?? null;
        }

        $candidates[] = $p->thumbnail ?? null;
        $candidates[] = $p->image ?? null;
        $candidates[] = $p->images ?? null;

        foreach ($candidates as $cand) {
            $first = $this->extractFirstPath($cand);
            if ($first) return $first;
        }
        return null;
    }

    protected function extractFirstPath($val): ?string
    {
        if (!$val) return null;
        if (is_array($val)) return $val[0] ?? null;

        if (is_string($val)) {
            $trim = trim($val);
            if ($trim === '') return null;

            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded[0] ?? null;
            }
            return $trim;
        }
        return null;
    }

    protected function fullImageUrl(?string $path): string
    {
        if (!$path) {
            return 'https://placehold.co/80x80?text=IMG';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/storage/')) {
            return url($path);
        }
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }
        return asset('storage/' . ltrim($path, '/'));
    }
}
