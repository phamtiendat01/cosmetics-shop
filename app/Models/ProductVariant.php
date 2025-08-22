<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'name', 'price', 'compare_at_price', 'weight_grams', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];   // 👈 thêm

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    } // 👈 thêm
}
