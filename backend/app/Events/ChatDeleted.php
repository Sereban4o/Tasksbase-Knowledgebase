<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $chatId,
        public string $type,
        public ?string $name,
        public array $memberIds,
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("chat.{$this->chatId}")];

        foreach ($this->memberIds as $memberId) {
            $channels[] = new PrivateChannel("user.{$memberId}");
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'chat_type' => $this->type,
            'chat_name' => $this->name,
        ];
    }
}
