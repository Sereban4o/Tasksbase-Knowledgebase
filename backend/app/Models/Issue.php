<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Issue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'issue_type_id', 'status_id', 'created_by',
        'assigned_to', 'reporter_id', 'parent_id', 'epic_id',
        'summary', 'description', 'key', 'sequence', 'custom_fields',
        'priority', 'start_date', 'due_date', 'story_points',
        'original_estimate_hours', 'time_spent_hours', 'remaining_hours',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'start_date' => 'date',
        'due_date' => 'date',
        'story_points' => 'integer',
    ];

    // ─── Relationships ───

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function type()
    {
        return $this->belongsTo(IssueType::class, 'issue_type_id');
    }

    public function status()
    {
        return $this->belongsTo(IssueStatus::class, 'status_id');
    }

    public function parent()
    {
        return $this->belongsTo(Issue::class, 'parent_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Issue::class, 'parent_id');
    }

    public function epic()
    {
        return $this->belongsTo(Issue::class, 'epic_id');
    }

    public function epicIssues()
    {
        return $this->hasMany(Issue::class, 'epic_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(IssueComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(IssueAttachment::class);
    }

    public function links()
    {
        return $this->hasMany(IssueLink::class, 'source_issue_id');
    }

    public function incomingLinks()
    {
        return $this->hasMany(IssueLink::class, 'target_issue_id');
    }

    public function sprintIssues()
    {
        return $this->hasMany(SprintIssue::class);
    }

    public function knowledgebaseLinks()
    {
        return $this->hasMany(IssueKnowledgebaseLink::class);
    }

    // ─── Accessors ───

    public function getSubtasksCompletedAttribute(): int
    {
        if (!$this->relationLoaded('subtasks')) {
            return $this->subtasks()
                ->whereHas('status', function ($query) {
                    $query->where('category', 'done');
                })
                ->count();
        }

        return $this->getRelationValue('subtasks')
            ->filter(function ($subtask) {
                return $subtask->status && $subtask->status->category === 'done';
            })
            ->count();
    }

    public function getProgressAttribute(): float
    {
        $total = $this->subtasks()->count();
        if ($total === 0) return 100;
        return round(($this->subtasks_completed / $total) * 100);
    }

    // ─── Scopes ───

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('issue_type_id', $typeId);
    }

    public function scopeByAssignee($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('summary', 'ilike', "%{$search}%")
              ->orWhere('description', 'ilike', "%{$search}%");
        });
    }
}
