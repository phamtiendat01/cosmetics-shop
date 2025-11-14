<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Payments\Contracts\PaymentGateway;

class CodGateway implements PaymentGateway
{
    public function initiate(Order $order): array
    {
        // tạo bản ghi payment ở trạng thái pending (thực chất COD là unpaid, nhưng pending để theo dõi)
        OrderPayment::create([
            'order_id'    => $order->id,
            'method_code' => 'COD',
            'amount'      => $order->grand_total,
            'status'      => 'pending',
            'meta'        => ['note' => 'COD'],
        ]);

        // Đơn ở trạng thái chờ xác nhận
        $order->update([
            'payment_method' => 'COD',
            'payment_status' => 'unpaid',
            'order_status'   => 'cho_xac_nhan',
        ]);

        return ['ok' => true, 'message' => 'Đặt hàng COD thành công'];
    }

    public function handleCallback(array $data): array
    {
        return ['ok' => true, 'message' => 'COD không dùng callback'];
    }
}
