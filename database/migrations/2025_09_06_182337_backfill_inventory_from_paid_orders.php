<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 0) Bảo đảm mọi biến thể đều có bản ghi kho (qty=0)
            DB::statement("
                INSERT INTO inventories (product_variant_id, qty_in_stock, low_stock_threshold, created_at, updated_at)
                SELECT v.id, 0, 0, NOW(), NOW()
                FROM product_variants v
                LEFT JOIN inventories i ON i.product_variant_id = v.id
                WHERE i.product_variant_id IS NULL
            ");

            // 1) Tổng đã bán theo biến thể từ đơn PAID (loại trừ cancelled/refunded)
            $rows = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->where('o.payment_status', 'paid')
                ->whereNotIn('o.status', ['cancelled', 'refunded'])
                ->whereNotNull('oi.product_variant_id') // 👈 chỉ cột này
                ->groupBy('oi.product_variant_id')
                ->select('oi.product_variant_id as variant_id', DB::raw('SUM(oi.qty) as sold_qty'))
                ->get();

            // 2) TRỪ KHO: qty_in_stock = max(qty_in_stock - sold_qty, 0)
            foreach ($rows as $r) {
                $vid  = (int) ($r->variant_id ?? 0);
                $sold = (int) ($r->sold_qty ?? 0);
                if ($vid <= 0 || $sold <= 0) continue;

                DB::table('inventories')
                    ->where('product_variant_id', $vid)
                    ->update([
                        'qty_in_stock' => DB::raw('GREATEST(qty_in_stock - ' . $sold . ', 0)'),
                        'updated_at'   => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Data migration 1 lần — không rollback (tránh cộng ngược sai thực tế).
        // Nếu cần rollback, hãy khôi phục từ backup DB.
    }
};
