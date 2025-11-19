<?php

namespace App\Tools\Bot;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * GetUserCouponsTool - Lấy danh sách mã giảm giá của user
 */
class GetUserCouponsTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để xem mã giảm giá.',
            ];
        }

        $userId = auth()->id();
        $now = Carbon::now();
        
        // ✅ Log để debug
        Log::info('GetUserCouponsTool: Starting', [
            'user_id' => $userId,
            'is_authenticated' => auth()->check(),
        ]);

        try {
            // Lấy coupons từ user_coupons (tương tự CouponController::mine)
            $rows = DB::table('user_coupons as uc')
                ->join('coupons as c', 'c.id', '=', 'uc.coupon_id')
                ->leftJoin('coupon_usages as u', function ($j) use ($userId) {
                    $j->on('u.coupon_id', '=', 'c.id')
                      ->on('u.user_id', '=', DB::raw($userId));
                })
                ->where('uc.user_id', $userId)
                ->selectRaw(
                    'uc.id as user_coupon_id, uc.code as user_code, uc.times,
                     c.id as coupon_id, c.code as sys_code, c.name, c.description,
                     c.discount_type, c.discount_value, c.max_discount, c.min_order_total,
                     c.starts_at, c.ends_at, c.is_active,
                     COUNT(u.id) as used_count'
                )
                ->groupBy(
                    'uc.id', 'uc.code', 'uc.times',
                    'c.id', 'c.code', 'c.name', 'c.description',
                    'c.discount_type', 'c.discount_value', 'c.max_discount', 'c.min_order_total',
                    'c.starts_at', 'c.ends_at', 'c.is_active'
                )
                ->orderByDesc('uc.id')
                ->get();

            $coupons = [];
            foreach ($rows as $x) {
                $active = (int)$x->is_active === 1
                    && (empty($x->starts_at) || Carbon::parse($x->starts_at)->lte($now))
                    && (empty($x->ends_at) || Carbon::parse($x->ends_at)->gte($now));

                $remain = max(0, (int)($x->times ?? 0) - (int)$x->used_count);
                $isPct = strtolower($x->discount_type ?? '') === 'percent';

                $userCode = $x->user_code ? strtoupper($x->user_code) : null;
                $systemCode = $x->sys_code ? strtoupper($x->sys_code) : null;
                $code = $userCode ?: $systemCode;
                $discountText = $isPct
                    ? ('Giảm ' . rtrim(rtrim((string)$x->discount_value, '0'), '.') . '%' 
                       . ($x->max_discount ? ' • Tối đa ' . number_format($x->max_discount, 0, ',', '.') . 'đ' : ''))
                    : ('Trừ ' . number_format((int)$x->discount_value, 0, ',', '.') . 'đ');

                $coupons[] = [
                    'coupon_id' => (int)$x->coupon_id,
                    'id' => (int)$x->coupon_id,
                    'code' => $code,
                    'user_code' => $userCode,
                    'system_code' => $systemCode,
                    'apply_code' => $systemCode ?: $code,
                    'name' => $x->name,
                    'description' => $x->description,
                    'discount_text' => $discountText,
                    'min_order_total' => (int)($x->min_order_total ?? 0),
                    'expires_at' => $x->ends_at ? Carbon::parse($x->ends_at)->format('d/m/Y H:i') : null,
                    'usable' => $active && ($x->times === null || $remain > 0),
                    'reason' => !$active ? 'Hết hạn / Không hiệu lực' : (($x->times !== null && $remain <= 0) ? 'Đã dùng hết' : null),
                    'left' => $x->times === null ? null : $remain,
                ];
            }

            // Chỉ lấy coupons có thể dùng được
            $usableCoupons = array_values(array_filter($coupons, fn($c) => $c['usable']));
            
            // ✅ Log để debug
            Log::info('GetUserCouponsTool: Returning coupons', [
                'user_id' => $userId,
                'coupons_count' => count($usableCoupons),
                'first_coupon_code' => $usableCoupons[0]['code'] ?? null,
                'first_coupon_discount_text' => $usableCoupons[0]['discount_text'] ?? null,
            ]);

            return [
                'success' => true,
                'coupons' => $usableCoupons,
                'total' => count($usableCoupons),
                'message' => count($usableCoupons) > 0 
                    ? 'Bạn có ' . count($usableCoupons) . ' mã giảm giá có thể sử dụng.'
                    : 'Bạn chưa có mã giảm giá nào.',
            ];
        } catch (\Throwable $e) {
            Log::error('GetUserCouponsTool failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'message' => 'Không thể lấy danh sách mã giảm giá. Vui lòng thử lại!',
            ];
        }
    }
}

