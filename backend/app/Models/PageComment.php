<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageComment extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'user_id', 'body', 'line_number', 'column_number', 'resolved'];

    protected $casts = ['resolved' => 'boolean'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
