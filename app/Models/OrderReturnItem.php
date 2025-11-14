<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturnItem extends Model
{
    protected $fillable = [
        'order_return_id',
        'order_item_id',
        'qty',
        'approved_qty',
        'condition',
        'line_refund',
        'meta'
    ];
    protected $casts = ['meta' => 'array'];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
