<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Tính giảm giá cho giỏ hàng.
     * $cart = [
     *   'items' => [
     *     ['product_id'=>, 'brand_id'=>null|int, 'category_ids'=>[], 'qty'=>int, 'price'=>int, 'is_sale'=>bool],
     *     ...
     *   ],
     *   'subtotal' => int,
     *   'shipping_fee' => int|null
     * ]
     */
    public function compute(array $cart, Coupon $coupon): array
    {
        // 1) Kiểm tra trạng thái/khung giờ
        if (!$coupon->isCurrentlyActive()) {
            return $this->fail('Mã giảm giá không còn hiệu lực.');
        }

        // 2) Ngưỡng tối thiểu
        $subtotal = (int)($cart['subtotal'] ?? 0);
        if ($subtotal <= 0) {
            return $this->fail('Giỏ hàng trống.');
        }
        if ($coupon->min_subtotal > 0 && $subtotal < $coupon->min_subtotal) {
            return $this->fail('Đơn chưa đạt giá trị tối thiểu để áp dụng mã.');
        }

        // 3) Xác định base_amount (phạm vi áp dụng)
        $scope = $coupon->apply_scope; // order|item|shipping
        $baseAmount = 0;

        if ($scope === 'shipping') {
            $ship = (int)($cart['shipping_fee'] ?? 0);
            if ($ship <= 0) return $this->fail('Đơn này không có phí vận chuyển để áp dụng.');
            $baseAmount = $ship;
        } elseif ($scope === 'order') {
            $baseAmount = $subtotal;
        } else { // item scope
            $baseAmount = $this->eligibleItemsTotal($cart['items'] ?? [], $coupon);
            if ($baseAmount <= 0) {
                return $this->fail('Không có mặt hàng nào phù hợp điều kiện mã.');
            }
        }

        // 4) Tính số tiền giảm
        $discount = 0;

        switch ($coupon->discount_type) {
            case 'percent':
                $discount = (int) floor($baseAmount * max(0, min(100, $coupon->percent)) / 100);
                if ($coupon->max_discount > 0) {
                    $discount = min($discount, (int)$coupon->max_discount);
                }
                break;

            case 'fixed':
                $discount = (int) min(max(0, $coupon->amount), $baseAmount);
                break;

            case 'free_shipping':
                $cap = (int) $coupon->shipping_cap;
                $discount = $cap > 0 ? min($baseAmount, $cap) : $baseAmount;
                break;

            default:
                return $this->fail('Loại mã giảm giá không hợp lệ.');
        }

        // 5) Không cho giảm vượt tổng đơn
        $discount = max(0, min($discount, $subtotal));

        return [
            'ok' => true,
            'discount' => $discount,
            'apply_scope' => $scope,
            'message' => $discount > 0 ? 'Áp dụng thành công.' : 'Mã hợp lệ nhưng không làm giảm giá trị đơn.',
        ];
    }
    public static function applyCoupon(string $code): array
    {
        $code = strtoupper(trim($code));
        $c = Coupon::where('code', $code)->first();
        if (!$c) return ['ok' => false, 'message' => 'Mã không tồn tại'];

        // Build cart từ session như FE đang dùng
        $items = session('cart.items', []);
        if (!$items) return ['ok' => false, 'message' => 'Giỏ hàng trống'];

        $cart = app(\App\Http\Controllers\CouponController::class)->buildCart(null); // tái dùng helper có sẵn
        $res  = app(CouponService::class)->compute($cart, $c);
        if (!($res['ok'] ?? false) || ($res['discount'] ?? 0) <= 0) {
            return ['ok' => false, 'message' => $res['message'] ?? 'Không áp được mã'];
        }

        session([
            'applied_coupon' => [
                'coupon_id' => (int)$c->id,
                'code'      => $code,
                'discount'  => (int)$res['discount'],
                'keys'      => $res['keys'] ?? array_keys($items),
            ],
        ]);

        return ['ok' => true, 'code' => $code, 'discount' => (int)$res['discount']];
    }
    public static function estimateShipping(?string $city = null, ?float $subtotal = null): array
    {
        $subtotal = (float)($subtotal ?? (session('cart.subtotal', 0)));
        $freeAt = (int) (optional(DB::table('settings')->where('key', 'shipping.free_threshold')->first())->value ?? 0);
        if ($freeAt > 0 && $subtotal >= $freeAt) return ['fee' => 0, 'reason' => 'free_threshold'];

        // Fallback rất đơn giản: lấy rate chung (zone_id null) rẻ nhất
        $row = DB::table('shipping_rates')->whereNull('zone_id')->where('enabled', 1)->orderBy('base_fee')->first();
        $fee = (int) ($row->base_fee ?? 30000); // default
        return ['fee' => $fee, 'reason' => 'base_rate'];
    }

    // ===== Helpers =====
    private function eligibleItemsTotal(array $items, Coupon $coupon): int
    {
        // Nếu không có bảng targets thì coi như áp dụng toàn bộ items
        if (!Schema::hasTable('coupon_targets')) {
            return array_reduce($items, fn($s, $it) => $s + (int)$it['price'] * (int)$it['qty'], 0);
        }

        $include = $coupon->targets()->where('mode', 'include')->get();
        $exclude = $coupon->targets()->where('mode', 'exclude')->get();

        $inProd  = $include->where('target_type', 'product');
        $inBrand = $include->where('target_type', 'brand');
        $inCat   = $include->where('target_type', 'category');

        $exProd  = $exclude->where('target_type', 'product');
        $exBrand = $exclude->where('target_type', 'brand');
        $exCat   = $exclude->where('target_type', 'category');

        $needInclude = $include->count() > 0;

        $sum = 0;
        foreach ($items as $it) {
            $pid   = $it['product_id'] ?? null;
            $bid   = $it['brand_id'] ?? null;
            $cats  = collect($it['category_ids'] ?? []);
            $price = (int)($it['price'] ?? 0);
            $qty   = (int)($it['qty'] ?? 0);

            // loại trừ
            if ($pid && $exProd->contains('target_id', $pid)) continue;
            if ($bid && $exBrand->contains('target_id', $bid)) continue;
            if ($cats->isNotEmpty() && $exCat->whereIn('target_id', $cats)->count() > 0) continue;

            // nếu có include -> phải match ít nhất 1
            if ($needInclude) {
                $ok = false;
                if ($pid && $inProd->contains('target_id', $pid)) $ok = true;
                if ($bid && $inBrand->contains('target_id', $bid)) $ok = true;
                if ($cats->isNotEmpty() && $inCat->whereIn('target_id', $cats)->count() > 0) $ok = true;
                if (!$ok) continue;
            }

            $sum += $price * $qty;
        }

        return $sum;
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'discount' => 0, 'message' => $message];
    }
}
