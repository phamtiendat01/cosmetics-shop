<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'product_variant_id',
        'user_id',
        'delta',
        'reason',
        'note',
    ];
}
