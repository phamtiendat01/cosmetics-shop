<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Ho_Chi_Minh');

        // Mốc thời gian
        $now        = Carbon::now($tz)->endOfDay();
        $startMonth = Carbon::now($tz)->startOfMonth();
        $start14    = Carbon::now($tz)->subDays(13)->startOfDay();

        // Định nghĩa các trạng thái coi như "hoàn tất" để ghi nhận doanh thu
        $completed = ['delivered', 'completed', 'da_giao', 'hoan_thanh'];

        // === Điều kiện đơn đã thu được tiền & đã hoàn tất (cho các thống kê doanh thu) ===
        $paidAndCompleted = function ($q) use ($completed) {
            $q->where('payment_status', 'paid')
                ->where(function ($x) use ($completed) {
                    $x->whereIn('status', $completed)
                        ->orWhereIn('order_status', $completed);
                });
        };

        // === KPI ===
        $todayRevenue = (float) DB::table('orders')
            ->where($paidAndCompleted)
            ->whereDate('created_at', Carbon::now($tz)->toDateString())
            ->sum('grand_total');

        $monthRevenue = (float) DB::table('orders')
            ->where($paidAndCompleted)
            ->whereBetween('created_at', [$startMonth, $now])
            ->sum('grand_total');

        $paidOrderCountMonth = (int) DB::table('orders')
            ->where($paidAndCompleted)
            ->whereBetween('created_at', [$startMonth, $now])
            ->count();

        $ordersCount = $paidOrderCountMonth;
        $aov         = $paidOrderCountMonth ? round($monthRevenue / $paidOrderCountMonth) : 0;

        // === Doanh thu 14 ngày ===
        $rows = DB::table('orders')
            ->selectRaw('DATE(created_at) as d, SUM(grand_total) as v')
            ->where($paidAndCompleted)
            ->whereBetween('created_at', [$start14, $now])
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $revMap = [];
        foreach ($rows as $r) {
            $revMap[$r->d] = (float) $r->v;
        }

        $revLabels = [];
        $revSeries = [];
        for ($i = 0; $i < 14; $i++) {
            $d = Carbon::now($tz)->subDays(13 - $i)->toDateString();
            $revLabels[] = $d;
            $revSeries[] = isset($revMap[$d]) ? (float) $revMap[$d] : 0.0;
        }

        // === Tỉ lệ trạng thái trong tháng (gộp status + order_status) ===
        $stRows = DB::table('orders')
            ->selectRaw('COALESCE(NULLIF(status, \'\'), order_status) as st, COUNT(*) as c')
            ->whereBetween('created_at', [$startMonth, $now])
            ->groupBy('st')
            ->get();

        $statusAgg = [];
        foreach ($stRows as $r) {
            $statusAgg[$r->st ?: 'unknown'] = (int) $r->c;
        }

        // === Kênh thanh toán trong tháng ===
        $payRows = DB::table('orders')
            ->selectRaw('payment_method, COUNT(*) as c')
            ->whereBetween('created_at', [$startMonth, $now])
            ->groupBy('payment_method')
            ->get();

        $payAgg = [];
        foreach ($payRows as $r) {
            $payAgg[$r->payment_method ?: 'N/A'] = (int) $r->c;
        }

        // === Top sản phẩm (14 ngày) ===
        $topProducts = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where($paidAndCompleted)
            ->whereBetween('o.created_at', [$start14, $now])
            ->selectRaw(
                'oi.product_name_snapshot as name,
                 SUM(oi.qty) as qty,
                 SUM(oi.line_total) as total'
            )
            ->groupBy('oi.product_name_snapshot')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // === Top ngành hàng (14 ngày) nếu có categories ===
        $categoryAgg = collect();
        if (Schema::hasTable('categories')) {
            $categoryAgg = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->join('products as p', 'p.id', '=', 'oi.product_id')
                ->join('categories as c', 'c.id', '=', 'p.category_id')
                ->where($paidAndCompleted)
                ->whereBetween('o.created_at', [$start14, $now])
                ->selectRaw('c.name as name, SUM(oi.line_total) as total')
                ->groupBy('c.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        // === Cảnh báo tồn kho: JOIN inventories (đúng với schema của bạn) ===
        $lowStockCount = 0;
        $lowStockItems = collect();

        if (Schema::hasTable('inventories')) {
            $lowStockCount = (int) DB::table('inventories')
                ->whereColumn('qty_in_stock', '<=', 'low_stock_threshold')
                ->count();

            $lowStockItems = DB::table('product_variants as v')
                ->join('inventories as i', 'i.product_variant_id', '=', 'v.id')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->selectRaw('p.name as product_name, v.sku as sku, i.qty_in_stock as qty, i.low_stock_threshold as min_qty')
                ->whereColumn('i.qty_in_stock', '<=', 'i.low_stock_threshold')
                ->orderBy('i.qty_in_stock')
                ->limit(10)
                ->get();
        }

        return view('admin.dashboard.index', [
            'todayRevenue'    => $todayRevenue,
            'monthRevenue'    => $monthRevenue,
            'ordersCount'     => $ordersCount,
            'aov'             => $aov,
            'revLabels'       => $revLabels,
            'revSeries'       => $revSeries,
            'statusAgg'       => $statusAgg,
            'payAgg'          => $payAgg,
            'topProducts'     => $topProducts,
            'categoryAgg'     => $categoryAgg,
            'lowStockCount'   => $lowStockCount,
            'lowStockItems'   => $lowStockItems,
        ]);
    }
}
