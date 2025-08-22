<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'image',
        'description',
        'is_active',
        'has_variants'
    ];

    protected $casts = [
        'skin_types' => 'array',
        'concerns'   => 'array',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** Tổng hợp tồn kho cho toàn bộ biến thể của 1 sản phẩm */
    public function inventories()
    {
        // Product -> ProductVariant -> Inventory
        return $this->hasManyThrough(
            Inventory::class,          // model cuối
            ProductVariant::class,     // model trung gian
            'product_id',              // FK trên product_variants trỏ về products
            'product_variant_id',      // FK trên inventories trỏ về product_variants
            'id',                      // local key của products
            'id'                       // local key của product_variants
        );
    }
}
