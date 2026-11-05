<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification->load(['actor', 'subject']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->notification->user_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'data' => $this->notification->data,
            'actor' => $this->notification->actor ? [
                'id' => $this->notification->actor->id,
                'name' => $this->notification->actor->full_name,
            ] : null,
            'timestamp' => $this->notification->created_at,
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
