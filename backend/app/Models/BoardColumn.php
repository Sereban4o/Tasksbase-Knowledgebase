<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardColumn extends Model
{
    use HasFactory;

    protected $fillable = ['board_id', 'name', 'status_id', 'order', 'wip_limit'];

    // ─── Relationships ───

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function status()
    {
        return $this->belongsTo(IssueStatus::class, 'status_id');
    }
}
