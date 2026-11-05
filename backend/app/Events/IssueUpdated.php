<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $issue;
    public $action;
    public $boardId;

    public function __construct(Issue $issue, string $action = 'updated', ?int $boardId = null)
    {
        $this->issue = $issue->load(['project', 'type', 'status', 'assignee', 'reporter', 'creator']);
        $this->action = $action;
        $this->boardId = $boardId;
    }

    public function broadcastOn(): array
    {
        $boardId = $this->boardId ?? $this->issue->project_id;
        return [
            new PrivateChannel("project.{$this->issue->project_id}"),
            new PrivateChannel("issue.{$this->issue->id}"),
            new PrivateChannel("board.{$boardId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->issue->id,
            'key' => $this->issue->key,
            'summary' => $this->issue->summary,
            'status_id' => $this->issue->status_id,
            'assigned_to' => $this->issue->assigned_to,
            'priority' => $this->issue->priority,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return "issue.{$this->action}";
    }
}
