<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MomoGateway implements PaymentGateway
{
    private function makeMomoOrderId(Order $order): string
    {
        return 'ORD' . $order->id . '-' . Str::upper(Str::random(6)); // VD: ORD123-8K2MZQ
    }

    public function initiate(Order $order): array
    {
        $endpoint    = env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey   = env('MOMO_ACCESS_KEY');
        $secretKey   = env('MOMO_SECRET_KEY');

        if (!$partnerCode || !$accessKey || !$secretKey) {
            return ['ok' => false, 'message' => 'MoMo chưa được cấu hình trong .env'];
        }

        // Ép https để tránh lỗi callback http://localhost
        $redirectUrl = route('payment.momo.return');
        $ipnUrl      = route('payment.momo.ipn');

        // Chỉ ép https khi KHÔNG phải local
        if (!app()->environment('local')) {
            $redirectUrl = preg_replace('/^http:/', 'https:', $redirectUrl);
            $ipnUrl      = preg_replace('/^http:/', 'https:', $ipnUrl);
        }


        // Bạn đang muốn thanh toán thẻ
        $requestType = 'payWithATM'; // Trang MoMo tổng hợp, có chọn phương thức

        $attempt = 0;
        do {
            $attempt++;

            $payload = [
                'partnerCode' => $partnerCode,
                'accessKey'   => $accessKey,
                'requestId'   => (string) Str::uuid(),
                'amount'      => (string) ((int) $order->grand_total),
                'orderId'     => $this->makeMomoOrderId($order),
                'orderInfo'   => 'ORDER-' . $order->code,
                'redirectUrl' => $redirectUrl,
                'ipnUrl'      => $ipnUrl,
                'requestType' => $requestType,
                'extraData'   => '',
                'lang'        => 'vi',
            ];

            // payWithCC cần email
            $email = $order->billing_email
                ?? $order->shipping_email
                ?? $order->customer_email
                ?? optional(auth()->user())->email
                ?? 'test@example.com';
            $payload['userInfo'] = ['email' => $email];

            // Ký CHÍNH XÁC theo thứ tự MoMo
            $raw = sprintf(
                'accessKey=%s&amount=%s&extraData=%s&ipnUrl=%s&orderId=%s&orderInfo=%s&partnerCode=%s&redirectUrl=%s&requestId=%s&requestType=%s',
                $payload['accessKey'],
                $payload['amount'],
                $payload['extraData'],
                $payload['ipnUrl'],
                $payload['orderId'],
                $payload['orderInfo'],
                $payload['partnerCode'],
                $payload['redirectUrl'],
                $payload['requestId'],
                $payload['requestType']
            );
            $payload['signature'] = hash_hmac('sha256', $raw, $secretKey);

            $resp = Http::post($endpoint, $payload)->json();

            // Trùng orderId → tạo lại 1 lần
            if (($resp['resultCode'] ?? null) === 41 && $attempt < 2) {
                continue;
            }

            if (is_array($resp) && ($resp['resultCode'] ?? -1) === 0 && !empty($resp['payUrl'])) {
                $order->payments()->create([
                    'method_code' => 'MOMO',
                    'amount'      => (int) $payload['amount'],
                    'status'      => 'pending',
                    'meta'        => [
                        'redirect_url'  => $resp['payUrl'],
                        'deeplink'      => $resp['deeplink'] ?? null,
                        'raw'           => $resp,
                        'momo_orderId'  => $payload['orderId'],
                    ],
                ]);
                $order->update(['payment_method' => 'MOMO', 'payment_status' => 'pending']);

                return ['ok' => true, 'redirect_url' => $resp['payUrl'], 'order_code' => $order->code];
            }

            // Thất bại → luôn kèm momo_orderId để tra cứu
            $meta = is_array($resp) ? $resp : ['note' => 'No response'];
            $meta['momo_orderId'] = $payload['orderId'];

            $order->payments()->create([
                'method_code' => 'MOMO',
                'amount'      => (int) $payload['amount'],
                'status'      => 'failed',
                'meta'        => $meta,
            ]);
            $order->update(['payment_method' => 'MOMO', 'payment_status' => 'failed']);

            $msg = $resp['message'] ?? 'MoMo trả về lỗi';
            if (isset($resp['resultCode'])) $msg .= " (resultCode={$resp['resultCode']})";
            return ['ok' => false, 'message' => $msg, 'order_code' => $order->code];
        } while (true);
    }

    public function handleCallback(array $data): array
    {
        // Tìm Order từ orderInfo (chứa ORDER-<code>) hoặc giải mã từ orderId (ORD<id>-xxxxxx)
        $order = null;
        if (!empty($data['orderInfo']) && preg_match('/^ORDER-(.+)$/', (string) $data['orderInfo'], $m)) {
            $order = Order::where('code', $m[1])->first();
        }
        if (!$order && !empty($data['orderId']) && preg_match('/^ORD(\d+)-/i', (string) $data['orderId'], $m)) {
            $order = Order::find((int) $m[1]);
        }
        if (!$order) {
            return ['ok' => false, 'message' => 'Order not found for MoMo callback'];
        }

        // Lấy payment liên quan (mới nhất của MOMO cho order này)
        $payment = $order->payments()->where('method_code', 'MOMO')->latest()->first();

        if ((int)($data['resultCode'] ?? -1) === 0) {
            $upd = ['payment_status' => 'paid'];
            if (Schema::hasColumn('orders', 'order_status')) {
                $upd['order_status'] = 'cho_xac_nhan';
            } elseif (Schema::hasColumn('orders', 'status')) {
                $upd['status'] = 'confirmed';
            }
            $order->update($upd);

            if ($payment) {
                $payment->update([
                    'status'       => 'paid',
                    'provider_ref' => $data['transId'] ?? null,
                    'paid_at'      => now(),
                    'meta'         => $data,
                ]);
            }

            return [
                'ok'             => true,
                'message'        => 'Payment success',
                'order_code'     => $order->code,
                'order_id'       => $order->id,     // <<< thêm dòng này
                'transaction_id' => $data['transId'] ?? null
            ];
        }

        // Thất bại
        $order->update(['payment_status' => 'failed']);
        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'meta'   => $data,
            ]);
        }

        return ['ok' => false, 'message' => $data['message'] ?? 'Payment failed', 'order_code' => $order->code, 'order_id' => $order->id];
    }
}
