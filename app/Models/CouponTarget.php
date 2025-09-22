<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponTarget extends Model
{
    protected $fillable = ['coupon_id', 'target_type', 'target_id', 'is_excluded'];
    protected $casts = ['is_excluded' => 'bool'];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
