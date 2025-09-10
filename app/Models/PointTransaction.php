<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    protected $fillable = ['user_id', 'delta', 'type', 'status', 'reference_type', 'reference_id', 'meta', 'available_at', 'expires_at'];
    protected $casts = ['meta' => 'array', 'available_at' => 'datetime', 'expires_at' => 'datetime'];
}
