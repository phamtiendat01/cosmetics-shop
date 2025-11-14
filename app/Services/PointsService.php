<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\UserPoint;
use Illuminate\Support\Facades\DB;

class PointsService
{
    // Tạo giao dịch earn ở trạng thái pending (mở khóa sau)
    public static function earnPending(int $userId, int $points, $availableAt, $expiresAt, $refModel = null, array $meta = []): PointTransaction
    {
        return DB::transaction(function () use ($userId, $points, $availableAt, $expiresAt, $refModel, $meta) {
            return PointTransaction::create([
                'user_id'        => $userId,
                'delta'          => $points,
                'type'           => 'earn',
                'status'         => 'pending',
                'reference_type' => $refModel ? get_class($refModel) : null,
                'reference_id'   => $refModel ? $refModel->getKey() : null,
                'available_at'   => $availableAt,
                'expires_at'     => $expiresAt,
                'meta'           => $meta,
            ]);
        });
    }

    // Xác nhận tất cả giao dịch pending đã tới hạn (dùng cho command points:confirm-due)
    public static function confirmDue(): void
    {
        PointTransaction::query()
            ->where('status', 'pending')
            ->whereNotNull('available_at')
            ->where('available_at', '<=', now())
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $tx) {
                    self::confirm($tx);
                }
            });
    }

    // Chuyển 1 giao dịch từ pending -> confirmed và +balance
    public static function confirm(PointTransaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            $tx->update(['status' => 'confirmed']);
            UserPoint::query()->updateOrCreate(
                ['user_id' => $tx->user_id],
                ['balance' => DB::raw('balance + ' . (int) $tx->delta)]
            );
        });
    }

    // Đốt điểm (khi đổi điểm ra mã giảm giá), trừ balance và ghi giao dịch burn
    public static function burn(int $userId, int $points, $refModel = null, array $meta = []): PointTransaction
    {
        return DB::transaction(function () use ($userId, $points, $refModel, $meta) {
            $up = UserPoint::firstOrCreate(['user_id' => $userId]);
            if ($up->balance < $points) {
                throw new \RuntimeException('Không đủ điểm');
            }
            $up->decrement('balance', $points);

            return PointTransaction::create([
                'user_id'        => $userId,
                'delta'          => -$points,
                'type'           => 'burn',
                'status'         => 'confirmed',
                'reference_type' => $refModel ? get_class($refModel) : null,
                'reference_id'   => $refModel ? $refModel->getKey() : null,
                'meta'           => $meta,
            ]);
        });
    }
}
