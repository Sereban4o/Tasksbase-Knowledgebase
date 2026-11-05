<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'space_id', 'parent_id', 'author_id', 'last_edited_by',
        'title', 'body', 'status', 'version', 'labels', 'metadata',
    ];

    protected $casts = [
        'labels' => 'array',
        'metadata' => 'array',
    ];

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function parent()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function versions()
    {
        return $this->hasMany(PageVersion::class)->orderBy('version', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(PageComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(PageAttachment::class);
    }

    public function labels()
    {
        return $this->hasMany(PageLabel::class);
    }

    public function tasksbaseLinks()
    {
        return $this->hasMany(IssueKnowledgebaseLink::class);
    }

    // ─── Scopes ───

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'ilike', "%{$search}%")
              ->orWhere('body', 'ilike', "%{$search}%");
        });
    }
}
