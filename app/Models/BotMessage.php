<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotMessage extends Model
{
    public $timestamps = false; // Table chỉ có created_at, không có updated_at
    
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'intent',
        'confidence',
        'tools_used',
        'metadata',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'tools_used' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BotConversation::class, 'conversation_id');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(BotAnalytic::class, 'message_id');
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
