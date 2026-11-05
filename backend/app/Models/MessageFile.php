<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id', 'file_id', 'mime_type', 'size', 'thumbnail_url',
    ];

    protected $appends = ['url', 'file_name'];

    public function getUrlAttribute(): ?string
    {
        if (!empty($this->thumbnail_url)) {
            return $this->thumbnail_url;
        }

        $path = $this->file?->path;
        if (!$path) {
            return null;
        }

        return url('storage/' . ltrim($path, '/'));
    }

    public function getFileNameAttribute(): string
    {
        return $this->file?->original_name ?? ('file_' . $this->id);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}
