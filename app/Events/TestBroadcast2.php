<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;
class TestBroadcast2 implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
    public $data;
    public $userId;

    public function __construct($message, $data = [], $userId = null)
    {
        $this->message = $message;
        $this->data = $data;
        $this->userId = $userId;
        Log::info('✏️ TestBroadcast constructed with message: '.$message);
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        Log::info('📢 Broadcasting TestBroadcast event on test-channel');
        return [new Channel('test-channel')];
    }
     public function broadcastAs(): string
    {
        return 'test.event';
    }
    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'data' => $this->data,
            'timestamp' => now()->toDateTimeString(),
            'user_id' => $this->userId
        ];
    }
}
