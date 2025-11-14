<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public Chat $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('support');
    }

    public function broadcastAs(): string
    {
        return 'chat.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => (int) $this->chat->id,
            'customer_id'   => (int) ($this->chat->customer_id ?? 0),
            'status'        => $this->chat->status ?? 'open',
            'last_message_at' => optional($this->chat->last_message_at)->toISOString(),
        ];
    }
}
