<?php
// app/Console/Commands/CouponRedemptionsBackfill.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Services\UserCouponService;

class CouponRedemptionsBackfill extends Command
{
    protected $signature = 'coupons:redemptions:backfill {--from-id=0}';
    protected $description = 'Backfill coupon_redemptions từ các đơn đã thanh toán';

    public function handle()
    {
        if (!Schema::hasTable('coupon_redemptions')) {
            $this->error('Table coupon_redemptions chưa tồn tại.');
            return 1;
        }

        $from = (int)$this->option('from-id');
        $chunk = 200;

        Order::where('id', '>', $from)
            ->where('payment_status', 'paid')
            ->orderBy('id')
            ->chunk($chunk, function ($orders) {
                foreach ($orders as $o) {
                    $codes = \App\Services\UserCouponService::extractCodesFromOrder($o);
                    foreach ($codes as $code) {
                        DB::transaction(function () use ($o, $code) {
                            $exists = DB::table('coupon_redemptions')
                                ->where('order_id', $o->id)->where('code', $code)->exists();
                            if ($exists) return;

                            $couponId = optional(DB::table('coupon_codes')->where('code', $code)->first())->coupon_id
                                ?? optional(DB::table('coupons')->where('code', $code)->first())->id;

                            DB::table('coupon_redemptions')->insert(array_filter([
                                'coupon_id' => $couponId ?? null,
                                'code' => $code,
                                'user_id' => $o->user_id ?: null,
                                'order_id' => $o->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ], fn($v) => !is_null($v)));
                        });
                    }
                }
            });

        $this->info('Done.');
        return 0;
    }
}
