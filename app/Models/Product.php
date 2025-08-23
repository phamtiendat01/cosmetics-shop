<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'short_desc',
        'long_desc',
        'slug',
        'thumbnail',
        'is_active',
        'has_variants',
        'skin_types',
        'concerns'
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'has_variants' => 'boolean',
        'skin_types'   => 'array',
        'concerns'     => 'array',
    ];

    /** ===== Scopes ===== */
    // Cho phép gọi Product::active()
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeOfCategory($query, $categoryId)
    {
        return $categoryId ? $query->where('category_id', $categoryId) : $query;
    }

    public function scopeOfBrand($query, $brandId)
    {
        return $brandId ? $query->where('brand_id', $brandId) : $query;
    }

    /** ===== Quan hệ ===== */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
