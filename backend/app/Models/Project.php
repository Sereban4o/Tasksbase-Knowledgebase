<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by', 'key', 'name', 'description', 'avatar',
        'type', 'visibility', 'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function hasPermission(User $user, string $permission): bool
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        if (!$member) return false;

        return match ($member->pivot->role) {
            'admin' => true,
            'member' => in_array($permission, [
                'view_issues', 'create_issues', 'edit_issues', 'transition_issues',
                'view_boards', 'edit_boards',
            ]),
            'viewer' => $permission === 'view_issues',
            default => false,
        };
    }
}
