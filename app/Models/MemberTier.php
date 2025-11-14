<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberTier extends Model
{
    protected $fillable = [
        'code',
        'name',
        'min_spend_year',
        'point_multiplier',
        'monthly_ship_quota',
        'auto_coupon_code',
        'perks_json',
        'active'
    ];
    protected $casts = ['perks_json' => 'array', 'active' => 'boolean', 'point_multiplier' => 'decimal:2'];

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }
}
