<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $fillable = ['order_id', 'method_code', 'amount', 'currency', 'provider_ref', 'status', 'meta', 'paid_at'];
    protected $casts = ['meta' => 'array', 'paid_at' => 'datetime'];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
