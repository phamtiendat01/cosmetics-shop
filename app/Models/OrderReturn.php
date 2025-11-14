<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'reason',
        'refund_method',
        'expected_refund',
        'final_refund',
        'meta'
    ];
    protected $casts = ['meta' => 'array'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function items()
    {
        return $this->hasMany(OrderReturnItem::class);
    }
    public function refunds()
    {
        return $this->hasMany(OrderRefund::class);
    }
}
