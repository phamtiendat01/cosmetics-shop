<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting; // <-- THÊM DÒNG NÀY
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Ho_Chi_Minh');

        // Khung thời gian
        $now        = Carbon::now($tz)->endOfDay();
        $startMonth = Carbon::now($tz)->startOfMonth();
        $start14    = Carbon::now($tz)->subDays(13)->startOfDay();

        // Điều kiện "đơn có doanh thu"
        $revWhere = fn($q) => $q->where('payment_status', 'paid')
            ->whereIn('status', ['delivered', 'completed']);

        // ==== KPI ====
        $todayRevenue = (float) DB::table('orders')
            ->where($revWhere)
            ->whereDate('created_at', Carbon::now($tz)->toDateString())
            ->sum('grand_total');

        $monthRevenue = (float) DB::table('orders')
            ->where($revWhere)
            ->whereBetween('created_at', [$startMonth, $now])
            ->sum('grand_total');

        $paidOrderCountMonth = (int) DB::table('orders')
            ->where($revWhere)
            ->whereBetween('created_at', [$startMonth, $now])
            ->count();

        $ordersCount = $paidOrderCountMonth;
        $aov = $paidOrderCountMonth ? round($monthRevenue / $paidOrderCountMonth) : 0;

        // ==== Doanh thu 14 ngày ====
        $daysRaw = DB::table('orders')
            ->selectRaw('DATE(created_at) as d, SUM(grand_total) as v')
            ->where($revWhere)
            ->whereBetween('created_at', [$start14, $now])
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // Bổ sung ngày trống = 0
        $days = collect();
        for ($i = 0; $i < 14; $i++) {
            $d = Carbon::now($tz)->subDays(13 - $i)->toDateString();
            $v = (float) optional($daysRaw->firstWhere('d', $d))->v ?? 0;
            $days->push((object)['d' => $d, 'v' => $v]);
        }

        // ==== Phân bổ trạng thái (tháng) ====
        $statusAgg = DB::table('orders')
            ->selectRaw('status, COUNT(*) as c')
            ->whereBetween('created_at', [$startMonth, $now])
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        // ==== Kênh thanh toán (tháng) ====
        $payAgg = DB::table('orders')
            ->selectRaw('payment_method, COUNT(*) as c')
            ->whereBetween('created_at', [$startMonth, $now])
            ->groupBy('payment_method')
            ->pluck('c', 'payment_method')
            ->toArray();

        // ==== Top sản phẩm (14 ngày) ====
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where($revWhere)
            ->whereBetween('orders.created_at', [$start14, $now])
            ->selectRaw(
                'order_items.product_name_snapshot as product_name_snapshot,
                 SUM(order_items.qty) as qty,
                 SUM(order_items.line_total) as total'
            )
            ->groupBy('order_items.product_name_snapshot')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ==== Top ngành hàng (14 ngày) – nếu có bảng categories ====
        $categoryAgg = collect();
        if (Schema::hasTable('categories')) {
            $categoryAgg = DB::table('order_items')
                ->join('orders',   'orders.id',   '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->where($revWhere)
                ->whereBetween('orders.created_at', [$start14, $now])
                ->selectRaw('categories.name as name, SUM(order_items.line_total) as total')
                ->groupBy('categories.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        // ==== Cảnh báo tồn kho (biến thể) ====
        $lowStock           = 0;
        $lowStockItems      = collect();
        $lowStockThreshold  = (int) Setting::get('inventory.low_stock_threshold', config('shop.low_stock_threshold', 5));

        if (Schema::hasTable('product_variants')) {
            // Tự phát hiện cột số lượng: qty | stock
            $qtyCol = Schema::hasColumn('product_variants', 'qty') ? 'qty'
                : (Schema::hasColumn('product_variants', 'stock') ? 'stock' : null);

            if ($qtyCol) {
                $hasMin = Schema::hasColumn('product_variants', 'stock_min');

                // Đếm số biến thể thấp hơn ngưỡng
                $lowQuery = DB::table('product_variants');
                $hasMin
                    ? $lowQuery->whereColumn($qtyCol, '<=', 'stock_min')
                    : $lowQuery->where($qtyCol, '<=', $lowStockThreshold);

                $lowStock = (int) $lowQuery->count();

                // Lấy danh sách chi tiết để hiển thị
                $skuExpr = Schema::hasColumn('product_variants', 'sku') ? 'v.sku' : 'v.id as sku';
                $lowList = DB::table('product_variants as v')
                    ->join('products as p', 'p.id', '=', 'v.product_id')
                    ->selectRaw('p.name as product_name, ' . $skuExpr . ', v.' . $qtyCol . ' as qty')
                    ->when(
                        $hasMin,
                        fn($q) => $q->whereColumn('v.' . $qtyCol, '<=', 'v.stock_min'),
                        fn($q) => $q->where('v.' . $qtyCol, '<=', $lowStockThreshold)
                    )
                    ->orderBy('qty') // ít nhất lên trước
                    ->limit(10)
                    ->get();

                $lowStockItems = $lowList;
            }
        }

        $revLabels = $days->pluck('d')->values();
        $revSeries = $days->pluck('v')->map(fn($v) => (float)$v)->values();

        return view('admin.dashboard.index', [
            'todayRevenue'       => $todayRevenue,
            'monthRevenue'       => $monthRevenue,
            'ordersCount'        => $ordersCount,
            'aov'                => $aov,
            'days'               => $days,
            'revLabels'          => $revLabels,
            'revSeries'          => $revSeries,
            'statusAgg'          => $statusAgg,
            'payAgg'             => $payAgg,
            'topProducts'        => $topProducts,
            'categoryAgg'        => $categoryAgg,
            'lowStock'           => $lowStock,
            'lowStockItems'      => $lowStockItems,
            'lowStockThreshold'  => $lowStockThreshold,
        ]);
    }
}
