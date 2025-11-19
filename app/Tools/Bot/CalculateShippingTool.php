<?php

namespace App\Tools\Bot;

use App\Models\UserAddress;
use App\Services\Shipping\DistanceEstimator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CalculateShippingTool - Tính phí vận chuyển dựa trên địa chỉ
 */
class CalculateShippingTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để tính phí vận chuyển.',
            ];
        }

        // Lấy subtotal từ cart
        $items = session('cart.items', []);
        $subtotal = 0;
        foreach ($items as $it) {
            $subtotal += (int)($it['price'] ?? 0) * (int)($it['qty'] ?? 1);
        }

        // Tìm địa chỉ từ message hoặc context
        $address = $this->findAddress($message, $context);

        if (!$address) {
            return [
                'success' => false,
                'message' => 'Mình cần địa chỉ giao hàng để tính phí ship. Bạn có thể cho mình biết địa chỉ không?',
            ];
        }

        try {
            // Tính phí ship
            $result = DistanceEstimator::estimateFee(
                $address->lat,
                $address->lng,
                $subtotal
            );

            $shippingFee = (int)($result['fee'] ?? 0);
            $km = $result['km'] ?? null;
            $reason = $result['reason'] ?? 'tier';

            // Lưu vào session
            session(['cart.shipping_fee' => $shippingFee]);
            session()->save();

            $feeText = $shippingFee > 0 
                ? number_format($shippingFee, 0, ',', '.') . '₫'
                : 'Miễn phí';

            $message = "Phí vận chuyển đến **{$this->formatAddress($address)}**: {$feeText}";
            if ($km !== null) {
                $message .= " (khoảng cách: " . number_format($km, 1) . "km)";
            }
            if ($reason === 'free_threshold') {
                $message .= "\n\n✨ Miễn phí ship vì đơn hàng đạt ngưỡng miễn phí!";
            }

            return [
                'success' => true,
                'address_id' => $address->id,
                'address' => $this->formatAddress($address),
                'shipping_fee' => $shippingFee,
                'km' => $km,
                'reason' => $reason,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            Log::error('CalculateShippingTool failed', [
                'error' => $e->getMessage(),
                'address_id' => $address->id ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tính phí vận chuyển. Vui lòng thử lại!',
            ];
        }
    }

    /**
     * Tìm địa chỉ từ message hoặc context
     */
    private function findAddress(string $message, array $context): ?UserAddress
    {
        $userId = auth()->id();
        $lower = Str::lower(trim($message));

        // ✅ Ưu tiên: Tìm từ available_addresses trong context (checkout_data)
        $availableAddresses = $context['checkout_data']['available_addresses'] ?? [];
        if (!empty($availableAddresses)) {
            // Check nếu user chọn địa chỉ theo index (số 1, số 2, địa chỉ số 1...)
            if (preg_match('/\b(?:địa chỉ|address)?\s*(?:số|thứ)\s*(\d+)\b/u', $lower, $m) || 
                preg_match('/\b(?:địa chỉ|address)\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm)\b/u', $lower, $m)) {
                $index = $this->extractIndex($m[0] ?? $m[1] ?? '');
                if ($index !== null && isset($availableAddresses[$index - 1])) {
                    $addressData = $availableAddresses[$index - 1];
                    $addressId = $addressData['id'] ?? null;
                    if ($addressId) {
                        return UserAddress::where('user_id', $userId)->find($addressId);
                    }
                }
            }
            
            // Nếu chỉ có 1 địa chỉ và user không chỉ định → dùng địa chỉ đó
            if (count($availableAddresses) === 1) {
                $addressData = $availableAddresses[0];
                $addressId = $addressData['id'] ?? null;
                if ($addressId) {
                    return UserAddress::where('user_id', $userId)->find($addressId);
                }
            }
        }

        // Check nếu user chọn địa chỉ theo index (địa chỉ thứ nhất, thứ hai...)
        if (preg_match('/\b(địa chỉ|address)\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|1|2|3|4|5)\b/u', $lower, $m)) {
            $index = $this->extractIndex($m[0]);
            if ($index !== null) {
                $addresses = UserAddress::where('user_id', $userId)
                    ->orderByDesc('is_default_shipping')
                    ->orderByDesc('id')
                    ->get();
                
                if (isset($addresses[$index - 1])) {
                    return $addresses[$index - 1];
                }
            }
        }

        // Check nếu có address_id trong context
        if (!empty($context['checkout_data']['selected_address_id'])) {
            $address = UserAddress::where('user_id', $userId)
                ->where('id', $context['checkout_data']['selected_address_id'])
                ->first();
            if ($address) {
                return $address;
            }
        }

        // Lấy địa chỉ mặc định
        $defaultAddress = UserAddress::where('user_id', $userId)
            ->where('is_default_shipping', true)
            ->first();

        if ($defaultAddress) {
            return $defaultAddress;
        }

        // Lấy địa chỉ đầu tiên
        return UserAddress::where('user_id', $userId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Extract index từ text
     */
    private function extractIndex(string $text): ?int
    {
        $numberMap = [
            'đầu tiên' => 1, 'nhất' => 1, 'một' => 1, '1' => 1,
            'hai' => 2, '2' => 2,
            'ba' => 3, '3' => 3,
            'bốn' => 4, '4' => 4,
            'năm' => 5, '5' => 5,
        ];

        foreach ($numberMap as $word => $num) {
            if (Str::contains(Str::lower($text), $word)) {
                return $num;
            }
        }

        if (preg_match('/(\d+)/', $text, $m)) {
            return (int)$m[1];
        }

        return null;
    }

    /**
     * Format địa chỉ
     */
    private function formatAddress(UserAddress $addr): string
    {
        $parts = array_filter([
            $addr->line1,
            $addr->ward,
            $addr->district,
            $addr->province,
        ]);

        return implode(', ', $parts);
    }
}

