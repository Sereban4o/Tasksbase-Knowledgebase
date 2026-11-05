<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\SavedFilter;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilterController extends Controller
{
    public function index(Request $request)
    {
        $filters = SavedFilter::where(function ($q) use ($request) {
            $q->where('user_id', Auth::id())
              ->orWhere('is_global', true);
        })
            ->with('project')
            ->latest()
            ->get();

        return response()->json($filters);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'jql_query' => 'required|string',
            'project_id' => 'nullable|exists:projects,id',
            'is_global' => 'sometimes|boolean',
        ]);

        $filter = SavedFilter::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'jql_query' => $validated['jql_query'],
            'project_id' => $validated['project_id'] ?? null,
            'is_global' => $validated['is_global'] ?? false,
        ]);

        return response()->json($filter, 201);
    }

    public function show(Request $request, string $filterId)
    {
        $filter = SavedFilter::where('user_id', Auth::id())
            ->orWhere('is_global', true)
            ->firstOrFail();

        return response()->json($filter);
    }

    public function update(Request $request, string $filterId)
    {
        $filter = SavedFilter::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'jql_query' => 'sometimes|string',
            'is_global' => 'sometimes|boolean',
        ]);

        $filter->update($validated);

        return response()->json($filter);
    }

    public function destroy(Request $request, string $filterId)
    {
        $filter = SavedFilter::where('user_id', Auth::id())->firstOrFail();
        $filter->delete();

        return response()->json(['message' => 'Filter deleted']);
    }

    public function execute(Request $request, string $filterId)
    {
        $filter = SavedFilter::where('user_id', Auth::id())
            ->orWhere('is_global', true)
            ->firstOrFail();

        // Simple JQL execution (expand with full JQL parser later)
        $query = Issue::with(['project', 'type', 'status', 'assignee']);

        $jql = $filter->jql_query;

        // Parse simple JQL: project = PROJ, status = "In Progress", etc.
        if (preg_match('/project\s*=\s*(\w+)/', $jql, $matches)) {
            $project = \App\Models\Project::where('key', $matches[1])->first();
            if ($project) $query->where('project_id', $project->id);
        }

        if (preg_match('/status\s*=\s*"([^"]+)"/', $jql, $matches)) {
            $query->whereHas('status', function ($q) use ($matches) {
                $q->where('name', $matches[1]);
            });
        }

        if (preg_match('/assignee\s*=\s*currentUser\(\)/', $jql)) {
            $query->where('assigned_to', Auth::id());
        }

        if (preg_match('/assignee\s*=\s*(\w+)/', $jql, $matches)) {
            $user = \App\Models\User::where('email', $matches[1])->first();
            if ($user) $query->where('assigned_to', $user->id);
        }

        if (preg_match('/priority\s*=\s*(\w+)/', $jql, $matches)) {
            $query->where('priority', $matches[1]);
        }

        if (preg_match('/order\s+by\s+(\w+)(\s+(asc|desc))?/', $jql, $matches)) {
            $query->orderBy($matches[1], $matches[3] ?? 'desc');
        }

        $issues = $query->limit(100)->get();

        return response()->json($issues);
    }
}
