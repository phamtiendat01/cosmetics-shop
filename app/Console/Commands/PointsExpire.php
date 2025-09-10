<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PointTransaction;
use App\Models\UserPoint;

class PointsExpire extends Command
{
    protected $signature = 'points:expire';
    protected $description = 'Expire confirmed point transactions that passed expires_at';

    public function handle(): int
    {
        PointTransaction::where('status', 'confirmed')
            ->where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $tx) {
                    DB::transaction(function () use ($tx) {
                        $tx->update(['status' => 'cancelled']);
                        UserPoint::query()
                            ->where('user_id', $tx->user_id)
                            ->update(['balance' => DB::raw('GREATEST(balance - ' . (int)$tx->delta . ',0)')]);
                    });
                }
            });

        $this->info('Expired old point transactions.');
        return self::SUCCESS;
    }
}
