<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $sort = in_array($r->get('sort'), ['newest', 'total_desc', 'total_asc']) ? $r->get('sort') : 'newest';

        $base = Order::query()
            ->withCount('items')
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
                    // bỏ qua nếu người dùng nhập sai format
                }
            });

        $q = clone $base;
        if ($sort === 'total_desc') {
            $q->orderByDesc('grand_total')->orderByDesc('id');
        } elseif ($sort === 'total_asc') {
            $q->orderBy('grand_total')->orderByDesc('id');
        } else { // newest
            $q->orderByDesc('placed_at')->orderByDesc('id');
        }

        $orders = $q->paginate(12)->withQueryString();

        // tabs count
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

        // Export CSV (giống các sàn có nút Export)
        if ($r->get('export') === 'csv') {
            $rows = (clone $q)->limit(2000)->get();
            $csv  = collect();
            $csv->push(['Code', 'Customer', 'Phone', 'Payment', 'Status', 'Items', 'Total', 'Placed At']);
            foreach ($rows as $o) {
                $csv->push([
                    $o->code,
                    $o->customer_name,
                    $o->customer_phone,
                    $o->payment_status,
                    $o->status,
                    $o->items_count,
                    (string)$o->grand_total,
                    optional($o->placed_at)->format('Y-m-d H:i'),
                ]);
            }
            $out = $csv->map(fn($r) => implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $r)))->implode("\n");
            return Response::make($out, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="orders.csv"',
            ]);
        }

        return view('admin.orders.index', [
            'orders'        => $orders,
            'filters'       => $r->only('keyword', 'status', 'payment_status', 'sort'),
            'counts'        => $counts,
            'statusOptions' => Order::STATUSES,
            'payOptions'    => Order::PAY_STATUSES,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'items.variant', 'events']);
        return view('admin.orders.show', [
            'order'        => $order,
            'statusOptions' => Order::STATUSES,
            'payOptions'   => Order::PAY_STATUSES,
        ]);
    }

    // Bulk update theo “Hành động” (chọn nhiều)
    public function bulk(Request $r)
    {
        $data = $r->validate([
            'ids'            => ['required', 'array', 'min:1'],
            'action'         => ['required', 'in:set_status,set_payment'],
            'status'         => ['nullable', 'in:pending,confirmed,processing,shipping,completed,cancelled,refunded'],
            'payment_status' => ['nullable', 'in:unpaid,paid,failed,refunded'],
        ], [], [
            'ids' => 'Danh sách đơn',
        ]);

        $orders = Order::whereIn('id', $data['ids'])->get();
        $count = 0;

        foreach ($orders as $o) {
            if ($data['action'] === 'set_status' && $data['status']) {
                $old = ['status' => $o->status];
                $o->status = $data['status'];
                $o->save();
                $o->logEvent('status_changed', $old, ['status' => $o->status], ['by' => 'admin']);
                $count++;
            }
            if ($data['action'] === 'set_payment' && $data['payment_status']) {
                $old = ['payment_status' => $o->payment_status];
                $o->payment_status = $data['payment_status'];
                $o->save();
                $o->logEvent('payment_changed', $old, ['payment_status' => $o->payment_status], ['by' => 'admin']);
                $count++;
            }
        }

        return back()->with('ok', "Đã cập nhật {$count} đơn.");
    }

    // Cập nhật nhanh ở trang show (giống sàn lớn: 1 form)
    public function update(Request $r, Order $order)
    {
        $old = ['status' => $order->status, 'payment_status' => $order->payment_status];

        $data = $r->validate([
            'status'          => ['required', 'in:pending,confirmed,processing,shipping,completed,cancelled,refunded'],
            'payment_status'  => ['required', 'in:unpaid,paid,failed,refunded'],
            'tracking_no'     => ['nullable', 'string', 'max:255'],
            'shipping_method' => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string'],
        ]);

        $order->update($data);

        if ($old['status'] !== $order->status) {
            $order->logEvent('status_changed', ['status' => $old['status']], ['status' => $order->status], ['by' => 'admin']);
        }
        if ($old['payment_status'] !== $order->payment_status) {
            $order->logEvent('payment_changed', ['payment_status' => $old['payment_status']], ['payment_status' => $order->payment_status], ['by' => 'admin']);
        }
        if (!empty($data['tracking_no'])) {
            $order->logEvent('tracking_updated', null, ['tracking_no' => $data['tracking_no']], ['by' => 'admin']);
        }
        if (!empty($data['notes'])) {
            $order->logEvent('note_added', null, ['notes' => $data['notes']], ['by' => 'admin']);
        }

        return redirect()->route('admin.orders.show', $order)->with('ok', 'Đã cập nhật đơn hàng.');
    }
}
