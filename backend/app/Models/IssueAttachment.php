<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['issue_id', 'file_id', 'uploaded_by'];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
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
