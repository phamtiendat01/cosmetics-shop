<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductChainMovement extends Model
{
    protected $fillable = [
        'product_variant_id',
        'certificate_id',
        'movement_type',
        'from_location',
        'to_location',
        'order_id',
        'order_item_id',
        'batch_number',
        'quantity',
        'moved_at',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(ProductBlockchainCertificate::class, 'certificate_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
