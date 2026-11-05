<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueKnowledgebaseLink extends Model
{
    use HasFactory;

    protected $fillable = ['issue_id', 'page_id', 'context'];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
