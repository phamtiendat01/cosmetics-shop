<?php

namespace App\Tools\Bot;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Log;

/**
 * GetUserAddressesTool - Lấy danh sách địa chỉ của user
 */
class GetUserAddressesTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để xem địa chỉ.',
            ];
        }

        $userId = auth()->id();
        
        // ✅ Log để debug
        Log::info('GetUserAddressesTool: Starting', [
            'user_id' => $userId,
            'is_authenticated' => auth()->check(),
        ]);

        try {
            $addresses = UserAddress::where('user_id', $userId)
                ->orderByDesc('is_default_shipping')
                ->orderByDesc('is_default_billing')
                ->orderByDesc('id')
                ->get();
            
            // ✅ Log để debug
            Log::info('GetUserAddressesTool: Query result', [
                'user_id' => $userId,
                'addresses_count' => $addresses->count(),
                'first_address_id' => $addresses->first()?->id,
                'first_address_name' => $addresses->first()?->name,
            ]);

            if ($addresses->isEmpty()) {
                Log::warning('GetUserAddressesTool: No addresses found', [
                    'user_id' => $userId,
                ]);
                return [
                    'success' => true,
                    'addresses' => [],
                    'total' => 0,
                    'message' => 'Bạn chưa có địa chỉ nào. Mình sẽ hướng dẫn bạn thêm địa chỉ.',
                ];
            }

            $formatted = $addresses->map(function ($addr) {
                $isDefaultShipping = (bool)$addr->is_default_shipping;
                return [
                    'id' => $addr->id,
                    'name' => $addr->name,
                    'phone' => $addr->phone,
                    'line1' => $addr->line1,
                    'line2' => $addr->line2,
                    'ward' => $addr->ward,
                    'district' => $addr->district,
                    'province' => $addr->province,
                    'country' => $addr->country,
                    'lat' => $addr->lat,
                    'lng' => $addr->lng,
                    'is_default_shipping' => $isDefaultShipping,
                    'is_default_billing' => (bool)$addr->is_default_billing,
                    'is_default' => $isDefaultShipping, // ✅ Tương thích ngược
                    'full_address' => $this->formatAddress($addr),
                ];
            })->toArray();
            
            // ✅ Log để debug
            Log::info('GetUserAddressesTool: Returning addresses', [
                'user_id' => $userId,
                'addresses_count' => count($formatted),
                'first_address_id' => $formatted[0]['id'] ?? null,
                'first_address_name' => $formatted[0]['name'] ?? null,
            ]);

            return [
                'success' => true,
                'addresses' => $formatted,
                'total' => count($formatted),
                'default_address' => $formatted[0] ?? null, // Địa chỉ mặc định (đầu tiên)
                'message' => 'Bạn có ' . count($formatted) . ' địa chỉ đã lưu.',
            ];
        } catch (\Throwable $e) {
            Log::error('GetUserAddressesTool failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'message' => 'Không thể lấy danh sách địa chỉ. Vui lòng thử lại!',
            ];
        }
    }

    /**
     * Format địa chỉ đầy đủ
     */
    private function formatAddress(UserAddress $addr): string
    {
        $parts = array_filter([
            $addr->line1,
            $addr->line2,
            $addr->ward,
            $addr->district,
            $addr->province,
        ]);

        return implode(', ', $parts);
    }
}

