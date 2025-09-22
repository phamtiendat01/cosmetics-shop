<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PointsService
{
    public static function earnPending(int $userId, int $points, $availableAt, $expiresAt, $ref = null, array $meta = []): PointTransaction
    {
        return DB::transaction(function () use ($userId, $points, $availableAt, $expiresAt, $ref, $meta) {
            return PointTransaction::create([
                'user_id' => $userId,
                'delta' => $points,
                'type' => 'earn',
                'status' => 'pending',
                'reference_type' => $ref ? get_class($ref) : null,
                'reference_id' => $ref?->getKey(),
                'available_at' => $availableAt,
                'expires_at' => $expiresAt,
                'meta' => $meta,
            ]);
        });
    }
    public static function confirm(PointTransaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            $tx->update(['status' => 'confirmed']);
            UserPoint::query()->updateOrCreate(
                ['user_id' => $tx->user_id],
                ['balance' => DB::raw('balance + ' . (int)$tx->delta)]
            );
        });
    }
    public static function burn(int $userId, int $points, $ref = null, array $meta = []): PointTransaction
    {
        return DB::transaction(function () use ($userId, $points, $ref, $meta) {
            $up = UserPoint::firstOrCreate(['user_id' => $userId]);
            if ($up->balance < $points) throw new \RuntimeException('Không đủ điểm');
            $up->decrement('balance', $points);
            return PointTransaction::create([
                'user_id' => $userId,
                'delta' => -$points,
                'type' => 'burn',
                'status' => 'confirmed',
                'reference_type' => $ref ? get_class($ref) : null,
                'reference_id' => $ref?->getKey(),
                'meta' => $meta,
            ]);
        });
    }
    public static function confirmDue(): void
    {
        PointTransaction::where('status', 'pending')->where('available_at', '<=', now())
            ->orderBy('id')->chunkById(200, fn($rows) => $rows->each(fn($tx) => self::confirm($tx)));
    }
}
