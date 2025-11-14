<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReturnController extends Controller
{
    public function create(Request $req, Order $order)
    {
        abort_if($order->user_id !== $req->user()->id, 403);

        // Cho phép tạo yêu cầu khi đang giao / đã hoàn tất (cover alias)
        $okStatus = in_array(Str::snake($order->status), ['shipping', 'completed', 'dang_giao', 'hoan_tat'], true);

        $days          = (int) config('orders.return_window_days', 14);
        $withinWindow  = $this->withinWindow($order, $days);

        if (!$okStatus || !$withinWindow) {
            return redirect()
                ->route('account.orders.show', $order)
                ->withErrors("Đơn đã quá hạn {$days} ngày kể từ khi hoàn tất (hoặc trạng thái không hỗ trợ). Không thể tạo yêu cầu trả hàng.");
        }

        return view('account.returns.create', compact('order'));
    }

    public function store(Request $req, Order $order)
    {
        abort_if($order->user_id !== $req->user()->id, 403);

        $days         = (int) config('orders.return_window_days', 14);
        $okStatus     = in_array(Str::snake($order->status), ['shipping', 'completed', 'dang_giao', 'hoan_tat'], true);
        $withinWindow = $this->withinWindow($order, $days);

        if (!$okStatus || !$withinWindow) {
            return back()
                ->withErrors("Đơn đã quá hạn {$days} ngày kể từ khi hoàn tất. Không thể gửi yêu cầu.")
                ->withInput();
        }

        // Form có thể nhập 0 → validate rồi lọc > 0 ở dưới
        $data = $req->validate([
            'reason'                => 'nullable|string|max:255',
            'items'                 => 'required|array',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.qty'           => 'required|integer|min:0',
        ]);

        // ✅ Ép phương thức hoàn tiền về ví Cosme (bỏ mọi input khác)
        $refundMethod = 'wallet';

        // Lọc chỉ giữ dòng có qty > 0
        $selected = array_values(array_filter($data['items'] ?? [], fn($r) => (int)($r['qty'] ?? 0) > 0));
        if (empty($selected)) {
            return back()->withErrors('Vui lòng chọn số lượng cần trả (> 0).')->withInput();
        }

        try {
            DB::transaction(function () use ($order, $data, $selected, $req, $refundMethod) {
                // Tạo phiếu yêu cầu
                $ret = OrderReturn::create([
                    'order_id'        => $order->id,
                    'user_id'         => $req->user()->id,
                    'status'          => 'requested',
                    'reason'          => $data['reason'] ?? null,
                    'refund_method'   => $refundMethod, // luôn là wallet
                    'expected_refund' => 0,
                ]);

                $expected = 0;

                foreach ($selected as $row) {
                    /** @var OrderItem $it */
                    // dùng relation để đảm bảo item thuộc đúng đơn
                    $it  = $order->items()->findOrFail($row['order_item_id']);
                    $qty = min((int) $row['qty'], (int) $it->qty); // không vượt quá đã mua
                    if ($qty <= 0) {
                        continue;
                    }

                    $unit       = (int) ($it->unit_price ?? 0);
                    $lineRefund = (int) round($unit * $qty);

                    OrderReturnItem::create([
                        'order_return_id' => $ret->id,
                        'order_item_id'   => $it->id,
                        'qty'             => $qty,
                        'line_refund'     => $lineRefund,
                    ]);

                    $expected += $lineRefund;
                }

                if ($expected <= 0) {
                    // Rollback bằng cách ném exception
                    throw new \RuntimeException('Không có dòng hợp lệ để trả.');
                }

                $ret->update(['expected_refund' => $expected]);

                // Timeline
                DB::table('order_events')->insert([
                    'order_id'   => $order->id,
                    'type'       => 'return_requested',
                    'old'        => json_encode(null),
                    'new'        => json_encode([
                        'order_return_id' => $ret->id,
                        'expected'        => $expected,
                        'refund_method'   => $refundMethod,
                    ]),
                    'meta'       => json_encode(['by' => 'customer']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage() ?: 'Không thể tạo yêu cầu trả hàng.')->withInput();
        }

        return redirect()
            ->route('account.orders.show', $order)
            ->with('ok', 'Đã gửi yêu cầu trả hàng (hoàn về Ví Cosme).');
    }

    /* ----------------- Helpers ----------------- */

    private function withinWindow(Order $order, int $days): bool
    {
        if (method_exists($order, 'isReturnWindowOpen')) {
            return (bool) $order->isReturnWindowOpen($days);
        }
        $completedAt = $this->completedAtFromEvents($order);
        return $completedAt ? now()->lte($completedAt->copy()->addDays($days)) : false;
    }

    private function completedAtFromEvents(Order $order): ?Carbon
    {
        if (!empty($order->completed_at)) {
            try {
                return Carbon::parse($order->completed_at);
            } catch (\Throwable $e) {
            }
        }

        $events = $order->relationLoaded('events') ? $order->events : $order->events()->get();
        foreach ($events->sortByDesc('created_at') as $ev) {
            if (($ev->type ?? '') !== 'status_changed') continue;
            $new   = data_get($ev, 'new.status');
            $canon = Str::snake((string) $new);
            if (in_array($canon, ['completed', 'hoan_tat'], true)) {
                return Carbon::parse($ev->created_at);
            }
        }
        return null;
    }
}
