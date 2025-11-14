<?php

namespace App\Events;

use App\Models\SkinTest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class SkinTestFailed implements ShouldBroadcastNow
{
    public function __construct(public SkinTest $skinTest, public string $publicToken) {}

    public function broadcastOn(): array
    {
        return [new Channel('public-skintest.' . $this->publicToken)];
    }

    public function broadcastAs(): string
    {
        return 'skintest.failed';
    }
}
