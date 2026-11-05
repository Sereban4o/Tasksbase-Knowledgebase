<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $pageId;
    public $username;

    public function __construct(int $userId, int $pageId, string $username)
    {
        $this->userId = $userId;
        $this->pageId = $pageId;
        $this->username = $username;
    }

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("page.{$this->pageId}.presence");
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'username' => $this->username,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user-typing';
    }
}
