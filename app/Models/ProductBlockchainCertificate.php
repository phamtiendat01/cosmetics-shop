<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBlockchainCertificate extends Model
{
    protected $fillable = [
        'product_variant_id',
        'certificate_hash',
        'ipfs_hash',
        'ipfs_url',
        'metadata',
        'minted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'minted_at' => 'datetime',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductChainMovement::class, 'certificate_id');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(ProductQRCode::class, 'certificate_id');
    }
}
