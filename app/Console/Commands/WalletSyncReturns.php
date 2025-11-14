<?php

namespace App\Console\Commands;

use App\Models\OrderReturn;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Console\Command;

class WalletSyncReturns extends Command
{
    protected $signature = 'wallet:sync-returns {--user=} {--dry}';
    protected $description = 'Đồng bộ các yêu cầu hoàn (store_credit) vào ví, idempotent';

    public function handle()
    {
        $q = OrderReturn::query()
            ->with(['order:id,code,user_id', 'order.user:id'])
            ->where('status', 'refunded')                 // chỉ hoàn thành
            ->where('refund_method', 'store_credit');     // hoàn vào ví

        if ($uid = $this->option('user')) {
            $q->where('user_id', (int)$uid);
        }

        $returns = $q->get();

        $bar = $this->output->createProgressBar($returns->count());
        $bar->start();

        foreach ($returns as $ret) {
            $bar->advance();

            if (!$ret->user_id || !$ret->final_refund) continue;

            $wallet = Wallet::firstOrCreate(['user_id' => $ret->user_id], ['balance' => 0, 'hold' => 0, 'currency' => 'VND']);

            $meta = [
                'title'     => 'Hoàn đơn ' . ($ret->order->code ?? ('#' . $ret->order_id)),
                'ref_code'  => $ret->order->code ?? null,
                'source'    => 'wallet:sync-returns',
            ];

            if (!$this->option('dry')) {
                WalletService::creditOnce(
                    $wallet,
                    (int)$ret->final_refund,
                    'order_return',
                    (int)$ret->id,
                    $meta
                );
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Done.');
        return self::SUCCESS;
    }
}
