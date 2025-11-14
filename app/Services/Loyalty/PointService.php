<?php

namespace App\Services\Loyalty;

use App\Models\{User, UserPoint, PointTransaction};
use Illuminate\Support\Facades\DB;

class PointService
{
    public function earnForOrder(User $user, int $orderId, int $eligibleVnd, float $multiplier, array $meta = []): ?PointTransaction
    {
        // Idempotent: nếu đã có transaction earn cho order này thì bỏ qua
        $exists = PointTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'earn')
            ->where('meta->order_id', $orderId)
            ->exists();
        if ($exists) return null;

        $pointsRate = (float) config('loyalty.point_per_vnd', 1 / 1000);
        $points = (int) round($eligibleVnd * $pointsRate * max(1.0, $multiplier));

        $tx = new PointTransaction();
        $tx->user_id = $user->id;
        $tx->delta = $points;
        $tx->type = 'earn';
        $tx->status = 'confirmed'; // hoặc 'pending' -> confirm sau X ngày
        $tx->available_at = now();

        // hết hạn 31/12 năm sau
        $tx->expires_at = now()->setDate(now()->year + 1, 12, 31)->endOfDay();

        $tx->meta = array_merge($meta, [
            'order_id' => $orderId,
            'eligible_vnd' => $eligibleVnd,
            'multiplier' => $multiplier,
        ]);
        $tx->save();

        // cập nhật số dư
        $up = UserPoint::firstOrCreate(['user_id' => $user->id]);
        $up->balance = DB::raw('balance + ' . $points);
        $up->save();

        return $tx;
    }

    public function revertForOrder(User $user, int $orderId, int $pointsToRevert, array $meta = []): ?PointTransaction
    {
        // tránh đảo nhiều lần
        $exists = PointTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'revert')
            ->where('meta->order_id', $orderId)
            ->exists();
        if ($exists) return null;

        $tx = new PointTransaction();
        $tx->user_id = $user->id;
        $tx->delta = -abs($pointsToRevert);
        $tx->type = 'revert';
        $tx->status = 'confirmed';
        $tx->available_at = now();
        $tx->expires_at = now(); // đảo điểm không có hạn
        $tx->meta = array_merge($meta, ['order_id' => $orderId]);
        $tx->save();

        $up = UserPoint::firstOrCreate(['user_id' => $user->id]);
        $up->balance = DB::raw('balance - ' . abs($pointsToRevert));
        $up->save();

        return $tx;
    }
}
