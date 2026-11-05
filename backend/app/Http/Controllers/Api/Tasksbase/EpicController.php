<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EpicController extends Controller
{
    public function index(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();

        $epics = Issue::where('project_id', $project->id)
            ->whereHas('type', function ($q) {
                $q->where('is_epic', true);
            })
            ->with(['status', 'epicIssues' => function ($query) {
                $query->with(['status', 'assignee']);
            }])
            ->latest()
            ->get();

        return response()->json($epics);
    }

    public function store(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();

        $validated = $request->validate([
            'issue_type_id' => 'required|exists:issue_types,id',
            'summary' => 'required|string|max:500',
            'description' => 'nullable|string',
        ]);

        $epic = Issue::create([
            'project_id' => $project->id,
            'issue_type_id' => $validated['issue_type_id'],
            'status_id' => 1,
            'created_by' => Auth::id(),
            'assigned_to' => Auth::id(),
            'reporter_id' => Auth::id(),
            'summary' => $validated['summary'],
            'description' => $validated['description'] ?? null,
            'key' => "{$project->key}-" . ($project->issues()->count() + 1),
        ]);

        return response()->json($epic, 201);
    }

    public function show(Request $request, string $epicId)
    {
        $epic = Issue::with([
            'status', 'epicIssues' => function ($query) {
                $query->with(['status', 'assignee', 'type']);
            },
        ])->findOrFail($epicId);

        return response()->json($epic);
    }

    public function update(Request $request, string $epicId)
    {
        $epic = Issue::findOrFail($epicId);

        $validated = $request->validate([
            'summary' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'status_id' => 'sometimes|exists:issue_statuses,id',
        ]);

        $epic->update($validated);

        return response()->json($epic);
    }

    public function destroy(Request $request, string $epicId)
    {
        $epic = Issue::findOrFail($epicId);
        $epic->delete();

        return response()->json(['message' => 'Epic deleted']);
    }
}
