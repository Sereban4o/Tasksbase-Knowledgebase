<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueLink extends Model
{
    use HasFactory;

    protected $fillable = ['source_issue_id', 'target_issue_id', 'link_type', 'created_by'];

    public function source()
    {
        return $this->belongsTo(Issue::class, 'source_issue_id');
    }

    public function target()
    {
        return $this->belongsTo(Issue::class, 'target_issue_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
