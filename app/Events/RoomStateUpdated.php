<?php

namespace App\Events;

use App\Http\Controllers\Api\StateController;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomStateUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;

    public function __construct($roomId)
    {
        $this->roomId = $roomId;
    }

    public function broadcastWith(): array
    {
        $response = (new StateController)->getState($this->roomId);
        $payload = json_decode($response->getContent(), true);

        return $payload['status'] === 'success' ? ['payload' => $payload] : [];
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
