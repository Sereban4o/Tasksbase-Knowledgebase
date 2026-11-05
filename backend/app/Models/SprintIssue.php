<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SprintIssue extends Model
{
    use HasFactory;

    protected $table = 'sprint_issues';

    protected $fillable = ['sprint_id', 'issue_id', 'rank'];

    protected $casts = ['rank' => 'decimal:3'];

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }
}
