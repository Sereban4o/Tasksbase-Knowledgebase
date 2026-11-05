<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chat_id', 'sender_id', 'parent_id', 'body', 'type', 'metadata',
        'edit_count', 'edited_at', 'deleted_at',
    ];

    protected $casts = [
        'body' => 'encrypted',
        'metadata' => 'array',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(MessageFile::class);
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function markAsDeleted(): void
    {
        $this->update([
            'deleted_at' => now(),
            'body' => '[Удалено]',
        ]);
    }
}
