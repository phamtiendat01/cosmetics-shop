<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponRedemption extends Model
{
    protected $fillable = [
        'coupon_id',
        'code',
        'user_id',
        'order_id',
        'discount_amount',
        'shipping_discount_amount'
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function redemptions()
    {
        return $this->hasMany(\App\Models\CouponRedemption::class, 'coupon_id');
    }
}
