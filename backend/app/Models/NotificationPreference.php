<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'type', 'email', 'in_app', 'websocket'];

    protected $casts = [
        'email' => 'boolean',
        'in_app' => 'boolean',
        'websocket' => 'boolean',
    ];
}
