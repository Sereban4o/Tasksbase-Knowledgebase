<?php

namespace App\Events;

use App\Models\Board;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $board;
    public $action;
    public $columnId;
    public $issueId;
    public $order;

    public function __construct(Board $board, string $action, ?int $columnId = null, ?int $issueId = null, ?int $order = null)
    {
        $this->board = $board->load(['project', 'columns', 'creator']);
        $this->action = $action;
        $this->columnId = $columnId;
        $this->issueId = $issueId;
        $this->order = $order;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("board.{$this->board->id}"),
            new PrivateChannel("project.{$this->board->project_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'board_id' => $this->board->id,
            'action' => $this->action,
            'column_id' => $this->columnId,
            'issue_id' => $this->issueId,
            'order' => $this->order,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return "board.{$this->action}";
    }
}
