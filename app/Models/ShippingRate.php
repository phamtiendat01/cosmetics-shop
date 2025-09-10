<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = [
        'carrier_id',
        'zone_id',
        'name',
        'min_weight',
        'max_weight',
        'min_total',
        'max_total',
        'base_fee',
        'per_kg_fee',
        'etd_min_days',
        'etd_max_days',
        'enabled'
    ];
    protected $casts = ['enabled' => 'boolean', 'min_weight' => 'float', 'max_weight' => 'float'];

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class);
    }
    public function zone()
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
