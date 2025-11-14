<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQRCode extends Model
{
    protected $table = 'product_qr_codes';

    protected $fillable = [
        'product_variant_id',
        'certificate_id',
        'order_item_id',
        'qr_code',
        'qr_image_path',
        'qr_image_url',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_count',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_flagged' => 'boolean',
        'verified_at' => 'datetime',
        'verification_count' => 'integer',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(ProductBlockchainCertificate::class, 'certificate_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(ProductVerificationLog::class, 'qr_code_id');
    }
}
