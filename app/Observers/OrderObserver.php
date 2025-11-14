<?php

namespace App\Observers;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use App\Models\Product;
use App\Services\UserCouponService;
use App\Services\Loyalty\LoyaltyOrchestrator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Events\OrderCompleted;
use App\Events\OrderConfirmed;

class OrderObserver
{
    public function __construct(private LoyaltyOrchestrator $loyalty) {}

    /** Đã đủ điều kiện coi như đã thanh toán (để tính sold/hoàn tất)? */
    private function paidEnough(Order $o): bool
    {
        // online đã trả tiền
        if (in_array($o->payment_status, ['paid'], true)) return true;

        // COD: coi là "đã thanh toán" khi đã giao/hoàn tất
        if ($o->payment_method === 'COD' && in_array($o->status, ['delivered', 'completed'], true)) return true;

        // đôi khi hệ thống đánh dấu 'cod' trong payment_status
        if ($o->payment_status === 'cod') return true;

        return false;
    }

    /** Đơn có được tính vào "đã bán" không (đã trả tiền & không bị huỷ/hoàn)? */
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

    /** Gói dữ liệu gửi mail hoá đơn */
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

    /** Khi đơn vừa tạo: nếu đã countable thì cộng sold & tiêu mã luôn */
    public function created(Order $order): void
    {
        if ($this->countable($order)) {
            $this->adjustSoldCount($order, +1);
            UserCouponService::ensureConsumed($order);
        }
    }

    /** Khi đơn cập nhật: xử lý chuyển trạng thái, mail, tiêu/hoàn mã, loyalty */
    public function updated(Order $order): void
    {
        // Lấy giá trị trước-sau để bắt chuyển tiếp
        $oldPayStatus = (string) $order->getOriginal('payment_status');
        $newPayStatus = (string) $order->payment_status;

        $oldStatus = (string) $order->getOriginal('status');
        $newStatus = (string) $order->status;

        $oldPayMethod = (string) ($order->getOriginal('payment_method') ?: $order->payment_method);

        // A) SOLD COUNT: nếu tính được/không được trước-sau thì cộng/trừ
        $wasCountable = (
            in_array($oldPayStatus, ['paid', 'cod'], true)
            || ($oldPayMethod === 'COD' && in_array($oldStatus, ['delivered', 'completed'], true))
        ) && !in_array($oldStatus, ['cancelled', 'refunded'], true);

        $nowCountable = $this->countable($order);

        if ($wasCountable !== $nowCountable) {
            $this->adjustSoldCount($order, $nowCountable ? +1 : -1);
        }

        // B) TIÊU MÃ: khi vừa thanh toán xong hoặc COD chuyển sang delivered/completed
        $justPaid     = ($newPayStatus === 'paid' && $oldPayStatus !== 'paid');
        $justCODMark  = ($newPayStatus === 'cod'  && $oldPayStatus !== 'cod');
        $codCompleted = ($order->payment_method === 'COD'
            && !in_array($oldStatus, ['delivered', 'completed'], true)
            && in_array($newStatus, ['delivered', 'completed'], true));

        if ($justPaid || $justCODMark || $codCompleted) {
            UserCouponService::ensureConsumed($order);
        }

        // C) MAIL HOÁ ĐƠN: ngay khi vừa "paid"
        if ($justPaid) {
            $to = $order->customer_email ?: optional($order->user)->email;
            if ($to) {
                // dùng queue nếu mailable implements ShouldQueue; send() nếu không
                Mail::to($to)->send(new OrderInvoiceMail($this->buildInvoicePayload($order), true));
            }
        }

        // F) GENERATE QR CODES: khi order được confirmed hoặc processing (TRƯỚC khi đóng gói)
        $justConfirmed = (
            !in_array($oldStatus, ['confirmed', 'processing'], true)
            && in_array($newStatus, ['confirmed', 'processing'], true)
        );

        if ($justConfirmed) {
            // Chỉ generate nếu đã thanh toán (để đảm bảo order sẽ được xử lý)
            if ($this->paidEnough($order) || $newPayStatus === 'paid') {
                event(new OrderConfirmed($order->fresh()));
            }
        }

        // D) LOYALTY: gọi orchestrator khi vừa hoàn tất (completed/delivered) & đủ điều kiện thanh toán
        $completedNow = (
            !in_array($oldStatus, ['completed', 'delivered'], true)
            && in_array($newStatus, ['completed', 'delivered'], true)
            && $this->paidEnough($order)
        );

        if ($completedNow) {
            // dùng fresh() để tránh race condition khi listener khác cũng update
            $this->loyalty->onOrderCompleted($order->fresh());
            event(new OrderCompleted($order->fresh()));
        }

        // E) HOÀN/CANCEL: hoàn mã; nếu orchestrator có hàm revert thì gọi thêm
        $becameRefundOrCancelled = (
            (!in_array($oldStatus, ['cancelled', 'refunded'], true) && in_array($newStatus, ['cancelled', 'refunded'], true))
            || ($oldPayStatus !== 'refunded' && $newPayStatus === 'refunded')
        );

        if ($becameRefundOrCancelled) {
            UserCouponService::restoreCodesForOrder($order);

            if (method_exists($this->loyalty, 'onOrderCancelledOrRefunded')) {
                $this->loyalty->onOrderCancelledOrRefunded($order->fresh());
            }
        }
    }
}
