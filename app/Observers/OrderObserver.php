<?php

namespace App\Observers;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Models\PointTransaction;
use App\Models\UserPoint;
use App\Services\PointsService;
use App\Services\UserCouponService;

class OrderObserver
{
    /** Đã thanh toán đủ để tính sold/tiêu mã? */
    private function paidEnough(Order $o): bool
    {
        // Online đã trả tiền hoặc hệ thống đánh dấu 'cod' (tùy DB của bạn)
        if (in_array($o->payment_status, ['paid', 'cod'], true)) return true;

        // Với COD, nhiều flow thực tế chỉ đổi status khi giao xong
        if ($o->payment_method === 'COD' && in_array($o->status, ['delivered', 'completed'], true)) return true;

        return false;
    }

    /** Có tính vào “đã bán” không (đã thanh toán & không bị hủy/hoàn)? */
    private function countable(Order $o): bool
    {
        return $this->paidEnough($o) && !in_array($o->status, ['cancelled', 'refunded'], true);
    }

    /** Cộng/trừ sold_count cho các product theo sign (+1 hoặc -1) */
    private function adjustSoldCount(Order $order, int $sign): void
    {
        $rows = $order->items()
            ->select('product_id', DB::raw('SUM(qty) as qty_sum'))
            ->groupBy('product_id')
            ->get();

        foreach ($rows as $r) {
            $q = (int) $r->qty_sum;
            if ($q <= 0 || !$r->product_id) continue;

            Product::whereKey($r->product_id)->update([
                'sold_count' => $sign > 0
                    ? DB::raw('sold_count + ' . $q)
                    : DB::raw('GREATEST(sold_count - ' . $q . ', 0)'),
            ]);
        }
    }

    /** Nếu đơn tạo ra đã đủ điều kiện -> cộng sold & TIÊU MÃ ngay */
    public function created(Order $order): void
    {
        if ($this->countable($order)) {
            $this->adjustSoldCount($order, +1);
            UserCouponService::ensureConsumed($order); // QUAN TRỌNG
        }
    }

    public function updated(Order $order): void
    {
        // ===== A) SOLD COUNT: so sánh trước/sau để cộng hoặc trừ =====
        $oldPayStat = (string) $order->getOriginal('payment_status');
        $newPayStat = (string) $order->payment_status;

        $oldStatus  = (string) $order->getOriginal('status');
        $newStatus  = (string) $order->status;

        $oldPayMeth = (string) $order->getOriginal('payment_method') ?: (string) $order->payment_method;

        $was = (
            in_array($oldPayStat, ['paid', 'cod'], true)
            || ($oldPayMeth === 'COD' && in_array($oldStatus, ['delivered', 'completed'], true))
        ) && !in_array($oldStatus, ['cancelled', 'refunded'], true);

        $now = $this->countable($order);

        if ($was !== $now) {
            $this->adjustSoldCount($order, $now ? +1 : -1);
        }

        // ===== B) TRIGGER TIÊU MÃ: 3 cửa an toàn (idempotent) =====
        $justPaid     = ($newPayStat === 'paid' && $oldPayStat !== 'paid');
        $justCODMark  = ($newPayStat === 'cod'  && $oldPayStat !== 'cod');
        $codCompleted = ($order->payment_method === 'COD'
            && !in_array($oldStatus, ['delivered', 'completed'], true)
            && in_array($newStatus, ['delivered', 'completed'], true));

        if ($justPaid || $justCODMark || $codCompleted) {
            UserCouponService::ensureConsumed($order);
        }

        // ===== C) MAIL + ĐIỂM: giữ logic "khi vừa chuyển sang paid" của bạn =====
        if ($justPaid) {
            // Mail
            $to = $order->customer_email ?: optional($order->user)->email;
            if ($to) {
                $payload = $this->buildInvoicePayload($order);
                Mail::to($to)->send(new OrderInvoiceMail($payload, true));
            }

            // Điểm: treo pending
            if ($order->user_id) {
                $shipping = (int) ($order->shipping_fee ?? 0);
                $grand    = (int) ($order->grand_total ?? 0);
                $eligible = max(0, $grand - $shipping);
                $points   = intdiv($eligible, 1000);

                $alreadyPending = PointTransaction::where('user_id', $order->user_id)
                    ->where('status', 'pending')
                    ->where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->exists();

                if ($points > 0 && !$alreadyPending) {
                    $availableAt = now()->addDays(3);
                    $expiresAt   = (clone $availableAt)->addDays(365);
                    PointsService::earnPending(
                        $order->user_id,
                        $points,
                        $availableAt,
                        $expiresAt,
                        $order,
                        ['order_code' => $order->code, 'eligible_vnd' => $eligible]
                    );
                }
            }
        }

        // ===== D) completed → confirm điểm (như bạn đang có) =====
        $beforeStatus = $oldStatus;
        $afterStatus  = $newStatus;
        if ($beforeStatus !== 'completed' && $afterStatus === 'completed' && $order->user_id) {
            $pending = PointTransaction::where('user_id', $order->user_id)
                ->where('status', 'pending')
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->get();

            if ($pending->count()) {
                foreach ($pending as $tx) PointsService::confirm($tx);
            } else {
                $shipping = (int) ($order->shipping_fee ?? 0);
                $grand    = (int) ($order->grand_total ?? 0);
                $eligible = max(0, $grand - $shipping);
                $points   = intdiv($eligible, 1000);

                $alreadyConfirmed = PointTransaction::where('user_id', $order->user_id)
                    ->where('type', 'earn')->where('status', 'confirmed')
                    ->where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->exists();

                if ($points > 0 && !$alreadyConfirmed) {
                    DB::transaction(function () use ($order, $points, $eligible) {
                        PointTransaction::create([
                            'user_id'        => $order->user_id,
                            'delta'          => $points,
                            'type'           => 'earn',
                            'status'         => 'confirmed',
                            'reference_type' => Order::class,
                            'reference_id'   => $order->id,
                            'meta'           => ['order_code' => $order->code, 'eligible_vnd' => $eligible],
                        ]);

                        UserPoint::query()->updateOrCreate(
                            ['user_id' => $order->user_id],
                            ['balance' => DB::raw('balance + ' . (int)$points)]
                        );
                    });
                }
            }
        }

        // ===== E) refund/cancel → hoàn mã + điểm (giữ nguyên) =====
        $isRefundedByPayment = in_array($order->payment_status, ['refunded'], true);
        $isCancelledOrRefund = in_array($order->status, ['cancelled', 'refunded'], true);

        if ($isRefundedByPayment || $isCancelledOrRefund) {
            UserCouponService::restoreCodesForOrder($order);

            PointTransaction::where('user_id', $order->user_id)
                ->where('status', 'pending')
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->update(['status' => 'cancelled']);

            $earned = PointTransaction::where('user_id', $order->user_id)
                ->where('type', 'earn')->where('status', 'confirmed')
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->sum('delta');

            if ($earned > 0) {
                DB::transaction(function () use ($order, $earned) {
                    PointTransaction::create([
                        'user_id'        => $order->user_id,
                        'delta'          => -$earned,
                        'type'           => 'adjust',
                        'status'         => 'confirmed',
                        'reference_type' => Order::class,
                        'reference_id'   => $order->id,
                        'meta'           => ['reason' => 'refund/cancel'],
                    ]);

                    UserPoint::query()
                        ->where('user_id', $order->user_id)
                        ->update(['balance' => DB::raw('GREATEST(balance - ' . (int)$earned . ', 0)')]);
                });
            }
        }
    }

    private function buildInvoicePayload(Order $order): array
    {
        $addr = $order->shipping_address;
        if (is_array($addr)) {
            $addrStr = implode(', ', array_filter([
                Arr::get($addr, 'line1'),
                Arr::get($addr, 'district'),
                Arr::get($addr, 'city'),
            ]));
        } elseif (is_string($addr)) {
            $addrStr = $addr;
        } else {
            $addrStr = '';
        }

        $items = $order->items()->get()->map(function ($it) {
            $name = trim(($it->product_name_snapshot ?? '') . ' ' . ($it->variant_name_snapshot ?? ''));
            if ($name === '') $name = 'SP #' . ($it->product_id ?? '');
            return [
                'name'  => $name,
                'qty'   => (int) ($it->qty ?? 0),
                'price' => (int) ($it->unit_price ?? 0),
            ];
        })->toArray();

        return [
            'code'           => $order->code,
            'created_at'     => optional($order->placed_at ?: $order->created_at)->toDateTimeString(),
            'payment_method' => (string) $order->payment_method,
            'customer'       => [
                'name'  => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
                'addr'  => $addrStr,
                'note'  => $order->notes,
            ],
            'cart'     => $items,
            'subtotal' => (int) $order->subtotal,
            'shipping' => (int) $order->shipping_fee,
            'discount' => (int) $order->discount_total,
            'total'    => (int) $order->grand_total,
        ];
    }
}
