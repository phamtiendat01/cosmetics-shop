<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ShippingVoucher extends Model
{
    protected $table = 'shipping_vouchers';
    protected $guarded = [];
    protected $casts = [
        'regions'       => 'array',
        'carriers'      => 'array',
        'is_active'     => 'boolean',
        'start_at'      => 'datetime',
        'end_at'        => 'datetime',
        'usage_limit'   => 'integer',
        'per_user_limit' => 'integer',
        'amount'        => 'integer',
        'max_discount'  => 'integer',
        'min_order'     => 'integer',
    ];

    /* Scopes tiện dụng */
    public function scopeValid(Builder $q): Builder
    {
        $now = Carbon::now();
        return $q->where('is_active', 1)
            ->where(function ($qq) use ($now) {
                $qq->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($qq) use ($now) {
                $qq->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    public function scopeForUser(Builder $q, $user): Builder
    {
        if (!$user) return $q;
        return $q->where(function ($qq) use ($user) {
            $qq->whereNull('user_id')->orWhere('user_id', $user->id);
        });
    }

    public function usages()
    {
        return $this->hasMany(ShippingVoucherUsage::class);
    }

    /* Helper hiển thị */
    public function discountText(): string
    {
        if ($this->discount_type === 'percent') {
            $txt = "Giảm {$this->amount}%";
            if ($this->max_discount) $txt .= ' tối đa ' . number_format($this->max_discount, 0, ',', '.') . 'đ';
            return $txt;
        }
        return 'Giảm ' . number_format($this->amount, 0, ',', '.') . 'đ';
    }

    public function isRunning(): bool
    {
        $now = now();
        $ok = !$this->start_at || $this->start_at <= $now;
        $ok = $ok && (!$this->end_at || $this->end_at >= $now);
        return $this->is_active && $ok;
    }
}
