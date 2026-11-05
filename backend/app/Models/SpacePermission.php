<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpacePermission extends Model
{
    use HasFactory;

    protected $fillable = ['space_id', 'target_type', 'target_value', 'permission'];
}
