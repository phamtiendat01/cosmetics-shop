<?php

namespace App\Policies;

use App\Models\User;
use App\Models\OrderItem;

class OrderItemReviewPolicy
{
    public function create(User $user, OrderItem $item): bool
    {
        $order = $item->order;
        if (!$order) return false;

        // Đúng chủ đơn
        $ownerId = $order->user_id ?? $order->customer_id;
        if ((int)$ownerId !== (int)$user->id) return false;

        // Tùy workflow – chỉnh theo constants của bạn
        if ($order->payment_status !== 'paid') return false;
        if (!in_array($order->status, ['completed', 'delivered'])) return false;

        // Chỉ từ chối nếu chính user này đã review item này
        return !$item->review()->where('user_id', $user->id)->exists();
    }
}
