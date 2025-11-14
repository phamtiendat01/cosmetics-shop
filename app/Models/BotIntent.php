<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotIntent extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'examples',
        'handler_class',
        'is_active',
        'priority',
        'config',
    ];

    protected $casts = [
        'examples' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'config' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderByDesc('priority');
    }
}
