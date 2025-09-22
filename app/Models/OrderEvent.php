<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    protected $fillable = ['order_id', 'type', 'old', 'new', 'meta'];
    protected $casts = [
        'old'  => 'array',
        'new'  => 'array',
        'meta' => 'array',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
