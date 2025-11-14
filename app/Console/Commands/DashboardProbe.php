<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DashboardProbe extends Command
{
    protected $signature = 'dashboard:probe';
    protected $description = 'Scan DB tables & suggest column mappings for dashboard + shipping';

    public function handle()
    {
        $like = ['order', 'orders', 'order_item', 'orderitem', 'order_detail', 'orderdetail', 'customer', 'users', 'product', 'variant', 'category', 'setting', 'ship', 'carrier', 'zone', 'rate', 'coupon', 'payment'];

        $tables = collect(DB::select("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE()"))
            ->pluck('t')
            ->filter(fn($t) => collect($like)->contains(fn($k) => stripos($t, $k) !== false))
            ->values();

        $probe = [
            'database' => DB::getDatabaseName(),
            'generated_at' => now()->toDateTimeString(),
            'tables' => [],
        ];

        $guessMap = function ($cols) {
            $cols = collect($cols)->map(fn($c) => strtolower($c));
            $pick = fn($keys) => $cols->first(fn($c) => collect($keys)->contains(fn($k) => $c === $k || str_contains($c, $k)));
            return [
                'order_total'   => $pick(['total', 'grand_total', 'amount', 'sum']),
                'order_status'  => $pick(['status', 'state']),
                'order_code'    => $pick(['code', 'order_code', 'number']),
                'payment_method' => $pick(['payment_method', 'payment', 'pay_method']),
                'carrier_id'    => $pick(['shipping_carrier_id', 'carrier_id', 'ship_carrier']),
                'customer_id'   => $pick(['customer_id', 'user_id']),
                'created_at'    => $pick(['created_at', 'order_date', 'paid_at']),
                'product_name'  => $pick(['product_name_snapshot', 'name']),
                'item_total'    => $pick(['total', 'line_total', 'amount']),
                'item_qty'      => $pick(['qty', 'quantity']),
                'category_id'   => $pick(['category_id']),
                'stock'         => $pick(['stock', 'qty_on_hand']),
                'sku'           => $pick(['sku', 'code']),
            ];
        };

        foreach ($tables as $t) {
            $cols = collect(DB::select("SELECT column_name AS c, data_type AS dt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?", [$t]))
                ->map(fn($r) => ['name' => $r->c, 'type' => $r->dt])->values();

            try {
                $totalRows = (int) DB::table($t)->count();
            } catch (\Throwable $e) {
                $totalRows = null;
            }

            $recent = null;
            try {
                if (collect($cols)->pluck('name')->contains('created_at')) {
                    $recent = (int) DB::table($t)->where('created_at', '>=', now()->subDays(30))->count();
                }
            } catch (\Throwable $e) {
            }

            $samples = [];
            try {
                $samples = DB::table($t)->limit(2)->get()->map(fn($r) => (array)$r)->values();
            } catch (\Throwable $e) {
            }

            $probe['tables'][$t] = [
                'columns' => $cols,
                'rows_total' => $totalRows,
                'rows_recent_30d' => $recent,
                'samples' => $samples,
                'mapping_suggestion' => $guessMap(collect($cols)->pluck('name')),
            ];
        }

        $file = storage_path('app/dashboard_probe.json');
        file_put_contents($file, json_encode($probe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Saved: {$file}");
        return self::SUCCESS;
    }
}
