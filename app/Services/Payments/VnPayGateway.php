<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VnPayGateway implements PaymentGateway
{
    public function initiate(Order $order): array
    {
        $vnpUrl  = trim(env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'));
        $tmnCode = trim(env('VNPAY_TMN_CODE', ''));
        $hashKey = trim(env('VNPAY_HASH_SECRET', ''));

        if ($tmnCode === '' || $hashKey === '') {
            return ['ok' => false, 'message' => 'VNPAY chưa được cấu hình trong .env'];
        }

        // Return URL local dùng HTTP; lên server mới force HTTPS
        $returnUrl = route('payment.vnpay.return');

        // TxnRef: duy nhất mỗi lần
        $txnRef = 'ORD' . $order->id . '-' . Str::upper(Str::random(6));

        // VNPay yêu cầu số tiền *100
        $amount = (int) round($order->grand_total) * 100;

        // Chuẩn hóa IP local
        $ip = request()->ip();
        if ($ip === '::1') $ip = '127.0.0.1';

        $params = [
            'vnp_Version'    => '2.1.0',
            'vnp_Command'    => 'pay',
            'vnp_TmnCode'    => $tmnCode,
            'vnp_Amount'     => $amount,
            'vnp_CurrCode'   => 'VND',
            'vnp_TxnRef'     => $txnRef,
            'vnp_OrderInfo'  => 'ORDER-' . $order->code,   // map ngược về đơn
            'vnp_OrderType'  => 'other',
            'vnp_Locale'     => 'vn',
            'vnp_ReturnUrl'  => $returnUrl,
            'vnp_IpAddr'     => $ip,
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => now()->addMinutes(15)->format('YmdHis'),
        ];

        // ==== SIGN EXACTLY LIKE SPEC ====
        ksort($params);
        $hashDataPieces = [];
        foreach ($params as $k => $v) {
            // KHÔNG urlencode khi ký
            $hashDataPieces[] = $k . '=' . (string) $v;
        }
        $hashData   = implode('&', $hashDataPieces);
        $secureHash = hash_hmac('sha512', $hashData, $hashKey);

        // Build query (lúc này mới encode, dùng RFC3986 để tránh dấu '+')
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986)
            . '&vnp_SecureHashType=HMACSHA512'
            . '&vnp_SecureHash=' . $secureHash;

        $payUrl = $vnpUrl . '?' . $query;

        // Log để debug nếu vẫn báo code=70
        Log::info('VNPAY initiate', [
            'hashData'    => $hashData,
            'secureHash'  => $secureHash,
            'payUrl'      => $payUrl,
            'tmnCode'     => $tmnCode,
            'hashKey_len' => strlen($hashKey),
        ]);

        // Lưu & cập nhật đơn
        $order->payments()->create([
            'method_code' => 'VNPAY',
            'amount'      => (int) round($order->grand_total),
            'status'      => 'pending',
            'meta'        => ['redirect_url' => $payUrl, 'vnp_TxnRef' => $txnRef, 'hash_data' => $hashData],
        ]);

        $upd = ['payment_method' => 'VNPAY', 'payment_status' => 'pending'];
        if (Schema::hasColumn('orders', 'order_status')) $upd['order_status'] = 'cho_thanh_toan';
        elseif (Schema::hasColumn('orders', 'status'))   $upd['status'] = 'pending';
        $order->update($upd);

        return ['ok' => true, 'redirect_url' => $payUrl, 'order_code' => $order->code];
    }

    public function handleCallback(array $data): array
    {
        $hashKey = trim(env('VNPAY_HASH_SECRET', ''));

        // Verify chữ ký từ VNPay
        $forHash = $data;
        unset($forHash['vnp_SecureHash'], $forHash['vnp_SecureHashType']);
        ksort($forHash);

        $hashDataPieces = [];
        foreach ($forHash as $k => $v) {
            $hashDataPieces[] = $k . '=' . (string) $v; // KHÔNG encode khi verify
        }
        $calc = hash_hmac('sha512', implode('&', $hashDataPieces), $hashKey);
        if (strtolower($calc) !== strtolower((string)($data['vnp_SecureHash'] ?? ''))) {
            return ['ok' => false, 'message' => 'Invalid signature'];
        }

        // Map order
        $order = null;
        if (!empty($data['vnp_OrderInfo']) && preg_match('/^ORDER-(.+)$/', (string)$data['vnp_OrderInfo'], $m)) {
            $order = \App\Models\Order::where('code', $m[1])->first();
        }
        if (!$order && !empty($data['vnp_TxnRef']) && preg_match('/^ORD(\d+)-/i', (string)$data['vnp_TxnRef'], $m)) {
            $order = \App\Models\Order::find((int)$m[1]);
        }
        if (!$order) return ['ok' => false, 'message' => 'Order not found'];

        $ok = (($data['vnp_ResponseCode'] ?? '') === '00') || (($data['vnp_TransactionStatus'] ?? '') === '00');

        if ($ok) {
            $upd = ['payment_status' => 'paid'];
            if (Schema::hasColumn('orders', 'order_status')) $upd['order_status'] = 'cho_xac_nhan';
            elseif (Schema::hasColumn('orders', 'status'))   $upd['status'] = 'confirmed';
            $order->update($upd);

            $order->payments()->where('method_code', 'VNPAY')->latest()->first()?->update([
                'status'       => 'paid',
                'provider_ref' => $data['vnp_TransactionNo'] ?? null,
                'paid_at'      => now(),
                'meta'         => $data,
            ]);

            return [
                'ok' => true,
                'message' => 'Payment success',
                'order_code' => $order->code,
                'order_id' => $order->id,
                'transaction_id' => $data['vnp_TransactionNo'] ?? null
            ];
        }

        $order->update(['payment_status' => 'failed']);
        $order->payments()->where('method_code', 'VNPAY')->latest()->first()?->update([
            'status' => 'failed',
            'meta'   => $data,
        ]);

        return ['ok' => false, 'message' => $data['vnp_Message'] ?? 'Payment failed', 'order_code' => $order->code, 'order_id' => $order->id];
    }
}
