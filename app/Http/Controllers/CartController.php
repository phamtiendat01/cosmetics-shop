<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;

class CartController extends Controller
{
    /**
     * Session structure:
     * cart.items = [
     *   "p{product_id}-v{variant_id|0}" => ['product_id'=>1,'variant_id'=>10|null,'qty'=>2]
     * ]
     */
    protected function key(int $pid, ?int $vid): string
    {
        return 'p' . $pid . '-v' . (int)($vid ?? 0);
    }

    protected function getItems(): array
    {
        return (array) session('cart.items', []);
    }

    protected function putItems(array $items): void
    {
        session(['cart.items' => $items]);
    }

    protected function countItems(?array $items = null): int
    {
        $items = $items ?? $this->getItems();
        return collect($items)->sum('qty');
    }

    /* ============================ API ============================ */

    /** GET /cart/json */
    public function index()
    {
        $raw = $this->getItems();
        if (empty($raw)) {
            return response()->json([
                'ok'       => true,
                'count'    => 0,
                'subtotal' => 0,
                'items'    => [],
            ]);
        }

        $pids = collect($raw)->pluck('product_id')->unique()->values()->all();

        $products = Product::query()
            ->whereIn('id', $pids)
            ->with(['variants' => function ($q) {
                $q->select('id', 'product_id', 'name', 'price', 'compare_at_price');
            }])
            ->get()
            ->keyBy('id');

        $items    = [];
        $subtotal = 0;

        foreach ($raw as $k => $it) {
            /** @var Product|null $p */
            $p = $products->get((int)$it['product_id']);
            if (!$p) continue;

            $vid     = $it['variant_id'] ?? null;
            $variant = $vid ? $p->variants->firstWhere('id', (int)$vid) : null;

            // Giá ưu tiên variant -> fallback min variant -> fallback product->price
            if ($variant) {
                $price   = (int) $variant->price;
                $compare = $variant->compare_at_price ? (int) $variant->compare_at_price : null;
            } else {
                $price   = (int) (optional($p->variants)->min('price') ?? ($p->price ?? 0));
                $compare = optional($p->variants)->min('compare_at_price');
                $compare = $compare ? (int)$compare : null;
            }

            $qty       = (int) $it['qty'];
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $imgPath = $this->pickImagePath($p, $variant);
            $imgUrl  = $this->fullImageUrl($imgPath);

            $items[] = [
                'key'         => $k,
                'product_id'  => (int) $p->id,
                'variant_id'  => (int) ($vid ?? 0),
                'name'        => $p->name,
                'variant'     => $variant?->name,
                'slug'        => $p->slug,
                'img'         => $imgUrl,
                'image'       => $imgUrl,
                'qty'         => $qty,
                'price'       => $price,
                'compare'     => $compare,
                'line_total'  => $lineTotal,
                'url'         => route('product.show', $p->slug),
                // hữu ích cho coupon lọc theo brand/category
                'brand_id'    => $p->brand_id ?? null,
                'category_id' => $p->category_id ?? null,
            ];
        }

        return response()->json([
            'ok'       => true,
            'count'    => $this->countItems($raw),
            'subtotal' => $subtotal,
            'items'    => $items,
        ]);
    }

    /** POST /cart  {product_id, variant_id?, qty?} */
    public function store(Request $req)
    {
        $data = $req->validate([
            'product_id' => ['required', 'integer', 'min:1', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'qty'        => ['nullable', 'integer', 'min:1'],
        ]);

        $pid = (int) $data['product_id'];
        $vid = $data['variant_id'] ?? null;
        $qty = (int) ($data['qty'] ?? 1);

        // verify variant thuộc product (nếu có)
        if ($vid) {
            $valid = ProductVariant::query()
                ->where('id', $vid)
                ->where('product_id', $pid)
                ->exists();
            if (!$valid) {
                return response()->json(['ok' => false, 'message' => 'Variant không hợp lệ'], 422);
            }
        }

        $key   = $this->key($pid, $vid);
        $items = $this->getItems();

        $items[$key] = [
            'product_id' => $pid,
            'variant_id' => $vid,
            'qty'        => ($items[$key]['qty'] ?? 0) + $qty,
        ];

        $this->putItems($items);

        return response()->json([
            'ok'    => true,
            'count' => $this->countItems($items),
            'key'   => $key,
            'item'  => $items[$key],
        ]);
    }
    public static function addToCart(string|int $product, ?int $variant_id = null, int $qty = 1): array
    {
        // Resolve product -> id
        $p = is_numeric($product)
            ? Product::find((int)$product)
            : Product::where('slug', (string)$product)->first();
        if (!$p) return ['ok' => false, 'message' => 'Không tìm thấy sản phẩm'];

        // Nếu product có biến thể mà chưa chọn biến thể -> lấy biến thể active rẻ nhất
        if (!$variant_id && $p->has_variants) {
            $variant_id = ProductVariant::where('product_id', $p->id)
                ->where('is_active', 1)->orderBy('price')->value('id');
        }

        // Gọi controller hiện có để thêm vào session cart
        $req = Request::create('/cart', 'POST', [
            'product_id' => $p->id,
            'variant_id' => $variant_id,
            'qty'        => max(1, (int)$qty),
        ]);
        // CartController@store sẽ trả JSON: ok, items, subtotal, …
        $resp = app(CartController::class)->store($req);

        return ['ok' => true, 'result' => $resp->getData(true)];
    }


    /** PATCH /cart/{key}  {qty} (qty=0 sẽ xoá) */
    public function update(Request $req, string $key)
    {
        $data = $req->validate([
            'qty' => ['required', 'integer', 'min:0'],
        ]);
        $qty   = (int) $data['qty'];
        $items = $this->getItems();

        if (!isset($items[$key])) {
            return response()->json(['ok' => false, 'message' => 'Item không tồn tại'], 404);
        }

        if ($qty <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['qty'] = $qty;
        }

        $this->putItems($items);

        return response()->json([
            'ok'    => true,
            'count' => $this->countItems($items),
        ]);
    }

    /** DELETE /cart/{key} */
    public function destroy(string $key)
    {
        $items = $this->getItems();
        unset($items[$key]);
        $this->putItems($items);

        return response()->json([
            'ok'    => true,
            'count' => $this->countItems($items),
        ]);
    }

    /** DELETE /cart */
    public function clear()
    {
        $this->putItems([]);
        // nếu bạn lưu coupon ở đây
        session()->forget('cart.coupon');

        return response()->json(['ok' => true, 'count' => 0]);
    }

    /* ============================ Helpers ============================ */

    /** Ưu tiên ảnh variant -> thumbnail -> image (có thể là string hoặc mảng) */
    protected function pickImagePath(Product $p, ?ProductVariant $v = null): ?string
    {
        $path = null;

        if ($v) {
            // tuỳ schema: image|string hoặc images|json
            $img = $v->image ?? null;
            if (is_array($img)) $img = $img[0] ?? null;
            $path = $img ?: null;
        }

        if (!$path) {
            $img = $p->thumbnail ?: $p->image;
            if (is_array($img)) $img = $img[0] ?? null;
            $path = $img ?: null;
        }

        return $path;
    }

    /** Chuẩn hoá về URL đầy đủ cho ảnh */
    protected function fullImageUrl(?string $path): string
    {
        if (!$path) {
            return 'https://placehold.co/80x80?text=IMG';
        }

        // 1) Ảnh tuyệt đối
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // 2) Đã là /storage/xxx
        if (str_starts_with($path, '/storage/')) {
            return url($path);
        }

        // 3) Đã là storage/xxx (không thêm 'storage/' lần nữa)
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        // 4) Đường dẫn thô -> thêm 'storage/'
        return asset('storage/' . ltrim($path, '/'));
    }
    public function count()
    {
        $count = 0;
        foreach ((array) session('cart.items', []) as $it) {
            $count += max(1, (int)($it['qty'] ?? 1));
        }
        return response()->json(['count' => $count]);
    }
}
