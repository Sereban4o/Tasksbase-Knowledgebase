<?php

namespace App\Http\Controllers\Api\Knowledgebase;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Space::with(['creator', 'members'])
            ->where(function ($q) {
                $q->where('visibility', 'public')
                  ->orWhere('visibility', 'team')
                  ->orWhereHas('members', function ($q) {
                      $q->where('user_id', Auth::id());
                  });
            });

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('key', 'ilike', "%{$request->search}%");
            });
        }

        $spaces = $query->latest()->paginate(20);

        return response()->json($spaces);
    }

    public function show(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)
            ->with(['creator', 'members'])
            ->firstOrFail();

        if (!$space->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        return response()->json($space);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:10|unique:spaces,key',
            'description' => 'nullable|string',
            'type' => 'required|in:project,personal,knowledge',
            'visibility' => 'sometimes|in:private,public,team',
        ]);

        $space = Space::create([
            'created_by' => Auth::id(),
            'key' => strtoupper(Str::slug($validated['key'], '')),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'visibility' => $validated['visibility'] ?? 'team',
        ]);

        $space->members()->attach(Auth::id(), ['role' => 'admin']);

        return response()->json($space, 201);
    }

    public function update(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        if (!$space->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'sometimes|in:private,public,team',
        ]);

        $space->update($validated);

        return response()->json($space);
    }

    public function destroy(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        if (!$space->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $space->delete();

        return response()->json(['message' => 'Space deleted']);
    }

    public function members(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        return response()->json($space->members()->with('user')->get());
    }

    public function addMember(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        if (!$space->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        $space->members()->attach($validated['user_id'], ['role' => $validated['role']]);

        return response()->json(['message' => 'Member added'], 201);
    }

    public function permissions(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        return response()->json($space->permissions);
    }

    public function addPermission(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        if (!$space->isAdmin(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'target_type' => 'required|in:role,group,user',
            'target_value' => 'required|string',
            'permission' => 'required|in:read,write,admin',
        ]);

        $permission = $space->permissions()->create($validated);

        return response()->json($permission, 201);
    }

    public function removePermission(Request $request, string $spaceKey, string $permissionId)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        $permission = $space->permissions()->findOrFail($permissionId);
        $permission->delete();

        return response()->json(['message' => 'Permission removed']);
    }
}
