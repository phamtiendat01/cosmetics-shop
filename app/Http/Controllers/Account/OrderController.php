<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryAdjustment;

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
    public function cancel(Request $request, Order $order)
    {
        // Chỉ chủ đơn mới được huỷ
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Bạn không có quyền thao tác với đơn này.');
        }

        // Chỉ cho huỷ ở pending/confirmed/processing
        if (! in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
            return back()->withErrors('Trạng thái hiện tại không cho phép huỷ.');
        }

        // Bước dễ: chặn nếu đã thanh toán online (không phải COD)
        $isPaidOnline = ($order->payment_status === 'paid') && (strtoupper($order->payment_method ?? '') !== 'COD');
        if ($isPaidOnline) {
            return back()->withErrors('Đơn đã thanh toán online, vui lòng liên hệ CSKH để hỗ trợ hoàn tiền.');
        }

        DB::transaction(function () use ($order, $request) {
            $oldStatus = $order->status;

            // Cập nhật trạng thái & thanh toán
            $order->status = 'cancelled';
            if ($order->payment_status !== 'paid') {
                $order->payment_status = 'unpaid';
            }
            $order->cancelled_at = now();
            $order->save();

            // Trả kho theo từng item (nếu có variant)
            foreach ($order->items as $it) {
                if ($it->product_variant_id) {
                    InventoryAdjustment::create([
                        'product_variant_id' => $it->product_variant_id,
                        'user_id'            => $request->user()->id ?? null,
                        'delta'              => (int) $it->qty,
                        'reason'             => 'cancel',
                        'note'               => 'Customer cancel order',
                    ]);
                }
            }

            // Gỡ coupon/voucher khỏi đơn để khách có thể dùng lại
            DB::table('coupon_usages')->where('order_id', $order->id)->update(['order_id' => null]);
            DB::table('shipping_voucher_usages')->where('order_id', $order->id)->update(['order_id' => null]);

            // Ghi timeline
            DB::table('order_events')->insert([
                'order_id'   => $order->id,
                'type'       => 'status_changed',
                'old'        => json_encode(['status' => $oldStatus]),
                'new'        => json_encode(['status' => 'cancelled']),
                'meta'       => json_encode(['by' => 'customer']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('account.orders.show', ['order' => $order->id])
            ->with('ok', 'Đã huỷ đơn thành công.');
    }
}
