<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\UserPoint;

class PointsBackfill extends Command
{
    protected $signature = 'points:backfill {--user=}';
    protected $description = 'Backfill confirmed points for past completed & paid orders without point transactions';

    public function handle(): int
    {
        $q = Order::query()
            ->where('payment_status', 'paid')
            ->where('status', 'completed');

        if ($uid = $this->option('user')) {
            $q->where('user_id', $uid);
        }

        $count = 0;

        $q->orderBy('id')->chunkById(200, function ($orders) use (&$count) {
            foreach ($orders as $o) {
                if (!$o->user_id) continue;

                // Bỏ qua nếu đã có giao dịch earn-confirmed cho đơn này
                $exists = PointTransaction::where('user_id', $o->user_id)
                    ->where('type', 'earn')->where('status', 'confirmed')
                    ->where('reference_type', Order::class)
                    ->where('reference_id', $o->id)
                    ->exists();
                if ($exists) continue;

                // Tính tiền tính điểm: grand_total - shipping_fee
                $shipping = (int) ($o->shipping_fee ?? 0);
                $grand    = (int) ($o->grand_total ?? 0);
                $eligible = max(0, $grand - $shipping);
                $points   = intdiv($eligible, 1000);

                if ($points <= 0) continue;

                DB::transaction(function () use ($o, $points, $eligible, &$count) {
                    PointTransaction::create([
                        'user_id'        => $o->user_id,
                        'delta'          => $points,
                        'type'           => 'earn',
                        'status'         => 'confirmed',
                        'reference_type' => Order::class,
                        'reference_id'   => $o->id,
                        'meta'           => ['order_code' => $o->code, 'eligible_vnd' => $eligible, 'backfill' => true],
                        'created_at'     => $o->updated_at, // tuỳ chọn: bám thời điểm đơn
                        'updated_at'     => now(),
                    ]);

                    UserPoint::query()->updateOrCreate(
                        ['user_id' => $o->user_id],
                        ['balance' => DB::raw('balance + ' . (int) $points)]
                    );

                    $count++;
                });
            }
        });

        $this->info("Backfilled {$count} order(s).");
        return self::SUCCESS;
    }
}
