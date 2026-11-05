<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Role;
use App\Models\IssueStatus;
use App\Models\IssuePriority;
use App\Models\Space;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'users');
        
        // Stats
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'total_issues' => DB::table('issues')->count(),
            'total_spaces' => Schema::hasTable('knowledgebase_spaces') ? DB::table('knowledgebase_spaces')->count() : 0,
        ];

        return view('admin.dashboard', compact('stats', 'activeTab'));
    }

    // ===== USERS =====
    public function users(Request $request)
    {
        $search = $request->get('search', '');
        $statusFilter = $request->get('status', '');
        $roleFilter = $request->get('role', '');
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $query = User::with('role')->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($roleFilter) {
            $query->where('role_id', $roleFilter);
        }

        $users = $query->paginate($perPage)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function userCreate()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function userStore(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = 'active';
        $validated['email_verified_at'] = now();

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно создан');
    }

    public function userEdit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function userUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,suspended,pending',
            'password' => ['nullable', 'confirmed', Rules\Password::min(8)],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно обновлен');
    }

    public function userDelete(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Нельзя удалить себя');
        }

        $user->delete();
        return back()->with('success', 'Пользователь удален');
    }

    public function toggleUserStatus(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Нельзя изменить статус себя');
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('success', "Статус пользователя изменен на '{$newStatus}'");
    }

    // ===== STATUSES =====
    public function statuses(Request $request)
    {
        $search = $request->get('search', '');
        $categoryFilter = $request->get('category', '');

        $query = IssueStatus::orderBy('sort_order')->orderBy('id');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryFilter) {
            $query->where('category', $categoryFilter);
        }

        $statuses = $query->paginate(15)->withQueryString();
        $categories = IssueStatus::pluck('category')->unique()->filter()->toArray();

        return view('admin.statuses.index', compact('statuses', 'categories'));
    }

    public function statusCreate()
    {
        return view('admin.statuses.create');
    }

    public function statusStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:issue_statuses,name',
            'category' => 'nullable|string|max:100',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'is_used' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_used'] = $request->has('is_used');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        IssueStatus::create($validated);

        return redirect()->route('admin.statuses.index')->with('success', 'Статус успешно создан');
    }

    public function statusEdit(IssueStatus $status)
    {
        return view('admin.statuses.edit', compact('status'));
    }

    public function statusUpdate(Request $request, IssueStatus $status)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:issue_statuses,name,' . $status->id,
            'category' => 'nullable|string|max:100',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'is_used' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_used'] = $request->has('is_used');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $status->update($validated);

        return redirect()->route('admin.statuses.index')->with('success', 'Статус успешно обновлен');
    }

    public function statusDelete(IssueStatus $status)
    {
        $inUse = DB::table('issues')->where('status_id', $status->id)->exists();
        if ($inUse) {
            return back()->with('error', 'Нельзя удалить статус, который используется в задачах');
        }

        $status->delete();
        return back()->with('success', 'Статус удален');
    }

    // ===== PRIORITIES =====
    public function priorities(Request $request)
    {
        $search = $request->get('search', '');

        $query = IssuePriority::orderBy('order')->orderBy('name');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('key', 'like', "%{$search}%");
            });
        }

        $priorities = $query->paginate(15)->withQueryString();
        return view('admin.priorities.index', compact('priorities'));
    }

    public function priorityCreate()
    {
        return view('admin.priorities.create');
    }

    public function priorityStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:issue_priorities,name',
            'key' => 'required|string|max:10|unique:issue_priorities,key',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'order' => 'nullable|integer|min:0',
            'is_used' => 'boolean',
        ]);

        $validated['order'] = $request->input('order', 0);
        $validated['is_used'] = $request->has('is_used');
        IssuePriority::create($validated);

        return redirect()->route('admin.priorities.index')->with('success', 'Приоритет успешно создан');
    }

    public function priorityEdit(IssuePriority $priority)
    {
        return view('admin.priorities.edit', compact('priority'));
    }

    public function priorityUpdate(Request $request, IssuePriority $priority)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:issue_priorities,name,' . $priority->id,
            'key' => 'required|string|max:10|unique:issue_priorities,key,' . $priority->id,
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'order' => 'nullable|integer|min:0',
            'is_used' => 'boolean',
        ]);

        $validated['order'] = $request->input('order', 0);
        $validated['is_used'] = $request->has('is_used');
        $priority->update($validated);

        return redirect()->route('admin.priorities.index')->with('success', 'Приоритет успешно обновлен');
    }

    public function priorityDelete(IssuePriority $priority)
    {
        $inUse = DB::table('issues')->where('priority_id', $priority->id)->exists();
        if ($inUse) {
            return back()->with('error', 'Нельзя удалить приоритет, который используется в задачах');
        }

        $priority->delete();
        return back()->with('success', 'Приоритет удален');
    }

    // ===== ROLES =====
    public function roles(Request $request)
    {
        $search = $request->get('search', '');

        $query = Role::withCount('users')->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $roles = $query->paginate(15)->withQueryString();
        return view('admin.roles.index', compact('roles'));
    }

    public function roleCreate()
    {
        return view('admin.roles.create', []);
    }

    public function roleStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
        ]);

        Role::create($validated);
        return redirect()->route('admin.roles.index')->with('success', 'Роль успешно создана');
    }

    public function roleEdit(Role $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function roleUpdate(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
        ]);

        $role->update($validated);
        return redirect()->route('admin.roles.index')->with('success', 'Роль успешно обновлена');
    }

    public function roleDelete(Role $role, Request $request)
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'Нельзя удалить роль администратора');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Нельзя удалить роль, которая назначена пользователям');
        }

        $role->delete();
        return back()->with('success', 'Роль удалена');
    }

    // ===== SPACES =====
    public function spaces(Request $request)
    {
        $search = $request->get('search', '');

        $query = Space::withCount(['pages', 'members'])->with('creator')->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $spaces = $query->paginate(15)->withQueryString();
        return view('admin.spaces.index', compact('spaces'));
    }

    public function spaceCreate()
    {
        return view('admin.spaces.create');
    }

    public function spaceStore(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:spaces,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:project,personal,knowledge',
            'visibility' => 'required|in:private,public,team',
            'avatar' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        Space::create($validated);
        return redirect()->route('admin.spaces.index')->with('success', 'Пространство успешно создано');
    }

    public function spaceEdit(Space $space)
    {
        return view('admin.spaces.edit', compact('space'));
    }

    public function spaceUpdate(Request $request, Space $space)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:spaces,key,' . $space->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:project,personal,knowledge',
            'visibility' => 'required|in:private,public,team',
            'avatar' => 'nullable|string',
        ]);

        $space->update($validated);
        return redirect()->route('admin.spaces.index')->with('success', 'Пространство успешно обновлено');
    }

    public function spaceDelete(Space $space, Request $request)
    {
        $space->delete();
        return back()->with('success', 'Пространство удалено');
    }
}
