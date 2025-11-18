<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OcrCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $results;
    /**
     * Create a new event instance.
     */
    public function __construct($results)
    {
       
        $this->results = $results;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    // public function broadcastOn(): array
    // {
    //     return [
    //         new PrivateChannel('channel-name'),
    //     ];
    // }
    public function broadcastOn()
    {
        return [
            new Channel('ocr-results')
        ];
    }

    public function broadcastAs()
    {
        return 'ocr.completed';
    }
    public function broadcastWith()
    {
        
        return [$this->results];
    }
}
