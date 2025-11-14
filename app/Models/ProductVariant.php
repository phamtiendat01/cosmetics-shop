<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'compare_at_price',
        'weight',
        'qty',
        'is_active',
        // optional:
        'shade_name',
        'shade_hex',
        'tryon_effect',
        'tryon_alpha',
        'tryon_enabled',
    ];

    protected $casts = [
        'price'            => 'float',
        'compare_at_price' => 'float',
        'weight'           => 'float',
        'qty'              => 'int',
        'is_active'        => 'bool',
        'tryon_enabled'    => 'bool',
        'tryon_alpha'      => 'float',
    ];

    /* ---------- SCOPES ---------- */

    /** Cho phép: $product->variants()->active() */
    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }

    /** Nhiều nơi cũ gọi inventory() để lọc còn hàng */
    public function scopeInventory($q)
    {
        $t = $this->getTable();
        if (Schema::hasColumn($t, 'qty'))           return $q->where('qty', '>', 0);
        if (Schema::hasColumn($t, 'quantity'))      return $q->where('quantity', '>', 0);
        if (Schema::hasColumn($t, 'stock'))         return $q->where('stock', '>', 0);
        if (Schema::hasColumn($t, 'inventory'))     return $q->where('inventory', '>', 0);
        if (Schema::hasColumn($t, 'inventory_qty')) return $q->where('inventory_qty', '>', 0);
        return $q; // không có cột tồn kho thì bỏ qua
    }

    /** Alias rõ nghĩa hơn */
    public function scopeInStock($q)
    {
        return $this->scopeInventory($q);
    }

    /** Thường dùng nhất: đang bán & còn hàng */
    public function scopeAvailable($q)
    {
        return $q->active()->inventory();
    }

    /* ---------- RELATIONS ---------- */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    // Blockchain relationships
    public function blockchainCertificate()
    {
        return $this->hasOne(ProductBlockchainCertificate::class);
    }

    public function qrCodes()
    {
        return $this->hasMany(ProductQRCode::class);
    }

    public function chainMovements()
    {
        return $this->hasMany(ProductChainMovement::class);
    }

    public function adjustments()
    {
        return $this->hasMany(InventoryAdjustment::class);
    }
}
