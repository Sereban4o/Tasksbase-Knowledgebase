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

    /**
     * Whether the user may view this project (and its issues/boards).
     */
    public function canView(User $user): bool
    {
        return $user->isAdmin()
            || (int) $this->created_by === (int) $user->id
            || in_array($this->visibility, ['public', 'team'], true)
            || $this->isMember($user);
    }

    /**
     * Scope a query to projects visible to the given user.
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->whereIn('visibility', ['public', 'team'])
              ->orWhere('created_by', $user->id)
              ->orWhereHas('members', function ($m) use ($user) {
                  $m->where('user_id', $user->id);
              });
        });
    }
}
