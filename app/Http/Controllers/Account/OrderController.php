<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Nhãn hiển thị
        $statusOptions = [
            'pending'   => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'confirmed' => 'Đã xác nhận',
            'shipping'  => 'Đang giao',
            'delivered' => 'Đã giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'refunded'  => 'Hoàn tiền',
        ];
        $payOptions = [
            'unpaid'             => 'Chưa thanh toán',
            'paid'               => 'Đã thanh toán',
            'cod'                => 'COD',
            'failed'             => 'Thất bại',
            'refunded'           => 'Hoàn tiền',
            'partially_refunded' => 'Hoàn một phần',
        ];

        $filters = [
            'q'       => trim((string) $request->get('q', '')),
            'status'  => $request->get('status'),
            'payment' => $request->get('payment'),
            'from'    => $request->get('from'),
            'to'      => $request->get('to'),
        ];

        $q = Order::query()->select('id', 'code', 'status', 'payment_status', 'grand_total', 'created_at');

        // Khớp cột sở hữu đơn hàng theo CSDL của bạn
        if (Schema::hasColumn('orders', 'user_id')) {
            $q->where('user_id', $userId);
        } elseif (Schema::hasColumn('orders', 'customer_id')) {
            $q->where('customer_id', $userId);
        } else {
            $q->whereRaw('1=0'); // không có cột thì trả rỗng an toàn
        }

        if ($filters['q'] !== '') {
            $q->where(function ($sub) use ($filters) {
                $sub->where('code', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('id', intval(preg_replace('/\D/', '', $filters['q'])) ?: -1);
            });
        }
        if (!empty($filters['status']))  $q->where('status', $filters['status']);
        if (!empty($filters['payment'])) $q->where('payment_status', $filters['payment']);
        if (!empty($filters['from']))    $q->whereDate('created_at', '>=', $filters['from']);
        if (!empty($filters['to']))      $q->whereDate('created_at', '<=', $filters['to']);

        $orders = $q->orderByDesc('created_at')->paginate(12)->withQueryString();

        return view('account.orders.index', compact('orders', 'statusOptions', 'payOptions', 'filters'));
    }

    public function show(Order $order)
    {
        // Chỉ chủ đơn mới được xem
        $userId = Auth::id();
        if (Schema::hasColumn('orders', 'user_id') && $order->user_id != $userId) abort(404);
        if (Schema::hasColumn('orders', 'customer_id') && $order->customer_id != $userId) abort(404);

        $order->load(['items.product:id,slug,name,thumbnail']);

        $statusMap = [
            'pending'   => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'confirmed' => 'Đã xác nhận',
            'shipping'  => 'Đang giao',
            'delivered' => 'Đã giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'refunded'  => 'Hoàn tiền',
        ];
        $payMap = [
            'unpaid'             => 'Chưa thanh toán',
            'paid'               => 'Đã thanh toán',
            'cod'                => 'COD',
            'failed'             => 'Thất bại',
            'refunded'           => 'Hoàn tiền',
            'partially_refunded' => 'Hoàn một phần',
        ];

        // Nếu bạn có cột JSON lưu địa chỉ giao hàng
        $shipping = is_array($order->shipping_address) ? $order->shipping_address : [];
        return view('account.orders.show', compact('order', 'statusMap', 'payMap', 'shipping'));
    }
}
