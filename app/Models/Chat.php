<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Chat extends Model
{
    protected $fillable = [
        'customer_id',
        'visitor_session_id',
        'assigned_to',
        'source',
        'product_id',
        'order_id',
        'status',
        'public_token',
        'last_message_at',
        'closed_at',
        'rating',
        'tags'
    ];

    protected $casts = [
        'tags' => 'array',
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
    public function participants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class);
    }
}
