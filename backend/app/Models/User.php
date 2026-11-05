<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'avatar',
        'status',
        'role_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Relationships ───

    public function ownedProjects()
    {
        return $this->hasMany(\App\Models\Project::class, 'created_by');
    }

    public function projectMemberships()
    {
        return $this->belongsToMany(\App\Models\Project::class, 'project_members')
            ->withColumn('role')
            ->withTimestamps();
    }

    public function assignedIssues()
    {
        return $this->hasMany(Issue::class, 'assigned_to');
    }

    public function createdIssues()
    {
        return $this->hasMany(Issue::class, 'created_by');
    }

    public function createdPages()
    {
        return $this->hasMany(Page::class, 'author_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function activityLog()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role && $this->role->name === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    // ─── Accessors ───

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
