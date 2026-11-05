<?php

namespace App\Events;

use App\Models\Page;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $page;
    public $action;

    public function __construct(Page $page, string $action = 'updated')
    {
        $this->page = $page->load(['space', 'author', 'lastEditor']);
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("space.{$this->page->space_id}"),
            new PrivateChannel("page.{$this->page->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->page->id,
            'title' => $this->page->title,
            'status' => $this->page->status,
            'version' => $this->page->version,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return "page.{$this->action}";
    }
}
