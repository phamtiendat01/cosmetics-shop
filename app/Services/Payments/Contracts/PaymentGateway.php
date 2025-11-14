<?php

namespace App\Services\Payments\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    /** Khởi tạo giao dịch, trả về mảng meta để front xử lý (redirect_url/qr_url/...) */
    public function initiate(Order $order): array;

    /** Xử lý callback/return từ cổng (nếu có), trả về ['ok'=>bool, 'message'=>..] */
    public function handleCallback(array $data): array;
}
