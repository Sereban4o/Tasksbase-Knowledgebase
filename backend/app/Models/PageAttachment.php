<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'file_id', 'uploaded_by'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
