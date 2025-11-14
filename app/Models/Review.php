<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'order_item_id',
        'rating',
        'title',
        'content',
        'is_approved',
        'verified_purchase',
    ];

    protected $casts = [
        'is_approved'       => 'boolean',
        'verified_purchase' => 'boolean',
        'edited_at'         => 'datetime',

    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
