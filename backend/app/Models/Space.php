<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by', 'key', 'name', 'description',
        'type', 'visibility', 'avatar', 'properties',
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
        return $this->belongsToMany(User::class, 'space_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Global admins and the space creator are always considered space admins.
     */
    public function isAdmin(User $user): bool
    {
        return $user->isAdmin()
            || (int) $this->created_by === (int) $user->id
            || $this->members()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }

    /**
     * Users allowed to create/edit/delete pages in this space.
     */
    public function canEdit(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->members()->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'editor'])->exists();
    }

    /**
     * Users allowed to view the space and its pages.
     */
    public function canView(User $user): bool
    {
        if ($this->visibility !== 'private' || $this->isAdmin($user)) {
            return true;
        }

        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function permissions()
    {
        return $this->hasMany(SpacePermission::class);
    }
}
