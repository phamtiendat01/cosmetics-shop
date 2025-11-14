<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'hold', 'currency'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // ===== Core ops (đảm bảo an toàn race condition) =====
    public function credit(int $amount, array $meta = [], ?string $type = 'credit', ?string $refType = null, ?int $refId = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $meta, $type, $refType, $refId) {
            $w = self::query()->whereKey($this->id)->lockForUpdate()->first();
            $w->balance += $amount;
            $w->save();

            return $w->transactions()->create([
                'type'          => $type ?? 'credit',
                'amount'        => $amount,
                'balance_after' => $w->balance,
                'reference_type' => $refType,
                'reference_id'  => $refId,
                'meta'          => $meta,
            ]);
        });
    }

    public function debit(int $amount, array $meta = [], ?string $type = 'debit', ?string $refType = null, ?int $refId = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $meta, $type, $refType, $refId) {
            $w = self::query()->whereKey($this->id)->lockForUpdate()->first();
            if ($amount > $w->balance) {
                throw new \RuntimeException('Số dư ví không đủ');
            }
            $w->balance -= $amount;
            $w->save();

            return $w->transactions()->create([
                'type'          => $type ?? 'debit',
                'amount'        => -$amount,
                'balance_after' => $w->balance,
                'reference_type' => $refType,
                'reference_id'  => $refId,
                'meta'          => $meta,
            ]);
        });
    }

    // accessor hiển thị tiền VND
    public function getBalanceMoneyAttribute(): string
    {
        return '₫' . number_format($this->balance);
    }
}
