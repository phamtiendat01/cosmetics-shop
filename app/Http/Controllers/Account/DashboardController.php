<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;

class DashboardController extends Controller
{
    /**
     * Trang Tổng quan tài khoản (/account)
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // 1) Wishlist (giữ nguyên)
        $wishlistSession = session('wishlist.items');
        if (is_null($wishlistSession)) $wishlistSession = session('wishlist');
        $wishlistCount = is_array($wishlistSession)
            ? count($wishlistSession)
            : (is_countable($wishlistSession) ? count($wishlistSession) : 0);

        // 2) Đơn hàng của user (giữ + mở rộng)
        $ordersBase = Order::select('id', 'code', 'status', 'payment_status', 'grand_total', 'created_at')
            ->where('user_id', $user->id);

        $stats = [
            'total_orders'     => (clone $ordersBase)->count(),
            'open_orders'      => (clone $ordersBase)->whereIn('status', ['pending', 'processing', 'confirmed'])->count(),
            'completed_orders' => (clone $ordersBase)->whereIn('status', ['completed', 'delivered'])->count(),
            'cancelled_orders' => (clone $ordersBase)->whereIn('status', ['cancelled', 'refunded'])->count(),
            'total_spent'      => (clone $ordersBase)->whereIn('payment_status', ['paid', 'partially_refunded', 'refunded'])->sum('grand_total'),
        ];

        $recentOrders = (clone $ordersBase)->orderByDesc('created_at')->limit(5)->get();

        // ======= 2.b) DỮ LIỆU CHART =======
        // a) Xu hướng 6 tháng: đơn & chi tiêu
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->startOfMonth();
        }
        $labels = array_map(fn($d) => $d->format('m/Y'), $months);

        $rawByMonth = (clone $ordersBase)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(grand_total) as amount')
            )
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $seriesOrders = [];
        $seriesAmount = [];
        foreach ($months as $d) {
            $key = $d->format('Y-m');
            $row = $rawByMonth[$key] ?? null;
            $seriesOrders[] = (int)($row->cnt ?? 0);
            $seriesAmount[] = (int)round($row->amount ?? 0);
        }

        // b) Tỉ trọng trạng thái đơn
        $statusCounts = (clone $ordersBase)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();
        // map nhãn đẹp (tùy hệ thống)
        $statusMap = [
            'pending'   => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'confirmed' => 'Đã xác nhận',
            'delivered' => 'Đã giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'refunded'  => 'Hoàn tiền',
        ];
        $pieLabels = [];
        $pieValues = [];
        foreach ($statusCounts as $k => $v) {
            $pieLabels[] = $statusMap[$k] ?? ucfirst(str_replace('_', ' ', $k));
            $pieValues[] = (int)$v;
        }

        // c) Top 5 sản phẩm/danh mục mua nhiều (fallback nếu thiếu bảng)
        $topLabels = [];
        $topValues = [];
        $topTitle  = 'Sản phẩm mua nhiều';
        if (
            Schema::hasTable('order_items') &&
            Schema::hasTable('products')
        ) {
            $q = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
                ->where('o.user_id', $user->id)
                ->select(DB::raw('COALESCE(p.name, "Sản phẩm") as label'), DB::raw('SUM(oi.qty) as total_qty'))
                ->groupBy('label')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();

            if ($q->isEmpty() && Schema::hasTable('categories')) {
                // fallback sang danh mục nếu muốn
                $topTitle = 'Danh mục mua nhiều';
                $q = DB::table('order_items as oi')
                    ->join('orders as o', 'o.id', '=', 'oi.order_id')
                    ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
                    ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                    ->where('o.user_id', $user->id)
                    ->select(DB::raw('COALESCE(c.name, "Khác") as label'), DB::raw('SUM(oi.qty) as total_qty'))
                    ->groupBy('label')
                    ->orderByDesc('total_qty')
                    ->limit(5)
                    ->get();
            }

            foreach ($q as $row) {
                $topLabels[] = $row->label;
                $topValues[] = (int)$row->total_qty;
            }
        }

        $charts = [
            'labels'        => $labels,
            'seriesOrders'  => $seriesOrders,
            'seriesAmount'  => $seriesAmount,
            'pieLabels'     => $pieLabels,
            'pieValues'     => $pieValues,
            'topLabels'     => $topLabels,
            'topValues'     => $topValues,
            'topTitle'      => $topTitle,
        ];

        // 3) Reviews & Coupons (giữ)
        $reviewCount = Schema::hasTable('reviews')
            ? DB::table('reviews')->where('user_id', $user->id)->count()
            : 0;

        $redeemedCoupons = Schema::hasTable('coupon_redemptions')
            ? DB::table('coupon_redemptions')->where('user_id', $user->id)->count()
            : 0;

        // 4) Địa chỉ mặc định JSON (giữ)
        $shipping = null;
        $billing = null;
        if (isset($user->default_shipping_address)) {
            $shipping = is_array($user->default_shipping_address)
                ? $user->default_shipping_address
                : json_decode($user->default_shipping_address, true);
        }
        if (isset($user->default_billing_address)) {
            $billing = is_array($user->default_billing_address)
                ? $user->default_billing_address
                : json_decode($user->default_billing_address, true);
        }
        $shipping = null;
        $billing  = null;

        if (Schema::hasTable('user_addresses')) {
            $q = UserAddress::query()->where('user_id', $user->id);

            $shipAddr = (clone $q)->where('is_default_shipping', 1)->first();
            $billAddr = (clone $q)->where('is_default_billing', 1)->first();

            // nếu chưa set default, lấy cái mới nhất
            if (!$shipAddr) $shipAddr = (clone $q)->orderByDesc('is_default_shipping')->orderByDesc('id')->first();
            if (!$billAddr) $billAddr = $shipAddr;

            $normalize = function ($a) use ($user) {
                if (!$a) return null;
                return [
                    'name'     => $a->name ?? $user->name,
                    'phone'    => $a->phone ?? $user->phone,
                    'line1'    => $a->line1 ?? $a->address ?? '',
                    'line2'    => $a->line2 ?? '',
                    'ward'     => $a->ward ?? $a->commune ?? $a->ward_name ?? '',
                    'district' => $a->district ?? $a->district_name ?? '',
                    'province' => $a->city ?? $a->province ?? $a->province_name ?? $a->state ?? '',
                ];
            };

            $shipping = $normalize($shipAddr);
            $billing  = $normalize($billAddr);
        }

        // Fallback về JSON cũ nếu chưa có địa chỉ sổ
        if (!$shipping && isset($user->default_shipping_address)) {
            $arr = is_array($user->default_shipping_address)
                ? $user->default_shipping_address
                : json_decode($user->default_shipping_address, true);
            if (is_array($arr)) $shipping = $arr;
        }
        if (!$billing && isset($user->default_billing_address)) {
            $arr = is_array($user->default_billing_address)
                ? $user->default_billing_address
                : json_decode($user->default_billing_address, true);
            if (is_array($arr)) $billing = $arr;
        }


        return view('account.dashboard', compact(
            'user',
            'stats',
            'recentOrders',
            'wishlistCount',
            'reviewCount',
            'redeemedCoupons',
            'shipping',
            'billing',
            'charts'
        ));
    }
}
