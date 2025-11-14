<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Payments\Contracts\PaymentGateway;

class VietQrGateway implements PaymentGateway
{
    /**
     * Khởi tạo giao dịch VietQR: tạo payment "pending"
     * và trả redirect_url tới trang hiển thị QR.
     */
    public function initiate(Order $order): array
    {
        // Đọc cấu hình từ config/vietqr.php (bạn đã tạo đúng)
        $bin   = (string) config('vietqr.bin');
        $acc   = (string) config('vietqr.account');
        $name  = (string) config('vietqr.name');
        $ttl   = (int)    config('vietqr.expire_minutes', 15);

        if (!$bin || !$acc || !$name) {
            return ['ok' => false, 'message' => 'Thiếu cấu hình VietQR (bin/account/name).'];
        }

        $ref    = (string) ($order->code ?? ('ORDER-' . $order->id));
        $amount = (int) round($order->grand_total);

        // Ảnh QR từ img.vietqr.io
        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.jpg?amount=%d&addInfo=%s&accountName=%s',
            $bin,
            $acc,
            $amount,
            rawurlencode($ref),
            rawurlencode($name)
        );

        // Ghi payment "pending"
        $order->payments()->create([
            'method_code' => 'VIETQR',
            'amount'      => $amount,
            'status'      => 'pending',
            'meta'        => [
                'qr_url'       => $qrUrl,
                'add_info'     => $ref,
                'bank_bin'     => $bin,
                'account_no'   => $acc,
                'account_name' => $name,
                'expire_at'    => now()->addMinutes($ttl)->toIso8601String(),
            ],
        ]);

        // Cập nhật order
        $order->update([
            'payment_method' => 'VIETQR',
            'payment_status' => 'pending',
        ]);

        return [
            'ok'           => true,
            'redirect_url' => route('payment.vietqr.show', $order),
        ];
    }

    public function handleCallback(array $data): array
    {
        // VietQR chuyển khoản thủ công, không callback máy chủ
        return ['ok' => false, 'message' => 'VietQR không có callback máy chủ.'];
    }
}
