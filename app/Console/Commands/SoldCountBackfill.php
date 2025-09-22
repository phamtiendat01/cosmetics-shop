<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SoldCountBackfill extends Command
{
    protected $signature = 'sold:backfill';
    protected $description = 'Recalculate products.sold_count from PAID orders (exclude cancelled/refunded)';

    public function handle(): int
    {
        $this->info('Reset sold_count to 0...');
        DB::table('products')->update(['sold_count' => 0]);

        $this->info('Aggregate from order_items + orders...');
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.payment_status', 'paid')
            ->whereNotIn('o.status', ['cancelled', 'refunded'])
            ->groupBy('oi.product_id')
            ->select('oi.product_id', DB::raw('SUM(oi.qty) as qty'))
            ->get();

        $bar = $this->output->createProgressBar($rows->count());
        foreach ($rows as $r) {
            DB::table('products')->where('id', $r->product_id)
                ->update(['sold_count' => (int)$r->qty]);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Done!');
        return self::SUCCESS;
    }
}
