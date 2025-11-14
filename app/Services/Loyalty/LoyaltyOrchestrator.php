<?php

namespace App\Services\Loyalty;

use App\Models\Order;

class LoyaltyOrchestrator
{
    public function __construct(
        private TierService $tiers,
        private PointService $points,
        private PerkIssuer $perks
    ) {}

    public function onOrderCompleted(Order $order): void
    {
        $user = $order->user;
        if (!$user) return;

        $eligible = max($order->subtotal - $order->discount_total, 0);

        // Evaluate tier (ghi/giữ user_tiers)
        $userTier = $this->tiers->evaluate($user);
        $mult = (float) ($userTier->tier->point_multiplier ?? 1.0);

        // Award points (idempotent per order)
        $this->points->earnForOrder($user, $order->id, $eligible, $mult, [
            'order_code' => $order->code,
        ]);

        // Attach tier coupon (one-time attach)
        $this->perks->attachTierCoupon($user, $userTier->tier->auto_coupon_code);
    }
}
