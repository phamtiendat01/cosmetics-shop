<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotAnalytic extends Model
{
    protected $table = 'bot_analytics';

    protected $fillable = [
        'conversation_id',
        'message_id',
        'event_type',
        'data',
        'session_id',
        'user_id',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BotConversation::class, 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(BotMessage::class, 'message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
