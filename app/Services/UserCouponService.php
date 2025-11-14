<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;

class UserCouponService
{
    /** Lấy các mã từ order (cột trực tiếp + meta nếu có) */
    public static function extractCodesFromOrder(Order $order): array
    {
        $candidates = [];

        foreach (['coupon_codes', 'coupon_code', 'coupon', 'discount_code', 'promotion_code'] as $key) {
            if (!isset($order->{$key}) || $order->{$key} === null) continue;
            $raw = $order->{$key};
            $arr = null;

            if (is_array($raw)) $arr = $raw;
            elseif (is_string($raw) && $raw !== '') {
                $trim = trim($raw);
                if (strlen($trim) && $trim[0] === '[') {
                    $decoded = json_decode($trim, true);
                    if (is_array($decoded)) $arr = $decoded;
                }
                if ($arr === null) $arr = preg_split('/[,\s]+/', $trim);
            }

            if ($arr) {
                foreach ($arr as $p) {
                    $p = is_string($p) ? trim($p) : '';
                    if ($p !== '') $candidates[] = $p;
                }
            }
        }

        foreach (['meta', 'metadata', 'extra'] as $metaKey) {
            if (!isset($order->{$metaKey})) continue;
            $m = $order->{$metaKey};
            if (is_string($m)) $m = json_decode($m, true);
            if (is_array($m)) {
                foreach (['coupon_codes', 'coupon_code', 'promotion_code'] as $k) {
                    if (!isset($m[$k])) continue;
                    $v = $m[$k];
                    $arr = is_array($v) ? $v : (is_string($v) ? preg_split('/[,\s]+/', $v) : []);
                    foreach ($arr as $p) {
                        $p = is_string($p) ? trim($p) : '';
                        if ($p !== '') $candidates[] = $p;
                    }
                }
            }
        }

        $codes = [];
        foreach ($candidates as $c) {
            $c = strtoupper(trim($c));
            if ($c !== '' && !in_array($c, $codes, true)) $codes[] = $c;
        }
        return $codes;
    }

    /** Idempotent: đảm bảo các mã đã được tiêu thụ cho đơn này */
    public static function ensureConsumed(Order $order): void
    {
        // ❌ KHÔNG return khi guest; guest vẫn cần ghi redemption để "Đã dùng" tăng
        $codes = self::extractCodesFromOrder($order);
        if (!$codes) return;

        foreach ($codes as $code) {
            self::consumeOne($order, $code); // an toàn gọi lặp lại
        }
    }

    /** Hoàn lại mã khi refund/cancel */
    public static function restoreCodesForOrder(Order $order): void
    {
        $codes = self::extractCodesFromOrder($order);
        if (!$codes) return;

        foreach ($codes as $code) {
            self::restoreOne($order, $code);
        }
    }

    /** ===== Helpers ===== */

    private static function recordRedemption(Order $order, int $couponId, string $code): void
    {
        if (!Schema::hasTable('coupon_redemptions')) return;

        $exists = DB::table('coupon_redemptions')
            ->where('order_id', $order->id)
            ->where('coupon_id', $couponId)
            ->where('code_snapshot', $code)
            ->exists();
        if ($exists) return;

        $discount = (int) ($order->discount_total ?? 0);

        DB::table('coupon_redemptions')->insert([
            'coupon_id'                => $couponId,
            'code'                     => $code,
            'user_id'                  => $order->user_id ?: null,
            'order_id'                 => $order->id,
            'code_snapshot'            => $code,
            'discount_amount'          => $discount,
            'shipping_discount_amount' => 0,
            'redeemed_at'              => now(),
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }

    private static function deleteRedemption(Order $order, int $couponId, string $code): void
    {
        if (!Schema::hasTable('coupon_redemptions')) return;

        DB::table('coupon_redemptions')
            ->where('order_id', $order->id)
            ->where('coupon_id', $couponId)
            ->where('code_snapshot', $code)
            ->delete();
    }

    /** Tiêu thụ 1 mã cho 1 đơn – idempotent theo (order_id, coupon_id, code) */
    private static function consumeOne(Order $order, string $code): void
    {
        DB::transaction(function () use ($order, $code) {
            $userId = (int) ($order->user_id ?? 0);
            $now    = now();

            // 0) Tìm coupon_id theo code
            $couponId = null;
            if (Schema::hasTable('coupon_codes')) {
                $row = DB::table('coupon_codes')->where('code', $code)->first();
                if ($row && isset($row->coupon_id)) $couponId = (int) $row->coupon_id;
            }
            if (!$couponId && Schema::hasTable('coupons')) {
                $c = DB::table('coupons')->where('code', $code)->first();
                if ($c) $couponId = (int) $c->id;
            }
            if (!$couponId) return;

            // Nếu đã có redemption thì thôi
            if (Schema::hasTable('coupon_redemptions')) {
                $exists = DB::table('coupon_redemptions')
                    ->where('order_id', $order->id)
                    ->where('coupon_id', $couponId)
                    ->where('code_snapshot', $code)
                    ->exists();
                if ($exists) return;
            }

            // 1) Đánh dấu code riêng đã dùng
            if (Schema::hasTable('coupon_codes')) {
                $q = DB::table('coupon_codes')->where('code', $code);
                if (Schema::hasColumn('coupon_codes', 'is_used')) {
                    $q->where(fn($w) => $w->whereNull('is_used')->orWhere('is_used', 0));
                }
                $payload = [
                    'is_used'    => Schema::hasColumn('coupon_codes', 'is_used') ? 1 : null,
                    'used_at'    => Schema::hasColumn('coupon_codes', 'used_at') ? $now : null,
                    'updated_at' => $now,
                ];
                if ($userId > 0 && Schema::hasColumn('coupon_codes', 'used_by')) {
                    $payload['used_by'] = $userId;
                }
                $q->update(array_filter($payload, fn($v) => !is_null($v)));
            }

            // 2) Trừ "Mã của tôi" chỉ khi có user
            if ($userId > 0 && Schema::hasTable('user_coupons')) {
                $base = DB::table('user_coupons')
                    ->where('user_id', $userId)
                    ->where('coupon_id', $couponId);

                if (Schema::hasColumn('user_coupons', 'times')) {
                    (clone $base)->where('times', '>', 0)
                        ->update(['times' => DB::raw('GREATEST(times - 1, 0)'), 'updated_at' => $now]);
                    (clone $base)->where('times', '<=', 0)->delete();
                } else {
                    $base->delete();
                }
            }

            // 3) Ghi redemption để Admin đếm "Đã dùng"
            self::recordRedemption($order, $couponId, $code);
        });
    }

    /** Hoàn 1 mã cho 1 đơn */
    private static function restoreOne(Order $order, string $code): void
    {
        DB::transaction(function () use ($order, $code) {
            $userId = (int) ($order->user_id ?? 0);
            $now    = now();

            $couponId = null;
            if (Schema::hasTable('coupon_codes')) {
                $row = DB::table('coupon_codes')->where('code', $code)->first();
                if ($row && isset($row->coupon_id)) $couponId = (int) $row->coupon_id;
            }
            if (!$couponId && Schema::hasTable('coupons')) {
                $c = DB::table('coupons')->where('code', $code)->first();
                if ($c) $couponId = (int) $c->id;
            }
            if (!$couponId) return;

            // 1) Mở khoá code riêng
            if (Schema::hasTable('coupon_codes')) {
                DB::table('coupon_codes')->where('code', $code)->update(array_filter([
                    'is_used'    => Schema::hasColumn('coupon_codes', 'is_used') ? 0 : null,
                    'used_by'    => Schema::hasColumn('coupon_codes', 'used_by') ? null : null,
                    'used_at'    => Schema::hasColumn('coupon_codes', 'used_at') ? null : null,
                    'updated_at' => $now,
                ], fn($v) => !is_null($v)));
            }

            // 2) Trả lại "Mã của tôi" chỉ khi có user
            if ($userId > 0 && Schema::hasTable('user_coupons')) {
                $base = DB::table('user_coupons')->where('user_id', $userId)->where('coupon_id', $couponId);

                if (Schema::hasColumn('user_coupons', 'times')) {
                    $exists = $base->first();
                    if ($exists) {
                        $base->update(['times' => DB::raw('COALESCE(times,0) + 1'), 'updated_at' => $now]);
                    } else {
                        DB::table('user_coupons')->insert(array_filter([
                            'user_id'    => $userId,
                            'coupon_id'  => $couponId,
                            'times'      => 1,
                            'source'     => Schema::hasColumn('user_coupons', 'source') ? 'refund-restore' : null,
                            'saved_at'   => Schema::hasColumn('user_coupons', 'saved_at') ? $now : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], fn($v) => !is_null($v)));
                    }
                } else {
                    if (!$base->exists()) {
                        DB::table('user_coupons')->insert([
                            'user_id'    => $userId,
                            'coupon_id'  => $couponId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            // 3) Xoá redemption
            self::deleteRedemption($order, $couponId, $code);
        });
    }
}
