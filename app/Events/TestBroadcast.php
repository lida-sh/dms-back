<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // ⚡ مهم برای ارسال فوری
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestBroadcast implements ShouldBroadcastNow
{
    use SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
        Log::info('✏️ TestBroadcast constructed with message: '.$message);
    }

    public function broadcastOn(): array
    {
        Log::info('📢 Broadcasting TestBroadcast event on test-channelلیدا');
        return [new Channel('test-channel')];
    }

    public function broadcastAs(): string
    {
        return 'test.event';
    }
}
