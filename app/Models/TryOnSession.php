<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TryOnSession extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
        'effect',
        'shade_hex',
        'match_score',
        'context',
    ];

    protected $casts = [
        'match_score' => 'float',
        'context'     => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
