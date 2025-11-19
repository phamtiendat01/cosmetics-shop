<?php

namespace App\Tools\Bot;

use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

/**
 * GetPaymentMethodsTool - Lấy danh sách phương thức thanh toán
 */
class GetPaymentMethodsTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để xem phương thức thanh toán.',
            ];
        }

        try {
            // Lấy số dư ví
            $wallet = Wallet::firstOrCreate(['user_id' => auth()->id()], ['balance' => 0]);
            $walletBalance = (int)$wallet->balance;

            // Danh sách phương thức thanh toán
            $methods = [
                [
                    'code' => 'COD',
                    'label' => 'COD (Thanh toán khi nhận hàng)',
                    'hint' => 'Thu tiền khi giao hàng',
                    'icon' => 'cod',
                ],
                [
                    'code' => 'VIETQR',
                    'label' => 'Chuyển khoản VietQR',
                    'hint' => 'Quét mã & chuyển khoản nhanh',
                    'icon' => 'qr',
                ],
                [
                    'code' => 'MOMO',
                    'label' => 'MoMo',
                    'hint' => 'Thanh toán qua ví MoMo',
                    'icon' => 'momo',
                ],
                [
                    'code' => 'VNPAY',
                    'label' => 'VNPay',
                    'hint' => 'Cổng thanh toán VNPay',
                    'icon' => 'card',
                ],
            ];

            // Thêm WALLET nếu có số dư
            if ($walletBalance > 0) {
                $methods[] = [
                    'code' => 'WALLET',
                    'label' => 'Ví Cosme',
                    'hint' => 'Số dư: ' . number_format($walletBalance, 0, ',', '.') . '₫',
                    'icon' => 'wallet',
                    'balance' => $walletBalance,
                ];
            }

            return [
                'success' => true,
                'methods' => $methods,
                'wallet_balance' => $walletBalance,
                'total' => count($methods),
                'message' => 'Bạn có thể chọn một trong các phương thức thanh toán sau.',
            ];
        } catch (\Throwable $e) {
            Log::error('GetPaymentMethodsTool failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Không thể lấy danh sách phương thức thanh toán. Vui lòng thử lại!',
            ];
        }
    }
}

