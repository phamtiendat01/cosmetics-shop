<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // ⟵ THÊM

class Product extends Model
{
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'short_desc',
        'long_desc',
        'description',
        'slug',
        'thumbnail',
        'is_active',
        'has_variants',
        'skin_types',
        'concerns',
        'ingredients',
        'benefits',
        'usage_instructions',
        'age_range',
        'gender',
        'product_type',
        'texture',
        'spf',
        'fragrance_free',
        'cruelty_free',
        'vegan',
        'sold_count',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'has_variants' => 'boolean',
        'skin_types'   => 'array',
        'concerns'     => 'array',
        'ingredients'  => 'array',
        'fragrance_free' => 'boolean',
        'cruelty_free' => 'boolean',
        'vegan'        => 'boolean',
        'spf'          => 'integer',
        'sold_count'   => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $p) {
            if (blank($p->long_desc) && filled($p->description)) {
                $p->long_desc = $p->description;
            }
            if (blank($p->short_desc)) {
                $source = (string)($p->long_desc ?: $p->description ?: '');
                $plain  = trim(preg_replace('/\s+/u', ' ', strip_tags($source)));
                if ($plain !== '') $p->short_desc = Str::limit($plain, 160);
            }
        });
    }

    /* ================= Scopes ================= */
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

    /* ================= Quan hệ ================= */
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

    // Tất cả review (không lọc)
    public function reviews(): HasMany
    {
        return $this->hasMany(\App\Models\ProductReview::class, 'product_id');
    }

    // Review đã duyệt (vẫn giữ nếu nơi khác cần)
    public function approvedReviews(): HasMany
    {
        // chỉ lấy review đã duyệt & có rating
        return $this->hasMany(Review::class)
            ->where('is_approved', 1)
            ->whereNotNull('rating');
    }

    // ⭐ Quan hệ dùng để tính rating trung bình cho listing:
    // - chỉ lấy review có rating
    // - nếu CSDL có cột approved thì chỉ lấy approved = 1; nếu không có, bỏ qua điều kiện đó
    public function ratedReviews(): HasMany
    {
        // alias dùng ở HomeController
        return $this->approvedReviews();
    }

    /** Tồn kho các biến thể */
    public function inventories(): HasManyThrough
    {
        return $this->hasManyThrough(
            Inventory::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id'
        );
    }

    public function scopeWithStockSum($q)
    {
        return $q->withSum('inventories as stock_sum', 'qty_in_stock');
    }

    public function scopeWithStockLeft($q)
    {
        return $q->selectSub("
            COALESCE((
                SELECT SUM(i.qty_in_stock)
                FROM product_variants v
                LEFT JOIN inventories i ON i.product_variant_id = v.id
                WHERE v.product_id = products.id
            ), 0)
        ", 'stock_left');
    }

    /* ===== Accessors ===== */
    public function getDescSummaryAttribute(): string
    {
        $short = trim((string)$this->short_desc);
        if ($short !== '') return $short;
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($this->long_desc ?: $this->description ?: ''))));
        return Str::limit($plain, 160);
    }

    public function getLongDescHtmlAttribute(): string
    {
        if ($this->long_desc) return (string)$this->long_desc;
        return nl2br(e((string)$this->description));
    }
}
