<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\Sprint;
use App\Models\SprintIssue;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SprintController extends Controller
{
    public function index(Request $request, string $boardId)
    {
        $sprints = Sprint::where('board_id', $boardId)
            ->with(['issues' => function ($query) {
                $query->with(['type', 'status', 'assignee']);
            }])
            ->orderBy('sequence', 'desc')
            ->get();

        return response()->json($sprints);
    }

    public function store(Request $request, string $boardId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $maxSequence = Sprint::where('board_id', $boardId)->max('sequence') ?? 0;

        $sprint = Sprint::create([
            'board_id' => $boardId,
            'name' => $validated['name'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => 'planning',
            'sequence' => $maxSequence + 1,
        ]);

        return response()->json($sprint, 201);
    }

    public function show(Request $request, string $sprintId)
    {
        $sprint = Sprint::with(['issues' => function ($query) {
            $query->with(['type', 'status', 'assignee', 'project']);
        }])->findOrFail($sprintId);

        return response()->json($sprint);
    }

    public function update(Request $request, string $sprintId)
    {
        $sprint = Sprint::findOrFail($sprintId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $sprint->update($validated);

        return response()->json($sprint);
    }

    public function start(Request $request, string $sprintId)
    {
        $sprint = Sprint::findOrFail($sprintId);

        // Complete other active sprints
        Sprint::where('board_id', $sprint->board_id)
            ->where('status', 'active')
            ->update(['status' => 'completed']);

        $sprint->update([
            'status' => 'active',
            'start_date' => now()->toDateString(),
        ]);

        return response()->json($sprint->load(['issues.type', 'issues.status', 'issues.assignee']));
    }

    public function complete(Request $request, string $sprintId)
    {
        $sprint = Sprint::findOrFail($sprintId);

        $sprint->update([
            'status' => 'completed',
            'end_date' => now()->toDateString(),
        ]);

        return response()->json($sprint);
    }

    public function addIssue(Request $request, string $sprintId)
    {
        $validated = $request->validate([
            'issue_id' => 'required|exists:issues,id',
            'rank' => 'nullable|numeric',
        ]);

        $sprint = Sprint::findOrFail($sprintId);

        // Check issue belongs to same project
        $issue = Issue::findOrFail($validated['issue_id']);
        if ($issue->project_id !== $sprint->project_id) {
            return response()->json(['message' => 'Issue does not belong to this sprint\'s project'], 422);
        }

        $rank = $validated['rank'] ?? (SprintIssue::where('sprint_id', $sprintId)->max('rank') ?? 0) + 1;

        $sprintIssue = SprintIssue::updateOrCreate(
            ['sprint_id' => $sprintId, 'issue_id' => $validated['issue_id']],
            ['rank' => $rank]
        );

        return response()->json($sprintIssue, 201);
    }

    public function removeIssue(Request $request, string $sprintId, string $issueId)
    {
        SprintIssue::where('sprint_id', $sprintId)
            ->where('issue_id', $issueId)
            ->delete();

        return response()->json(['message' => 'Issue removed from sprint']);
    }
}
