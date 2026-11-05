<?php

namespace App\Events;

use App\Models\IssueComment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewComment implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;
    public $subjectType;

    public function __construct(IssueComment $comment, string $subjectType = 'issue')
    {
        $this->comment = $comment->load('user');
        $this->subjectType = $subjectType;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("{$this->subjectType}.{$this->comment->{$this->subjectType === 'issue' ? 'issue_id' : 'page_id'}}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'body' => $this->comment->body,
            'user' => [
                'id' => $this->comment->user->id,
                'name' => $this->comment->user->full_name,
                'initials' => $this->comment->user->initials,
            ],
            'timestamp' => $this->comment->created_at,
        ];
    }

    public function broadcastAs(): string
    {
        return 'new_comment';
    }
}
