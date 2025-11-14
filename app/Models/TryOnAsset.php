<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TryOnAsset extends Model
{
    protected $fillable = ['effect', 'title', 'mask_url', 'config', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'bool',
    ];
}
