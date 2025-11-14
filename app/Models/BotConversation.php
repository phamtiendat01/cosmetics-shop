<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotConversation extends Model
{
    public $timestamps = false; // Table không có created_at, updated_at
    
    protected $fillable = [
        'session_id',
        'user_id',
        'status',
        'metadata',
        'started_at',
        'updated_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BotMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(BotAnalytic::class, 'conversation_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
