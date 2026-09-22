<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;

    public $payload;

    public function __construct($roomId, array $payload)
    {
        $this->roomId = $roomId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('stage-room.'.$this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.state.updated';
    }
}
