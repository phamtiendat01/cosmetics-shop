<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRefund extends Model
{
    protected $fillable = [
        'order_id',
        'order_return_id',
        'provider',
        'amount',
        'status',
        'provider_ref',
        'meta',
        'processed_at'
    ];
    protected $casts = ['meta' => 'array', 'processed_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }
}
