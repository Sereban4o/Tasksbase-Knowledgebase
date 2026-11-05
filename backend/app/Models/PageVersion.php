<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageVersion extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'version', 'author_id', 'summary', 'body_snapshot'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
