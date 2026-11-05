<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function burndown(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();

        $sprintId = $request->get('sprint_id');

        $query = Issue::where('project_id', $project->id)
            ->with(['sprintIssues' => function ($q) use ($sprintId) {
                if ($sprintId) $q->where('sprint_id', $sprintId);
            }]);

        // Get issues for the sprint
        $sprintIssues = Issue::where('project_id', $project->id)
            ->when($sprintId, function ($q) use ($sprintId) {
                $q->whereHas('sprintIssues', function ($q2) use ($sprintId) {
                    $q2->where('sprint_id', $sprintId);
                });
            })
            ->get();

        $totalPoints = $sprintIssues->sum('story_points') ?? 0;
        $donePoints = $sprintIssues->where('status.category', 'done')->sum('story_points') ?? 0;

        // Generate burndown data points
        $sprint = $sprintId ? \App\Models\Sprint::find($sprintId) : null;
        $days = $sprint ? $sprint->start_date->diffInDays($sprint->end_date) : 7;

        $data = [];
        for ($i = 0; $i <= $days; $i++) {
            $date = $sprint ? $sprint->start_date->copy()->addDays($i) : now()->addDays($i);
            $idealRemaining = max(0, $totalPoints - ($totalPoints / $days) * $i);
            $actualRemaining = max(0, $totalPoints - $donePoints);

            $data[] = [
                'date' => $date->toDateString(),
                'ideal' => round($idealRemaining, 2),
                'actual' => round($actualRemaining, 2),
            ];
        }

        return response()->json([
            'total_story_points' => $totalPoints,
            'done_story_points' => $donePoints,
            'remaining_story_points' => $totalPoints - $donePoints,
            'data' => $data,
        ]);
    }

    public function velocity(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();

        $sprints = \App\Models\Sprint::where('project_id', $project->id)
            ->where('status', 'completed')
            ->with(['issues' => function ($q) {
                $q->where('status.category', 'done');
            }])
            ->orderBy('end_date', 'desc')
            ->limit(12)
            ->get();

        $data = $sprints->map(function ($sprint) {
            return [
                'sprint_name' => $sprint->name,
                'completed_points' => $sprint->issues->sum('story_points') ?? 0,
                'completed_issues' => $sprint->issues->count(),
            ];
        });

        return response()->json($data->reverse()->values());
    }

    public function cumulativeFlow(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();

        $issues = Issue::where('project_id', $project->id)
            ->with(['status', 'project'])
            ->get();

        // Group by status and date
        $statusCounts = $issues->groupBy('status_id')->map->count();

        return response()->json([
            'status_counts' => $statusCounts,
            'total_issues' => $issues->count(),
        ]);
    }

    public function sprintReport(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();

        $sprintId = $request->get('sprint_id');
        $sprint = $sprintId ? \App\Models\Sprint::with(['issues.type', 'issues.status', 'issues.assignee'])->find($sprintId) : null;

        if (!$sprint) {
            return response()->json(['message' => 'Sprint not found'], 404);
        }

        $totalIssues = $sprint->issues->count();
        $completedIssues = $sprint->issues->where('status.category', 'done')->count();
        $incompleteIssues = $sprint->issues->whereNot('status.category', 'done');

        return response()->json([
            'sprint' => $sprint,
            'total_issues' => $totalIssues,
            'completed_issues' => $completedIssues,
            'incomplete_issues' => $incompleteIssues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'key' => $issue->key,
                    'summary' => $issue->summary,
                    'status' => $issue->status->name,
                    'assignee' => $issue->assignee?->name,
                ];
            }),
        ]);
    }
}
