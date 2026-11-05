<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['creator', 'members'])
            ->where(function ($q) use ($request) {
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

        $projects = $query->latest()->paginate(20);

        return response()->json($projects);
    }

    public function show(Request $request, string $projectKey)
    {
        $project = Project::where('key', $projectKey)
            ->with(['creator', 'members'])
            ->firstOrFail();

        // Check access
        if ($project->visibility === 'private' && !$project->isMember(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        return response()->json($project);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:10|unique:projects,key',
            'description' => 'nullable|string',
            'type' => 'required|in:scrum,kanban,basic',
            'visibility' => 'sometimes|in:private,public,team',
        ]);

        $project = Project::create([
            'created_by' => Auth::id(),
            'key' => strtoupper($validated['key']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'visibility' => $validated['visibility'] ?? 'team',
        ]);

        // Add creator as admin
        $project->members()->attach(Auth::id(), ['role' => 'admin']);

        return response()->json($project, 201);
    }

    public function update(Request $request, string $projectKey)
    {
        $project = Project::where('key', $projectKey)->firstOrFail();

        if (!$project->isMember(Auth::user()) || Auth::id() !== $project->created_by) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:scrum,kanban,basic',
            'visibility' => 'sometimes|in:private,public,team',
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    public function destroy(Request $request, string $projectKey)
    {
        $project = Project::where('key', $projectKey)->firstOrFail();

        if (Auth::id() !== $project->created_by) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    public function members(Request $request, string $projectKey)
    {
        $project = Project::where('key', $projectKey)->firstOrFail();

        $members = $project->members()->with('user')->get();

        return response()->json($members);
    }

    public function addMember(Request $request, string $projectKey)
    {
        $project = Project::where('key', $projectKey)->firstOrFail();

        if (!$project->isMember(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member,viewer',
        ]);

        $project->members()->attach($validated['user_id'], ['role' => $validated['role']]);

        return response()->json(['message' => 'Member added'], 201);
    }

    public function removeMember(Request $request, string $projectKey, string $userId)
    {
        $project = Project::where('key', $projectKey)->firstOrFail();

        if (Auth::id() !== (int) $userId && Auth::id() !== $project->created_by) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $project->members()->detach($userId);

        return response()->json(['message' => 'Member removed']);
    }
}
