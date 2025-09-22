<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'line1',
        'line2',
        'ward',
        'district',
        'province',
        'country',
        'lat',
        'lng',
        'is_default_shipping',
        'is_default_billing',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'is_default_shipping' => 'bool',
        'is_default_billing'  => 'bool',
    ];
}
