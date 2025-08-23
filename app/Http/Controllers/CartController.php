<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class CartController extends Controller
{
    // === Helpers ===
    protected function getCart(): array
    {
        return session()->get('cart.items', []);
    }

    protected function putCart(array $items): void
    {
        session()->put('cart.items', $items);
    }

    protected function getSetting(string $key, $default = null)
    {
        $row = DB::table('settings')->where('key', $key)->value('value'); // settings table
        return is_null($row) ? $default : (is_numeric($row) ? 0 + $row : ($row === 'null' ? null : $row));
    }

    protected function computeDiscount(?array $coupon, array $items): array
    {
        if (!$coupon || empty($items)) {
            return ['eligible_subtotal' => 0, 'discount' => 0];
        }

        $appliedTo   = $coupon['applied_to'];    // order|category|brand|product
        $ids         = $coupon['applies_to_ids'] ?: [];
        $eligibleSum = 0;

        foreach ($items as $it) {
            $ok = false;
            switch ($appliedTo) {
                case 'category':
                    $ok = in_array($it['category_id'], $ids);
                    break;
                case 'brand':
                    $ok = in_array($it['brand_id'], $ids);
                    break;
                case 'product':
                    $ok = in_array($it['product_id'], $ids);
                    break;
                default:
                    $ok = true;
                    break; // 'order'
            }
            if ($ok) $eligibleSum += $it['unit_price'] * $it['qty'];
        }

        $discount = 0;
        if ($eligibleSum > 0) {
            if ($coupon['discount_type'] === 'percent') {
                $discount = $eligibleSum * ($coupon['discount_value'] / 100);
            } else { // fixed
                $discount = min($coupon['discount_value'], $eligibleSum);
            }
            if (!is_null($coupon['max_discount'])) {
                $discount = min($discount, $coupon['max_discount']);
            }
        }

        return ['eligible_subtotal' => $eligibleSum, 'discount' => round($discount, 2)];
    }

    protected function snapshot(array $items): array
    {
        $subtotal = 0;
        $weight   = 0;
        foreach ($items as $it) {
            $line = $it['unit_price'] * $it['qty'];
            $subtotal += $line;
            $weight   += ((int)($it['weight_grams'] ?? 0)) * $it['qty'];
        }

        // coupon (recompute each time from code stored in session)
        $couponCode = session('cart.coupon_code');
        $couponRow  = null;
        if ($couponCode) {
            $couponRow = DB::table('coupons')->where('code', $couponCode)->first();
        }
        $coupon = $couponRow ? [
            'id' => $couponRow->id,
            'code' => $couponRow->code,
            'discount_type' => $couponRow->discount_type,
            'discount_value' => (float)$couponRow->discount_value,
            'max_discount' => $couponRow->max_discount !== null ? (float)$couponRow->max_discount : null,
            'applied_to' => $couponRow->applied_to,
            'applies_to_ids' => $couponRow->applies_to_ids ? json_decode($couponRow->applies_to_ids, true) : [],
            'min_order_total' => (float)$couponRow->min_order_total,
        ] : null;

        $discountInfo = $this->computeDiscount($coupon, $items);
        $discount     = $discountInfo['discount'];

        // shipping (simple): free if reach threshold; otherwise base fee
        $freeship = (float)$this->getSetting('shipping.freeship_threshold', 0);
        $baseShip = 30000; // bạn có thể thay bằng bảng shipping_rates sau
        $shipping = ($subtotal - $discount) >= $freeship ? 0 : $baseShip;

        $tax = 0; // VN retail thường đã gồm VAT trong giá
        $grand = max(0, $subtotal - $discount + $shipping + $tax);

        return [
            'items'       => array_values($items),
            'counts'      => array_sum(array_column($items, 'qty')),
            'subtotal'    => round($subtotal, 2),
            'discount'    => $discount,
            'shipping'    => $shipping,
            'tax'         => $tax,
            'grand_total' => round($grand, 2),
            'weight_grams' => (int)$weight,
            'coupon'      => $coupon ? Arr::only($coupon, ['id', 'code']) : null,
        ];
    }

    // === Routes ===

    // GET /cart -> JSON snapshot (dùng cho Drawer)
    public function index(Request $request)
    {
        return response()->json($this->snapshot($this->getCart()));
    }

    // POST /cart -> thêm vào giỏ
    public function store(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'qty'        => ['nullable', 'integer', 'min:1'],
            'replace'    => ['nullable', 'boolean'],
        ]);

        $qty = max(1, (int)($data['qty'] ?? 1));

        $variant = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('v.id', $data['variant_id'])
            ->where('v.is_active', 1)
            ->selectRaw('v.id as variant_id, v.name as variant_name, v.price, v.weight_grams, p.id as product_id, p.name as product_name, p.brand_id, p.category_id')
            ->first();

        if (!$variant) {
            return response()->json(['message' => 'Variant không tồn tại hoặc đã ngừng bán.'], 404);
        }

        $items = $this->getCart();
        $key = (string)$variant->variant_id;

        if (!isset($items[$key]) || ($data['replace'] ?? false)) {
            $items[$key] = [
                'key'          => $key,
                'product_id'   => (int)$variant->product_id,
                'variant_id'   => (int)$variant->variant_id,
                'product_name' => $variant->product_name,
                'variant_name' => $variant->variant_name,
                'unit_price'   => (float)$variant->price,
                'qty'          => $qty,
                'brand_id'     => $variant->brand_id ? (int)$variant->brand_id : null,
                'category_id'  => $variant->category_id ? (int)$variant->category_id : null,
                'weight_grams' => $variant->weight_grams ? (int)$variant->weight_grams : 0,
            ];
        } else {
            $items[$key]['qty'] += $qty;
        }

        $this->putCart($items);
        return response()->json($this->snapshot($items));
    }

    // PATCH /cart/{key} -> cập nhật qty
    public function update(Request $request, $key)
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
        ]);
        $items = $this->getCart();
        if (!isset($items[$key])) {
            return response()->json(['message' => 'Dòng hàng không tồn tại.'], 404);
        }
        $items[$key]['qty'] = (int)$data['qty'];
        $this->putCart($items);
        return response()->json($this->snapshot($items));
    }

    // DELETE /cart/{key} -> xóa dòng
    public function destroy($key)
    {
        $items = $this->getCart();
        unset($items[$key]);
        $this->putCart($items);
        return response()->json($this->snapshot($items));
    }

    // DELETE /cart -> xóa toàn bộ
    public function clear()
    {
        $this->putCart([]);
        session()->forget('cart.coupon_code');
        return response()->json($this->snapshot([]));
    }
}
