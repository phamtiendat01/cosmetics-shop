<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Payments\PayOSService;

class PayOSCheckoutController extends Controller
{
    /**
     * Tạo payment link & chuyển thẳng sang trang payOS (không dùng blade nội bộ).
     */
    public function showQR(Order $order, PayOSService $payos)
    {
        if (strtoupper((string) $order->payment_method) !== 'VIETQR') {
            abort(400, 'Phương thức không hợp lệ');
        }

        // Nếu đơn đã trả rồi thì về chi tiết đơn luôn
        if (Str::lower((string) $order->payment_status) === 'paid') {
            return redirect()->route('account.orders.show', $order->id);
        }

        // 1) Thử tái sử dụng link đang pending
        $op = $order->payments()
            ->where('method_code', 'VIETQR')
            ->where('status', 'pending')
            ->latest('id')->first();

        $meta = $this->toArraySafe($op?->meta);
        $checkoutUrl = $meta['checkoutUrl'] ?? null;

        // 2) Nếu chưa có => tạo link mới
        if (!$checkoutUrl) {
            $orderCode = (int) ($order->id . substr((string) time(), -5));
            $amount    = (int) round((float) $order->grand_total);
            $desc      = 'DH ' . mb_substr((string) ($order->code ?? $order->id), 0, 20);

            $res = $payos->createLink([
                'orderCode'   => $orderCode,
                'amount'      => $amount,
                'description' => $desc, // ≤25 ký tự
                'returnUrl'   => env('PAYOS_RETURN_URL'),
                'cancelUrl'   => env('PAYOS_CANCEL_URL'),
            ]);

            $checkoutUrl = $res['checkoutUrl'] ?? null;

            // Lưu payment record
            $op = OrderPayment::create([
                'order_id'     => $order->id,
                'method_code'  => 'VIETQR',
                'amount'       => $amount,
                'currency'     => 'VND',
                'provider_ref' => $res['paymentLinkId'] ?? null,
                'status'       => 'pending',
                'meta'         => $res, // chứa orderCode/checkoutUrl/qrCode...
            ]);
        }

        if (!$checkoutUrl) {
            return redirect()->route('account.orders.show', $order->id)
                ->with('error', 'Không tạo được link thanh toán.');
        }

        // 3) Chuyển thẳng sang trang thanh toán payOS
        return redirect()->away($checkoutUrl);
    }

    /**
     * API nhỏ cho FE polling (nếu bạn còn dùng).
     */
    public function status(Order $order)
    {
        $op = $order->payments()->where('method_code', 'VIETQR')->latest('id')->first();
        $isPaid = $order->payment_status === 'paid' || ($op && $op->status === 'paid');

        return response()->json([
            'status'         => $isPaid ? 'paid' : 'pending',
            'payment_status' => $order->payment_status,
            'op_status'      => $op?->status,
            'redirect'       => route('account.orders.show', $order->id),
        ]);
    }

    /**
     * Return từ payOS: đọc ?status=PAID&orderCode=...&id=paymentLinkId
     * → xác nhận & set paid rồi đưa về trang chi tiết đơn.
     */
    public function return(Request $r)
    {
        $status    = strtoupper((string) $r->query('status'));
        $plinkId   = $r->query('id');         // paymentLinkId
        $orderCode = $r->query('orderCode');  // số orderCode trên payOS

        // Tìm OrderPayment tương ứng
        $op = $this->findPayosPayment($plinkId, $orderCode);
        if (!$op) {
            return redirect()->route('account.orders.index')
                ->with('error', 'Không tìm thấy giao dịch thanh toán payOS.');
        }

        $order = $op->order;

        // Nếu thành công → cập nhật paid
        if ($status === 'PAID') {
            $this->markPaid($order, $op, [
                'return_query' => $r->query(),
                'confirmed_by' => 'payos_return',
            ]);
        } else {
            // Không paid: giữ nguyên pending (hoặc bạn muốn set 'failed' tuỳ policy)
            $op->meta = array_merge($this->toArraySafe($op->meta), [
                'return_query' => $r->query(),
            ]);
            $op->save();
        }

        return redirect()->route('account.orders.show', $order->id);
    }

    /**
     * Cancel từ payOS → quay về chi tiết đơn, không đổi trạng thái thanh toán.
     */
    public function cancel(Request $r)
    {
        $plinkId   = $r->query('id');
        $orderCode = $r->query('orderCode');

        $op = $this->findPayosPayment($plinkId, $orderCode);
        return $op
            ? redirect()->route('account.orders.show', $op->order_id)
            : redirect()->route('account.orders.index');
    }

    /* ================== Helpers ================== */

    private function toArraySafe($meta): array
    {
        if (is_array($meta))   return $meta;
        if (is_object($meta))  return (array) $meta;
        if (is_string($meta) && $meta !== '') {
            $m = json_decode($meta, true);
            if (json_last_error() === JSON_ERROR_NONE) return (array) $m;
        }
        return [];
    }

    /**
     * Tìm OrderPayment theo paymentLinkId hoặc orderCode trong meta.
     */
    private function findPayosPayment(?string $plinkId, ?string $orderCode): ?OrderPayment
    {
        // 1) Ưu tiên theo provider_ref = paymentLinkId
        if ($plinkId) {
            $op = OrderPayment::where('method_code', 'VIETQR')
                ->where('provider_ref', $plinkId)
                ->latest('id')->first();
            if ($op) return $op;
        }

        // 2) Theo orderCode trong meta (meta có thể là JSON/TEXT)
        if ($orderCode) {
            // Lấy vài bản ghi gần nhất rồi lọc bằng PHP cho an toàn kiểu cột
            $candidates = OrderPayment::where('method_code', 'VIETQR')
                ->latest('id')->take(20)->get();

            foreach ($candidates as $c) {
                $m = $this->toArraySafe($c->meta);
                if (($m['orderCode'] ?? null) == $orderCode) {
                    return $c;
                }
            }
        }

        return null;
    }

    /**
     * Đánh dấu đã thanh toán: cập nhật cả order & order_payments.
     */
    private function markPaid(Order $order, OrderPayment $op, array $extraMeta = []): void
    {
        $meta = array_merge($this->toArraySafe($op->meta), $extraMeta);

        $op->status   = 'paid';
        if ($op->isFillable('paid_at')) {
            $op->paid_at = now();
        }
        $op->meta     = $meta;
        $op->save();

        $order->payment_status = 'paid';
        // Nếu bạn muốn tự đổi “status” xử lý đơn:
        if (in_array((string)$order->status, ['pending', 'đặt hàng'], true)) {
            $order->status = 'confirmed'; // hoặc 'processing'
        }
        $order->save();
    }
}
