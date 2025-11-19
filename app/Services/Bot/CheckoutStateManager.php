<?php

namespace App\Services\Bot;

use App\Models\BotConversation;

/**
 * CheckoutStateManager - Quản lý state của checkout flow
 * 
 * States:
 * - cart_added: Đã thêm vào giỏ hàng
 * - coupon_asked: Đã hỏi về mã giảm giá
 * - coupon_applied: Đã áp mã giảm giá (hoặc skip)
 * - address_asked: Đã hỏi về địa chỉ
 * - address_confirmed: Đã xác nhận địa chỉ
 * - shipping_calculated: Đã tính phí ship
 * - shipping_voucher_asked: Đã hỏi về mã vận chuyển
 * - shipping_voucher_applied: Đã áp mã vận chuyển (hoặc skip)
 * - payment_method_asked: Đã hỏi về phương thức thanh toán
 * - payment_method_selected: Đã chọn phương thức thanh toán
 * - order_placed: Đã đặt hàng thành công
 */
class CheckoutStateManager
{
    private const STATE_KEY = 'checkout_state';
    private const STATE_DATA_KEY = 'checkout_data';

    /**
     * Get current checkout state
     */
    public function getState(BotConversation $conversation): ?string
    {
        $metadata = $conversation->metadata ?? [];
        return $metadata[self::STATE_KEY] ?? null;
    }

    /**
     * Set checkout state
     */
    public function setState(BotConversation $conversation, string $state, array $data = []): void
    {
        $metadata = $conversation->metadata ?? [];
        $metadata[self::STATE_KEY] = $state;
        
        if (!empty($data)) {
            $metadata[self::STATE_DATA_KEY] = array_merge(
                $metadata[self::STATE_DATA_KEY] ?? [],
                $data
            );
        }
        
        $conversation->update(['metadata' => $metadata]);
    }

    /**
     * Get checkout data
     */
    public function getData(BotConversation $conversation): array
    {
        $metadata = $conversation->metadata ?? [];
        return $metadata[self::STATE_DATA_KEY] ?? [];
    }

    /**
     * Update checkout data
     */
    public function updateData(BotConversation $conversation, array $data): void
    {
        $metadata = $conversation->metadata ?? [];
        $currentData = $metadata[self::STATE_DATA_KEY] ?? [];
        $metadata[self::STATE_DATA_KEY] = array_merge($currentData, $data);
        $conversation->update(['metadata' => $metadata]);
    }

    /**
     * Reset checkout state (khi hoàn tất hoặc hủy)
     */
    public function reset(BotConversation $conversation): void
    {
        $metadata = $conversation->metadata ?? [];
        unset($metadata[self::STATE_KEY]);
        unset($metadata[self::STATE_DATA_KEY]);
        $conversation->update(['metadata' => $metadata]);
    }

    /**
     * Check if in checkout flow
     */
    public function isInCheckout(BotConversation $conversation): bool
    {
        $state = $this->getState($conversation);
        return $state !== null && $state !== 'order_placed';
    }

    /**
     * Get next step based on current state
     */
    public function getNextStep(string $currentState): ?string
    {
        $flow = [
            'cart_added' => 'coupon_asked',
            'coupon_asked' => 'coupon_applied',
            'coupon_applied' => 'address_asked',
            'address_asked' => 'address_confirmed',
            'address_confirmed' => 'shipping_calculated',
            'shipping_calculated' => 'shipping_voucher_asked',
            'shipping_voucher_asked' => 'shipping_voucher_applied',
            'shipping_voucher_applied' => 'payment_method_asked',
            'payment_method_asked' => 'payment_method_selected',
            'payment_method_selected' => 'order_placed',
        ];

        return $flow[$currentState] ?? null;
    }
}

