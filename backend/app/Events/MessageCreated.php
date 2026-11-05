<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $chat;

    public function __construct(Message $message, Chat $chat)
    {
        $this->message = $message->load(['sender', 'files.file']);
        $this->chat = $chat;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.{$this->chat->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'sender_id' => $this->message->sender_id,
            'body' => $this->message->body,
            'type' => $this->message->type,
            'metadata' => $this->message->metadata,
            'parent_id' => $this->message->parent_id,
            'created_at' => $this->message->created_at,
            'sender' => $this->message->sender ? [
                'id' => $this->message->sender->id,
                'first_name' => $this->message->sender->first_name,
                'last_name' => $this->message->sender->last_name,
                'avatar' => $this->message->sender->avatar,
            ] : null,
            'files' => $this->message->files,
        ];
    }
}
