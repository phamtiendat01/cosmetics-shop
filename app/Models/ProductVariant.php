<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'compare_at_price',
        'weight_grams',
        'is_active'
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'price'            => 'decimal:2',
        'compare_at_price' => 'decimal:2',
    ];
    // Cho phép gọi ProductVariant::active()
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_variant_id');
    }
    public function adjustments()
    {
        return $this->hasMany(\App\Models\InventoryAdjustment::class, 'product_variant_id');
    }
}
