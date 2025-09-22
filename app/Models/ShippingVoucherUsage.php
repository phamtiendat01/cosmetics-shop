<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingVoucherUsage extends Model
{
    protected $table = 'shipping_voucher_usages';
    protected $guarded = [];

    public function voucher()
    {
        return $this->belongsTo(ShippingVoucher::class, 'shipping_voucher_id');
    }
}
