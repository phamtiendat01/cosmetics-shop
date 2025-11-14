<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.' . $this->message->chat_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (int)$this->message->id,
            'chat_id' => (int)$this->message->chat_id,
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
            'type' => $this->message->type,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
