<?php

namespace App\Services\Payments;

use App\Models\Order;
use Throwable;

// Dùng đúng gateways bạn đã có
use App\Services\Payments\CodGateway;
use App\Services\Payments\MomoGateway;
use App\Services\Payments\VnPayGateway;
use App\Services\Payments\VietQrGateway;
use App\Services\Payments\Contracts\PaymentGateway;

class PaymentService
{
    /** @return PaymentGateway */
    private function gateway(string $method): PaymentGateway
    {
        return match (strtoupper($method)) {
            'COD'    => app(CodGateway::class),
            'MOMO'   => app(MomoGateway::class),
            'VNPAY'  => app(VnPayGateway::class),
            'VIETQR' => app(VietQrGateway::class),
            default  => throw new \InvalidArgumentException("Unsupported payment method: {$method}"),
        };
    }

    /** Khởi tạo giao dịch và trả meta cho FE (redirect_url/qr_url/...) */
    public function initiate(string $method, Order $order): array
    {
        try {
            return $this->gateway($method)->initiate($order);
        } catch (Throwable $e) {
            \Log::error('payment.initiate.error', [
                'method' => $method,
                'order_id' => $order->id,
                'msg' => $e->getMessage()
            ]);
            return ['ok' => false, 'message' => 'Không khởi tạo được thanh toán.'];
        }
    }

    /** Dùng chung cho return/IPN */
    public function handleCallback(string $method, array $data): array
    {
        try {
            return $this->gateway($method)->handleCallback($data);
        } catch (Throwable $e) {
            \Log::error('payment.callback.error', [
                'method' => $method,
                'msg' => $e->getMessage()
            ]);
            return ['ok' => false, 'message' => 'Xử lý callback thất bại.'];
        }
    }
}
