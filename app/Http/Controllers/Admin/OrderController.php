<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryAdjustment;


class OrderController extends Controller
{
    public function index(Request $r)
    {
        $sort = in_array($r->get('sort'), ['newest', 'total_desc', 'total_asc']) ? $r->get('sort') : 'newest';

        $base = Order::query()
            ->withCount([
                'items',
                // đếm số yêu cầu trả hàng đang chờ duyệt cho từng đơn
                'returns as pending_returns_count' => function ($q) {
                    $q->where('status', 'requested');
                },
            ])
            ->keyword($r->keyword)
            ->status($r->status)
            ->payStatus($r->payment_status)
            ->when($r->province_code, fn($q) => $q->where('shipping_address->province_code', $r->province_code))
            ->when($r->district_code, fn($q) => $q->where('shipping_address->district_code', $r->district_code))
            ->when($r->ward_code,     fn($q) => $q->where('shipping_address->ward_code',     $r->ward_code))
            ->when($r->date_range, function ($qq) use ($r) {
                [$a, $b] = array_pad(preg_split('/\s*to\s*|\s*-\s*/', $r->date_range), 2, null);
                try {
                    $from = $a ? Carbon::createFromFormat('d/m/Y', trim($a))->startOfDay() : null;
                    $to   = $b ? Carbon::createFromFormat('d/m/Y', trim($b))->endOfDay()   : null;
                    $qq->when($from, fn($q) => $q->where('placed_at', '>=', $from))
                        ->when($to,   fn($q) => $q->where('placed_at', '<=', $to));
                } catch (\Throwable $e) {
                }
            });

        $q = clone $base;
        if ($sort === 'total_desc') {
            $q->orderByDesc('grand_total')->orderByDesc('id');
        } elseif ($sort === 'total_asc') {
            $q->orderBy('grand_total')->orderByDesc('id');
        } else {
            $q->orderByDesc('placed_at')->orderByDesc('id');
        }

        $orders = $q->paginate(12)->withQueryString();

        $counts = [
            'all'       => (clone $base)->count(),
            'pending'   => (clone $base)->where('status', 'pending')->count(),
            'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
            'processing' => (clone $base)->where('status', 'processing')->count(),
            'shipping'  => (clone $base)->where('status', 'shipping')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'refunded'  => (clone $base)->where('status', 'refunded')->count(),
        ];

        if ($r->get('export') === 'csv') {
            $rows = (clone $q)->limit(2000)->get();
            // ... phần export giữ nguyên
        }

        return view('admin.orders.index', [
            'orders'        => $orders,
            'filters'       => $r->only('keyword', 'status', 'payment_status', 'sort'),
            'counts'        => $counts,
            'statusOptions' => Order::STATUSES,
            'payOptions'    => Order::PAY_STATUSES,
        ]);
    }

    public function show(\App\Models\Order $order)
    {
        // ✅ Nạp các quan hệ cần thiết để view dùng được
        $order->loadMissing([
            'items.product:id,slug,name,thumbnail,image',
            'items.variant:id,product_id,name,sku',
            'items.review:id,order_item_id,rating,content,verified_purchase,created_at',
            'items.qrCodes', // Load QR codes cho tất cả items
            'events',
            'user',
        ]);

        // ✅ Tự động generate QR codes nếu order đã ở trạng thái phù hợp nhưng chưa có QR codes
        // Chỉ generate khi order ở trạng thái confirmed/processing/completed/delivered VÀ đã thanh toán
        $hasQRCodes = \App\Models\ProductQRCode::whereHas('orderItem', function($q) use ($order) {
            $q->where('order_id', $order->id);
        })->exists();

        $isEligibleStatus = in_array($order->status, ['confirmed', 'processing', 'completed', 'delivered'], true);
        $isPaid = $order->payment_status === 'paid' || $order->payment_method === 'COD';
        $shouldHaveQRCodes = $isEligibleStatus && $isPaid;

        if (!$hasQRCodes && $shouldHaveQRCodes && $order->items->isNotEmpty()) {
            // Generate QR codes đồng bộ (không dùng event để đảm bảo tạo ngay)
            try {
                $listener = app(\App\Listeners\GenerateProductQRCodes::class);
                $listener->handle(new \App\Events\OrderConfirmed($order->fresh()));
                
                // Reload order để có QR codes mới nhất
                $order->load('items.qrCodes');
            } catch (\Exception $e) {
                // Log error nhưng không block page load
                \Illuminate\Support\Facades\Log::warning('Failed to auto-generate QR codes', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('admin.orders.show', [
            'order'         => $order,
            'statusOptions' => \App\Models\Order::STATUSES,
            'payOptions'    => \App\Models\Order::PAY_STATUSES,
        ]);
    }

    /**
     * Print QR codes for order (để in và gắn vào sản phẩm)
     */
    public function printQRCodes(Order $order)
    {
        $order->loadMissing(['items.variant.product', 'items.qrCodes']);
        
        $qrCodes = \App\Models\ProductQRCode::whereHas('orderItem', function($q) use ($order) {
            $q->where('order_id', $order->id);
        })->with(['orderItem', 'productVariant.product'])->get();

        return view('admin.orders.print-qr-codes', [
            'order' => $order,
            'qrCodes' => $qrCodes,
        ]);
    }

    // Bulk update theo “Hành động” (chọn nhiều)
    // app/Http/Controllers/Admin/OrderController.php

    public function bulk(Request $r)
    {
        $ids    = array_filter((array) $r->input('ids', []));
        $action = $r->input('action');

        if (!$ids || !in_array($action, ['set_status', 'set_payment'], true)) {
            return back()->withErrors('Vui lòng chọn đơn và hành động hợp lệ.');
        }

        // nạp tất cả đơn, rồi xử lý từng cái bằng save() để kích hoạt Observer
        $orders = \App\Models\Order::whereIn('id', $ids)->get();

        $affected = 0;

        if ($action === 'set_status') {
            $newStatus = $r->input('status');
            if (!array_key_exists($newStatus, \App\Models\Order::STATUSES)) {
                return back()->withErrors('Trạng thái đơn không hợp lệ.');
            }
            foreach ($orders as $order) {
                $old = $order->getOriginal();
                if ($order->status !== $newStatus) {
                    $order->status = $newStatus;
                    $order->save(); // => Observer updated (nếu cần)
                    $order->logEvent('status_changed', ['status' => $old['status']], ['status' => $order->status], ['by' => 'admin']);
                    $affected++;
                }
            }
        }

        if ($action === 'set_payment') {
            $newPay = $r->input('payment_status');
            if (!array_key_exists($newPay, \App\Models\Order::PAY_STATUSES)) {
                return back()->withErrors('Trạng thái thanh toán không hợp lệ.');
            }
            foreach ($orders as $order) {
                $before = $order->getOriginal('payment_status');
                if ($order->payment_status !== $newPay) {
                    $order->payment_status = $newPay;
                    $order->save(); // => Observer updated (nếu đổi sang 'paid' sẽ gửi mail)
                    $order->logEvent('payment_changed', ['payment_status' => $before], ['payment_status' => $order->payment_status], ['by' => 'admin']);
                    $affected++;
                }
            }
        }

        return back()->with('ok', "Đã áp dụng cho {$affected} đơn.");
    }


    // Cập nhật nhanh ở trang show (giống sàn lớn: 1 form)
    // app/Http/Controllers/Admin/OrderController.php

    public function update(Request $r, Order $admin_order)
    {
        $order = $admin_order;

        $data = $r->only(['status', 'payment_status', 'tracking_no', 'notes']);

        if (isset($data['status']) && !array_key_exists($data['status'], Order::STATUSES)) {
            return back()->withErrors('Trạng thái đơn không hợp lệ.');
        }
        if (isset($data['payment_status']) && !array_key_exists($data['payment_status'], Order::PAY_STATUSES)) {
            return back()->withErrors('Trạng thái thanh toán không hợp lệ.');
        }

        $old = $order->getOriginal();

        if (isset($data['status']) && $order->status !== $data['status']) {
            $order->status = $data['status'];
        }
        if (isset($data['payment_status']) && $order->payment_status !== $data['payment_status']) {
            $order->payment_status = $data['payment_status'];
        }
        if (isset($data['tracking_no'])) {
            $order->tracking_no = trim((string) $data['tracking_no']) ?: null;
        }
        if (isset($data['notes'])) {
            $order->notes = trim((string) $data['notes']) ?: null;
        }

        $order->save();

        if (($old['status'] ?? null) !== $order->status) {
            $order->logEvent('status_changed', ['status' => $old['status']], ['status' => $order->status], ['by' => 'admin']);
        }
        if (($old['payment_status'] ?? null) !== $order->payment_status) {
            $order->logEvent('payment_changed', ['payment_status' => $old['payment_status']], ['payment_status' => $order->payment_status], ['by' => 'admin']);
        }
        if (($old['tracking_no'] ?? null) !== $order->tracking_no) {
            $order->logEvent('tracking_updated', null, ['tracking_no' => $order->tracking_no], ['by' => 'admin']);
        }
        if (($old['notes'] ?? null) !== $order->notes) {
            $order->logEvent('note_added', null, ['notes' => $order->notes], ['by' => 'admin']);
        }

        // Redirect đúng param 'admin_order' theo ID
        return redirect()->route('admin.orders.show', ['admin_order' => $order->id])
            ->with('ok', 'Đã cập nhật đơn hàng.');
    }
    public function cancel(Request $request, Order $admin_order)
    {
        // Route model binding dùng {admin_order} nên tham số phải cùng tên
        $order = $admin_order;

        // 1) Chỉ cho huỷ khi đơn còn ở pending/confirmed/processing
        if (! in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
            return back()->withErrors('Trạng thái hiện tại không cho phép huỷ.');
        }

        // 2) “Bước dễ”: chỉ huỷ cho COD/chưa thanh toán (chặn nếu đã PAID online)
        $isPaidOnline = ($order->payment_status === 'paid') && (strtoupper($order->payment_method) !== 'COD');
        if ($isPaidOnline) {
            return back()->withErrors('Đơn đã thanh toán online. Vui lòng dùng luồng hoàn tiền (bước tiếp theo).');
        }

        DB::transaction(function () use ($order, $request) {
            $old = $order->getOriginal(); // lấy snapshot để log event

            // 3) Cập nhật trạng thái đơn
            $order->status = 'cancelled';
            // Nếu là COD hoặc chưa thanh toán, payment_status để về unpaid cho rõ ràng
            if ($order->payment_status !== 'paid') {
                $order->payment_status = 'unpaid';
            }
            $order->save();

            // 4) Trả kho: mỗi item cộng lại tồn cho variant (nếu có)
            foreach ($order->items as $it) {
                if (!empty($it->product_variant_id)) {
                    InventoryAdjustment::create([
                        'product_variant_id' => $it->product_variant_id,
                        'user_id'            => $request->user()->id ?? null,
                        'delta'              => (int) $it->qty,
                        'reason'             => 'cancel',
                        'note'               => 'Auto restock on order cancel',
                    ]);
                }
            }

            // 5) Gỡ coupon/ship voucher gắn với order (để khách có thể dùng lại)
            DB::table('coupon_usages')->where('order_id', $order->id)->update(['order_id' => null]);
            DB::table('shipping_voucher_usages')->where('order_id', $order->id)->update(['order_id' => null]);

            // 6) Ghi timeline
            $order->logEvent(
                'status_changed',
                ['status' => $old['status'] ?? null],
                ['status' => $order->status],
                ['by' => 'admin']
            );
        });

        // Quay lại trang chi tiết đúng tên tham số 'admin_order'
        return redirect()->route('admin.orders.show', ['admin_order' => $order->id])
            ->with('ok', 'Đã huỷ đơn thành công (COD/chưa thanh toán).');
    }
}
