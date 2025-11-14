<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'meta'
    ];
    protected $casts = ['meta' => 'array'];

    // Để view dùng $t->title và $t->ref_code
    public function getTitleAttribute(): ?string
    {
        return $this->meta['title'] ?? null;
    }
    public function getRefCodeAttribute(): ?string
    {
        return $this->meta['ref_code'] ?? null;
    }
}
