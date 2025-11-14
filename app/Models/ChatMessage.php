<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_id',
        'sender_type',
        'sender_id',
        'type',
        'body',
        'payload',
        'delivered_at',
        'read_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
