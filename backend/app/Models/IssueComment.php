<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_id', 'user_id', 'parent_comment_id',
        'body', 'rendered_body', 'time_spent_hours',
    ];

    protected $casts = [
        'time_spent_hours' => 'decimal:2',
    ];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(IssueComment::class, 'parent_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(IssueComment::class, 'parent_comment_id');
    }
}
