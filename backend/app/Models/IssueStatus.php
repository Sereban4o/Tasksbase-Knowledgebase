<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'color', 'is_used', 'sort_order'];

    protected $casts = [
        'is_used' => 'boolean',
        'sort_order' => 'integer',
    ];
}
