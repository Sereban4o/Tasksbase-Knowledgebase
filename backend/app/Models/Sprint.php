<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'board_id', 'name',
        'start_date', 'end_date', 'status', 'sequence',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function issues()
    {
        return $this->belongsToMany(Issue::class, 'sprint_issues')
            ->withPivot('rank')
            ->orderByPivot('rank')
            ->withTimestamps();
    }
}
