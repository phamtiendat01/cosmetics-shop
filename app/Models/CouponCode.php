<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponCode extends Model
{
    protected $table = 'coupon_codes';

    protected $fillable = [
        'coupon_id',
        'code',
        'user_id',
        'is_used',
        'used_at',
        'expires_at'
    ];

    protected $casts = [
        'is_used'   => 'boolean',
        'used_at'   => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeActive($q)
    {
        return $q->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
