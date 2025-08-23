<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected function getSetting(string $key, $default = null)
    {
        $row = DB::table('settings')->where('key', $key)->value('value');
        return is_null($row) ? $default : (is_numeric($row) ? 0 + $row : ($row === 'null' ? null : $row));
    }

    protected function cart(): array
    {
        return session()->get('cart.items', []);
    }

    protected function computeCoupon(?string $code, array $items): float
    {
        if (!$code) return 0;
        $row = DB::table('coupons')->where('code', $code)->first();
        if (!$row) return 0;

        $coupon = [
            'discount_type'  => $row->discount_type,
            'discount_value' => (float)$row->discount_value,
            'max_discount'   => $row->max_discount !== null ? (float)$row->max_discount : null,
            'applied_to'     => $row->applied_to,
            'applies_to_ids' => $row->applies_to_ids ? json_decode($row->applies_to_ids, true) : [],
            'min_order_total' => (float)$row->min_order_total,
        ];

        $subtotal = array_reduce($items, fn($c, $i) => $c + $i['unit_price'] * $i['qty'], 0);
        if ($subtotal < $coupon['min_order_total']) return 0;

        $eligible = 0;
        foreach ($items as $it) {
            $ok = match ($coupon['applied_to']) {
                'category' => in_array($it['category_id'], $coupon['applies_to_ids']),
                'brand'    => in_array($it['brand_id'], $coupon['applies_to_ids']),
                'product'  => in_array($it['product_id'], $coupon['applies_to_ids']),
                default    => true,
            };
            if ($ok) $eligible += $it['unit_price'] * $it['qty'];
        }
        if ($eligible <= 0) return 0;

        $discount = $coupon['discount_type'] === 'percent'
            ? $eligible * ($coupon['discount_value'] / 100)
            : min($coupon['discount_value'], $eligible);

        if (!is_null($coupon['max_discount'])) $discount = min($discount, $coupon['max_discount']);
        return round($discount, 2);
    }

    protected function totals(array $items): array
    {
        $subtotal = array_reduce($items, fn($c, $i) => $c + $i['unit_price'] * $i['qty'], 0);
        $couponCode = session('cart.coupon_code');
        $discount   = $this->computeCoupon($couponCode, $items);

        $freeship = (float)$this->getSetting('shipping.freeship_threshold', 0);
        $baseShip = 30000;
        $shipping = ($subtotal - $discount) >= $freeship ? 0 : $baseShip;

        $tax = 0;
        $grand = max(0, $subtotal - $discount + $shipping + $tax);

        return compact('subtotal', 'discount', 'shipping', 'tax', 'grand');
    }

    // GET /checkout -> trang thanh toán
    public function index()
    {
        // Tuỳ view của bạn: 'checkout.index' hoặc 'checkout'
        return view('checkout.index');
    }

    // GET /checkout/preview -> JSON tổng tiền
    public function preview()
    {
        $items = $this->cart();
        if (empty($items)) {
            return response()->json(['message' => 'Giỏ hàng trống.'], 422);
        }
        return response()->json($this->totals($items));
    }

    // POST /checkout -> place order
    public function place(Request $request)
    {
        $items = $this->cart();
        if (empty($items)) {
            return response()->json(['message' => 'Giỏ hàng trống.'], 422);
        }

        // Guest checkout?
        $allowGuest = (int)$this->getSetting('checkout.allow_guest', 1) === 1;
        if (!Auth::check() && !$allowGuest) {
            return response()->json(['message' => 'Vui lòng đăng nhập để thanh toán.'], 401);
        }

        $data = $request->validate([
            'customer_name'   => 'required|string|max:255',
            'customer_phone'  => 'required|string|max:32',
            'customer_email'  => 'nullable|email',
            'shipping'        => 'required|array', // {province, district, ward, address}
            'notes'           => 'nullable|string',
            'payment_method'  => 'nullable|in:COD,VNPAY,MOMO,BANK',
        ]);

        $minOrder = (float)$this->getSetting('order.min_total', 0);
        $totals   = $this->totals($items);
        if ($totals['grand'] < $minOrder) {
            return response()->json(['message' => 'Chưa đạt giá trị đơn hàng tối thiểu.'], 422);
        }

        DB::beginTransaction();
        try {
            $code = 'DH' . Str::upper(Str::random(8)); // theo kiểu code trong bảng orders
            $orderId = DB::table('orders')->insertGetId([
                'user_id'        => Auth::id(),
                'code'           => $code,
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'] ?? 'COD',
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'shipping_address' => json_encode($data['shipping'], JSON_UNESCAPED_UNICODE),
                'shipping_method'  => 'GHTK', // placeholder
                'notes'            => $data['notes'] ?? null,
                'subtotal'       => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'shipping_fee'   => $totals['shipping'],
                'tax_total'      => $totals['tax'],
                'grand_total'    => $totals['grand'],
                'placed_at'      => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($items as $it) {
                DB::table('order_items')->insert([
                    'order_id'             => $orderId,
                    'product_id'           => $it['product_id'],
                    'product_variant_id'   => $it['variant_id'],
                    'product_name_snapshot' => $it['product_name'],
                    'variant_name_snapshot' => $it['variant_name'],
                    'unit_price'           => $it['unit_price'],
                    'qty'                  => $it['qty'],
                    'line_total'           => $it['unit_price'] * $it['qty'],
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            // Lưu redemption nếu có mã
            $couponCode = session('cart.coupon_code');
            if ($couponCode && $totals['discount'] > 0) {
                $coupon = DB::table('coupons')->where('code', $couponCode)->first();
                if ($coupon) {
                    DB::table('coupon_redemptions')->insert([
                        'coupon_id'      => $coupon->id,
                        'user_id'        => Auth::id(),
                        'order_id'       => $orderId,
                        'code_snapshot'  => $coupon->code,
                        'discount_amount' => $totals['discount'],
                        'redeemed_at'    => now(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }

            DB::commit();

            // clear cart
            session()->forget('cart.items');
            session()->forget('cart.coupon_code');

            return response()->json([
                'message'  => 'Đặt hàng thành công.',
                'order_id' => $orderId,
                'code'     => $code,
                'redirect' => null, // có thể trả URL cổng thanh toán nếu dùng online
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['message' => 'Có lỗi khi tạo đơn hàng.'], 500);
        }
    }
}
