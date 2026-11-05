<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Project;
use App\Models\Issue;
use App\Models\Space;
use App\Models\Page;
use App\Models\IssueStatus;
use App\Models\IssuePriority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // ─── System Stats ───
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
            'total_projects' => Project::count(),
            'total_issues' => Issue::count(),
            'total_spaces' => Space::count(),
            'total_pages' => Page::count(),
        ]);
    }

    // ─── Users ───
    public function allUsers()
    {
        $users = User::select('id', 'first_name', 'last_name', 'email', 'avatar')
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(function ($user) {
                $user->initials = ($user->first_name[0] ?? '') . ($user->last_name[0] ?? '');
                return $user;
            });
        return response()->json($users);
    }

    public function users(Request $request)
    {
        $query = User::with('role')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('role_id') && $request->role_id !== 'null') {
            $query->where('role_id', $request->role_id);
        }

        return response()->json($query->paginate(20));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'sometimes|in:active,suspended,pending',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role_id' => $validated['role_id'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json($user->load('role'), 201);
    }

    public function updateUser(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'password' => 'sometimes|min:8|confirmed',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'sometimes|in:active,suspended,pending',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->load('role'));
    }

    public function destroyUser(int $userId)
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete yourself'
            ], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function toggleUserStatus(int $userId)
    {
        $user = User::findOrFail($userId);
        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        return response()->json($user->load('role'));
    }

    // ─── Roles ───
    public function roles()
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function updateRolePermissions(Request $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        return response()->json($role->load('permissions'));
    }

    // ─── Projects ───
    public function projects(Request $request)
    {
        $query = Project::withCount(['issues', 'members'])
            ->with('creator');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('key', 'ilike', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    // ─── Statuses ───
    public function statuses()
    {
        $statuses = IssueStatus::orderBy('id')->get();
        return response()->json($statuses);
    }

    public function storeStatus(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'is_used' => 'boolean',
        ]);

        $status = IssueStatus::create($validated);
        return response()->json($status, 201);
    }

    public function updateStatus(Request $request, int $statusId)
    {
        $status = IssueStatus::findOrFail($statusId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:7',
            'is_used' => 'sometimes|boolean',
        ]);

        $status->update($validated);
        return response()->json($status);
    }

    public function destroyStatus(int $statusId)
    {
        $status = IssueStatus::findOrFail($statusId);
        $status->delete();
        return response()->json(['message' => 'Статус удалён']);
    }

    // ─── Priorities ───
    public function priorities()
    {
        $priorities = IssuePriority::orderBy('order')->get();
        return response()->json($priorities);
    }

    public function storePriority(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:issue_priorities',
            'color' => 'required|string|max:7',
            'order' => 'integer',
            'is_used' => 'boolean',
        ]);

        $priority = IssuePriority::create($validated);
        return response()->json($priority, 201);
    }

    public function updatePriority(Request $request, int $priorityId)
    {
        $priority = IssuePriority::findOrFail($priorityId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'key' => 'sometimes|string|max:255|unique:issue_priorities,key,' . $priorityId,
            'color' => 'sometimes|string|max:7',
            'order' => 'sometimes|integer',
            'is_used' => 'sometimes|boolean',
        ]);

        $priority->update($validated);
        return response()->json($priority);
    }

    public function destroyPriority(int $priorityId)
    {
        $priority = IssuePriority::findOrFail($priorityId);
        $priority->delete();
        return response()->json(['message' => 'Приоритет удалён']);
    }

    // ─── Spaces ───
    public function spaces()
    {
        $spaces = Space::withCount(['pages', 'members'])
            ->with('creator')
            ->latest()
            ->get();
        return response()->json($spaces);
    }

    public function storeSpace(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:spaces,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:project,personal,knowledge',
            'visibility' => 'required|in:private,public,team',
            'avatar' => 'nullable|string',
        ]);

        $space = Space::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return response()->json($space->load('creator'), 201);
    }

    public function updateSpace(Request $request, int $spaceId)
    {
        $space = Space::findOrFail($spaceId);

        $validated = $request->validate([
            'key' => 'sometimes|string|max:50|unique:spaces,key,' . $spaceId,
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'type' => 'sometimes|in:project,personal,knowledge',
            'visibility' => 'sometimes|in:private,public,team',
            'avatar' => 'nullable|string',
        ]);

        $space->update($validated);

        return response()->json($space->load('creator'));
    }

    public function destroySpace(int $spaceId)
    {
        $space = Space::findOrFail($spaceId);
        $space->delete();

        return response()->json(['message' => 'Пространство удалено']);
    }

    // ─── Roles CRUD ───
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string|max:500',
        ]);

        $role = Role::create($validated);
        return response()->json($role, 201);
    }

    public function updateRole(Request $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $roleId,
            'description' => 'sometimes|nullable|string|max:500',
        ]);

        $role->update($validated);
        return response()->json($role);
    }

    public function destroyRole(int $roleId)
    {
        $role = Role::findOrFail($roleId);

        // Prevent deleting admin role
        if ($role->name === 'admin') {
            return response()->json(['message' => 'Нельзя удалить роль администратора'], 400);
        }

        // Prevent deleting role if users have it
        if ($role->users()->exists()) {
            return response()->json(['message' => 'Нельзя удалить роль, назначенную пользователям'], 400);
        }

        $role->delete();
        return response()->json(['message' => 'Роль удалена']);
    }
}
