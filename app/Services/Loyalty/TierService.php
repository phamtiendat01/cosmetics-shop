<?php

namespace App\Services\Loyalty;

use App\Models\Order;
use App\Models\MemberTier;
use App\Models\UserTier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TierService
{
    /** Chi tiêu năm dương lịch (đơn đã thanh toán, không huỷ/hoàn) */
    public function yearSpend(User $user, ?int $year = null): int
    {
        $year = $year ?? now()->year;

        return (int) Order::query()
            ->where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->whereYear('created_at', $year)
            ->sum(DB::raw('GREATEST(subtotal - discount_total, 0)'));
    }

    /** Lấy hạng phù hợp với mức chi tiêu */
    public function determineTierBySpend(int $spend): MemberTier
    {
        $tier = MemberTier::where('active', true)
            ->where('min_spend_year', '<=', $spend)
            ->orderByDesc('min_spend_year')
            ->first();

        // Fallback an toàn: về 'member' hoặc hạng active thấp nhất
        if (!$tier) {
            $tier = MemberTier::where('code', 'member')->first()
                ?: MemberTier::where('active', true)->orderBy('min_spend_year')->first();
        }
        return $tier;
    }

    /** Hạng kế tiếp (nếu có) */
    public function nextTier(?MemberTier $current): ?MemberTier
    {
        if (!$current) {
            return MemberTier::where('active', true)->orderBy('min_spend_year')->first();
        }

        return MemberTier::where('active', true)
            ->where('min_spend_year', '>', $current->min_spend_year)
            ->orderBy('min_spend_year')
            ->first();
    }

    /** Đánh giá & lưu/khởi tạo user_tiers cho user hiện tại */
    public function evaluate(User $user): UserTier
    {
        $spend = $this->yearSpend($user);
        $tier  = $this->determineTierBySpend($spend);

        // Giữ hạng đến 31/12 năm sau (Ulta-style)
        $keepTo = Carbon::create(now()->year + 1, 12, 31, 23, 59, 59);

        $userTier = UserTier::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tier_id'             => $tier->id,
                'qualified_at'        => now(),
                'expires_at'          => $keepTo,
                'current_year_spend'  => $spend,
                'last_evaluated_at'   => now(),
            ]
        );

        return $userTier->load('tier');
    }

    /**
     * Dữ liệu tổng hợp cho UI:
     * - yearSpend: chi tiêu năm hiện tại
     * - current: MemberTier hiện tại
     * - next: MemberTier kế (hoặc null nếu đang cao nhất)
     * - toNext: còn thiếu bao nhiêu để lên hạng kế
     * - percent: % tiến độ lên hạng kế
     * - expiresAt: ngày hết hiệu lực hạng hiện tại
     */
    public function progressSummary(User $user): array
    {
        $spend = $this->yearSpend($user);

        // LẤY HẠNG HIỆN TẠI ĐÚNG KIỂU: không dùng optional()
        $current = $user->memberTier?->tier;     // ?MemberTier
        if (!$current) {
            $current = $this->determineTierBySpend($spend);
        }

        $next    = $this->nextTier($current);

        $toNext  = $next ? max(0, $next->min_spend_year - $spend) : 0;
        $range   = $next ? max(1, $next->min_spend_year - $current->min_spend_year) : 1;
        $gained  = max(0, $spend - $current->min_spend_year);
        $percent = $next ? max(0, min(100, (int) floor($gained * 100 / $range))) : 100;

        return [
            'yearSpend' => $spend,
            'current'   => $current,
            'next'      => $next,
            'toNext'    => $toNext,
            'percent'   => $percent,
            'expiresAt' => $user->memberTier?->expires_at, // null-safe
        ];
    }
}
