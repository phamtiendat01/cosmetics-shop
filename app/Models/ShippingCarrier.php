<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingRate;

class ShippingCarrier extends Model
{
    protected $fillable = ['name', 'code', 'logo', 'supports_cod', 'enabled', 'sort_order', 'config'];
    protected $casts = ['supports_cod' => 'boolean', 'enabled' => 'boolean', 'config' => 'array'];

    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'carrier_id');
    }
}
