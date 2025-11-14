<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\OrderRefund;
use App\Models\InventoryAdjustment;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderReturnController extends Controller
{
    public function index(Request $request)
    {
        $q = OrderReturn::with('order')->latest();

        if ($request->filled('order')) {
            $q->where('order_id', (int) $request->input('order'));
        }
        if ($request->filled('code')) {
            $code = $request->input('code');
            $q->whereHas('order', fn($qq) => $qq->where('code', 'like', "%{$code}%"));
        }

        $returns = $q->paginate(20)->appends($request->only('order', 'code'));
        return view('admin.order_returns.index', compact('returns'));
    }

    public function show(OrderReturn $return)
    {
        $return->load(['order', 'items.orderItem']);
        return view('admin.order_returns.show', compact('return'));
    }

    public function approve(Request $req, OrderReturn $return)
    {
        abort_unless(in_array($return->status, ['requested'], true), 400);

        $return->update(['status' => 'approved']);

        DB::table('order_events')->insert([
            'order_id'   => $return->order_id,
            'type'       => 'return_approved',
            'old'        => json_encode(['status' => 'requested']),
            'new'        => json_encode(['status' => 'approved', 'order_return_id' => $return->id]),
            'meta'       => json_encode(['by' => 'admin']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('ok', 'Đã duyệt yêu cầu trả hàng.');
    }

    // Kho xác nhận nhận hàng + chốt SL/tình trạng + nhập kho
    public function receive(Request $req, OrderReturn $return)
    {
        $data = $req->validate([
            'items'                 => 'required|array',
            'items.*.id'            => 'required|integer|exists:order_return_items,id',
            'items.*.approved_qty'  => 'required|integer|min:0',
            'items.*.condition'     => 'nullable|in:resell,damaged',
        ]);

        $final = 0;

        DB::transaction(function () use ($return, $data, &$final, $req) {
            $return->load('items.orderItem');

            foreach ($data['items'] as $row) {
                /** @var OrderReturnItem $rit */
                $rit      = $return->items()->findOrFail($row['id']);
                $approved = min((int) $row['approved_qty'], (int) $rit->qty);
                $cond     = $row['condition'] ?: 'resell';

                $price      = (int) round($rit->orderItem->unit_price);
                $lineRefund = $price * $approved;

                $rit->update([
                    'approved_qty' => $approved,
                    'condition'    => $cond,
                    'line_refund'  => $lineRefund,
                ]);

                // Nhập kho lại (nếu có variant)
                if ($approved > 0 && $rit->orderItem->product_variant_id) {
                    InventoryAdjustment::create([
                        'product_variant_id' => $rit->orderItem->product_variant_id,
                        'user_id'            => $req->user()->id ?? null,
                        'delta'              => $approved,
                        'reason'             => $cond === 'resell' ? 'return' : 'damaged',
                        'note'               => 'Return #' . $return->id,
                    ]);
                }

                $final += $lineRefund;
            }

            $return->update(['status' => 'received', 'final_refund' => $final]);

            DB::table('order_events')->insert([
                'order_id'   => $return->order_id,
                'type'       => 'return_received',
                'old'        => json_encode(null),
                'new'        => json_encode(['order_return_id' => $return->id, 'final' => $final]),
                'meta'       => json_encode(['by' => 'admin']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('ok', 'Đã xác nhận nhận hàng. Dự kiến hoàn: ' . number_format($final) . 'đ');
    }

    /**
     * HOÀN TIỀN ⇒ ALL TO WALLET (Ví Cosme).
     * - Ghi OrderRefund (log)
     * - Cộng ví idempotent (WalletService::creditOnce)
     * - Cập nhật trạng thái + log events
     */
    public function refund(Request $req, OrderReturn $return, WalletService $walletService)
    {
        $return->load('order');
        abort_unless(in_array($return->status, ['received', 'approved'], true), 400);

        $amount = max(0, (int) ($req->input('amount', $return->final_refund)));

        DB::transaction(function () use ($return, $amount, $walletService) {
            // 1) Log refund
            $refund = OrderRefund::create([
                'order_id'        => $return->order_id,
                'order_return_id' => $return->id,
                'provider'        => 'WALLET',
                'amount'          => $amount,
                'status'          => 'processed',
                'processed_at'    => now(),
                'meta'            => ['by' => 'admin'],
            ]);

            // 2) Cộng ví (idempotent)
            $walletTxId = null;
            if ($amount > 0 && $return->order && $return->order->user_id) {
                $tx = $walletService->creditFromOrderReturn($return);
                $walletTxId = $tx->id ?? null;

                $refund->update([
                    'meta' => array_merge($refund->meta ?? [], ['wallet_tx_id' => $walletTxId]),
                ]);
            }

            // 3) Cập nhật trạng thái phiếu
            $return->update(['status' => 'refunded']);

            // 4) (tùy) Thu hồi điểm nếu đang dùng mô hình điểm
            if ($return->order->user_id && $amount > 0) {
                DB::table('point_transactions')->insert([
                    'user_id'        => $return->order->user_id,
                    'delta'          => -$amount,
                    'type'           => 'revoke',
                    'status'         => 'confirmed',
                    'reference_type' => \App\Models\OrderReturn::class,
                    'reference_id'   => $return->id,
                    'meta'           => json_encode([
                        'order_id'   => $return->order_id,
                        'order_code' => $return->order->code,
                    ]),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            // 5) Nếu hoàn toàn bộ đơn ⇒ cập nhật Order
            if ($amount >= (int) $return->order->grand_total) {
                $old = [
                    'status'         => $return->order->status,
                    'payment_status' => $return->order->payment_status,
                ];
                $return->order->update(['status' => 'refunded', 'payment_status' => 'refunded']);

                DB::table('order_events')->insert([
                    'order_id'   => $return->order_id,
                    'type'       => 'payment_changed',
                    'old'        => json_encode(['payment_status' => $old['payment_status']]),
                    'new'        => json_encode(['payment_status' => 'refunded']),
                    'meta'       => json_encode(['by' => 'admin']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('order_events')->insert([
                    'order_id'   => $return->order_id,
                    'type'       => 'status_changed',
                    'old'        => json_encode(['status' => $old['status']]),
                    'new'        => json_encode(['status' => 'refunded']),
                    'meta'       => json_encode(['by' => 'admin']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 6) Event refund_processed
            DB::table('order_events')->insert([
                'order_id'   => $return->order_id,
                'type'       => 'refund_processed',
                'old'        => json_encode(null),
                'new'        => json_encode([
                    'order_return_id' => $return->id,
                    'order_refund_id' => $refund->id,
                    'amount'          => $amount,
                    'wallet_tx_id'    => $walletTxId,
                ]),
                'meta'       => json_encode(['by' => 'admin', 'provider' => 'WALLET']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('ok', 'Đã hoàn vào Ví Cosme ' . number_format($amount) . 'đ');
    }
}
