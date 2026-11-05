<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Page;
use App\Models\Project;
use App\Models\Space;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');

        $results = [
            'issues' => Issue::with(['project', 'type', 'status', 'assignee'])
                ->where(function ($q) use ($query) {
                    $q->where('summary', 'ilike', "%{$query}%")
                      ->orWhere('description', 'ilike', "%{$query}%");
                })
                ->limit(20)
                ->get(),

            'pages' => Page::with(['space', 'author'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'ilike', "%{$query}%")
                      ->orWhere('body', 'ilike', "%{$query}%");
                })
                ->limit(20)
                ->get(),

            'projects' => Project::where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('key', 'ilike', "%{$query}%");
            })
                ->limit(20)
                ->get(),

            'spaces' => Space::where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('key', 'ilike', "%{$query}%");
            })
                ->limit(20)
                ->get(),
        ];

        return response()->json($results);
    }

    public function jqlExecute(Request $request)
    {
        $validated = $request->validate([
            'jql' => 'required|string',
        ]);

        $jql = $validated['jql'];
        $query = Issue::with(['project', 'type', 'status', 'assignee']);

        // Parse JQL
        if (preg_match('/project\s*=\s*(\w+)/', $jql, $matches)) {
            $project = Project::where('key', $matches[1])->first();
            if ($project) $query->where('project_id', $project->id);
        }

        if (preg_match('/status\s*=\s*"([^"]+)"/', $jql, $matches)) {
            $query->whereHas('status', function ($q) use ($matches) {
                $q->where('name', $matches[1]);
            });
        }

        if (preg_match('/assignee\s*=\s*currentUser\(\)/', $jql)) {
            $query->where('assigned_to', auth()->id());
        }

        if (preg_match('/reporter\s*=\s*currentUser\(\)/', $jql)) {
            $query->where('reporter_id', auth()->id());
        }

        if (preg_match('/priority\s*=\s*(\w+)/', $jql, $matches)) {
            $query->where('priority', $matches[1]);
        }

        if (preg_match('/order\s+by\s+(\w+)(\s+(asc|desc))?/', $jql, $matches)) {
            $query->orderBy($matches[1], $matches[3] ?? 'desc');
        }

        if (preg_match('/created\s*>=\s*(-?\d+d?)/', $jql, $matches)) {
            $query->where('created_at', '>=', now()->parse($matches[1]));
        }

        $issues = $query->limit(100)->get();

        return response()->json($issues);
    }
}
