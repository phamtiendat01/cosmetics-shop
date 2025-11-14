<?php

namespace App\Services\Loyalty;

use App\Models\{User, ShippingVoucher, UserShippingVoucher, Coupon, CouponUsage};
use Illuminate\Support\Str;

class PerkIssuer
{
    public function attachTierCoupon(User $user, ?string $couponCode): void
    {
        if (!$couponCode) return;

        $coupon = Coupon::query()->where('code', $couponCode)->where('is_active', 1)->first();
        if (!$coupon) return;

        // Nếu hệ thống của bạn cần "pre-assign" -> tạo 1 usage trống (used_at = null)
        CouponUsage::firstOrCreate(
            ['user_id' => $user->id, 'coupon_id' => $coupon->id],
            ['used_at' => null, 'meta' => ['source' => 'tier-auto']]
        );
    }

    public function issueMonthlyShip(User $user, int $quota): void
    {
        if ($quota <= 0) return;

        $tplId = config('loyalty.monthly_shipping_voucher_id');
        if (!$tplId) return;

        $tpl = ShippingVoucher::find($tplId);
        if (!$tpl || !$tpl->is_active) return;

        // phát đúng số lượng còn thiếu trong tháng
        $monthKey = now()->format('Y-m');
        $issued = UserShippingVoucher::query()
            ->where('user_id', $user->id)
            ->where('shipping_voucher_id', $tpl->id)
            ->where('meta->month', $monthKey)
            ->count();

        $need = max(0, $quota - $issued);
        for ($i = 0; $i < $need; $i++) {
            UserShippingVoucher::firstOrCreate([
                'user_id' => $user->id,
                'shipping_voucher_id' => $tpl->id,
                // code cá nhân hóa để tránh trùng: TPLCODE-USER-xxxx
                'code' => $tpl->code . '-' . strtoupper(Str::padLeft((string)$user->id, 4, '0')) . '-' . Str::upper(Str::random(4)),
            ], [
                'times' => 1,
                'used_times' => 0,
                'expires_at' => now()->endOfMonth(),
                'meta' => ['source' => 'tier-monthly', 'month' => $monthKey],
            ]);
        }
    }
}
