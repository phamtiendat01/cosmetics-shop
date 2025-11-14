<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVerificationLog extends Model
{
    protected $fillable = [
        'qr_code_id',
        'qr_code',
        'verification_result',
        'verifier_ip',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(ProductQRCode::class, 'qr_code_id');
    }
}
