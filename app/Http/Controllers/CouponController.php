<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    protected function getCartItems(): array
    {
        return session()->get('cart.items', []);
    }

    protected function computeDiscount(array $coupon, array $items): float
    {
        $eligible = 0;
        foreach ($items as $it) {
            $ok = false;
            switch ($coupon['applied_to']) {
                case 'category':
                    $ok = in_array($it['category_id'], $coupon['applies_to_ids']);
                    break;
                case 'brand':
                    $ok = in_array($it['brand_id'], $coupon['applies_to_ids']);
                    break;
                case 'product':
                    $ok = in_array($it['product_id'], $coupon['applies_to_ids']);
                    break;
                default:
                    $ok = true;
            }
            if ($ok) $eligible += $it['unit_price'] * $it['qty'];
        }

        if ($eligible <= 0) return 0;

        $discount = $coupon['discount_type'] === 'percent'
            ? $eligible * ($coupon['discount_value'] / 100)
            : min($coupon['discount_value'], $eligible);

        if (!is_null($coupon['max_discount'])) {
            $discount = min($discount, $coupon['max_discount']);
        }

        return round($discount, 2);
    }

    public function apply(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:50']]);
        $code = strtoupper(trim($request->input('code')));

        $cart = $this->getCartItems();
        if (empty($cart)) {
            return response()->json(['message' => 'Giỏ hàng đang trống.'], 422);
        }

        $now = now();
        $row = DB::table('coupons')
            ->where('code', $code)
            ->where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Mã không hợp lệ hoặc đã hết hạn.'], 422);
        }

        $coupon = [
            'id'              => $row->id,
            'code'            => $row->code,
            'discount_type'   => $row->discount_type,
            'discount_value'  => (float)$row->discount_value,
            'max_discount'    => $row->max_discount !== null ? (float)$row->max_discount : null,
            'min_order_total' => (float)$row->min_order_total,
            'applied_to'      => $row->applied_to,
            'applies_to_ids'  => $row->applies_to_ids ? json_decode($row->applies_to_ids, true) : [],
            'first_order_only' => (int)$row->first_order_only === 1,
            'usage_limit'     => $row->usage_limit,
            'usage_limit_per_user' => $row->usage_limit_per_user,
        ];

        // first order only?
        if ($coupon['first_order_only'] && Auth::id()) {
            $count = DB::table('orders')->where('user_id', Auth::id())->count();
            if ($count > 0) {
                return response()->json(['message' => 'Mã chỉ áp dụng cho đơn hàng đầu tiên.'], 422);
            }
        }

        // min total?
        $subtotal = array_reduce($cart, fn($c, $it) => $c + $it['unit_price'] * $it['qty'], 0);
        if ($subtotal < $coupon['min_order_total']) {
            return response()->json(['message' => 'Chưa đạt giá trị tối thiểu để dùng mã.'], 422);
        }

        // (Optional) check usage limits at system / per user via coupon_redemptions
        if ($coupon['usage_limit']) {
            $used = DB::table('coupon_redemptions')->where('coupon_id', $coupon['id'])->count();
            if ($used >= $coupon['usage_limit']) {
                return response()->json(['message' => 'Mã đã đạt giới hạn sử dụng.'], 422);
            }
        }
        if ($coupon['usage_limit_per_user'] && Auth::id()) {
            $usedByMe = DB::table('coupon_redemptions')
                ->where('coupon_id', $coupon['id'])->where('user_id', Auth::id())->count();
            if ($usedByMe >= $coupon['usage_limit_per_user']) {
                return response()->json(['message' => 'Bạn đã dùng mã này quá số lần cho phép.'], 422);
            }
        }

        $discount = $this->computeDiscount($coupon, $cart);
        if ($discount <= 0) {
            return response()->json(['message' => 'Mã không áp dụng cho sản phẩm trong giỏ.'], 422);
        }

        // Save coupon code vào session, tính toán chi tiết sẽ làm lại khi preview/checkout
        session()->put('cart.coupon_code', $coupon['code']);

        return response()->json([
            'message'  => 'Áp dụng mã thành công.',
            'code'     => $coupon['code'],
            'discount' => $discount,
        ]);
    }

    public function remove()
    {
        session()->forget('cart.coupon_code');
        return response()->json(['message' => 'Đã gỡ mã giảm giá.']);
    }
}
