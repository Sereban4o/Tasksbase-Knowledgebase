<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        $boards = Board::select('id', 'project_id', 'name', 'type', 'created_by', 'order')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($boards);
    }

    public function indexByProject(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();
        $boards = $project->boards()->with('columns')->get();
        return response()->json($boards);
    }

    public function store(Request $request, string $projectKey)
    {
        $project = \App\Models\Project::where('key', $projectKey)->firstOrFail();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:kanban,scrum',
        ]);

        $board = $project->boards()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'created_by' => Auth::id(),
        ]);

        return response()->json($board->load('columns'), 201);
    }

    public function show(Request $request, string $boardId)
    {
        $board = Board::with(['columns.status', 'project', 'creator'])->findOrFail($boardId);
        return response()->json($board);
    }

    public function update(Request $request, string $boardId)
    {
        $board = Board::findOrFail($boardId);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'filter_query' => 'nullable|array',
            'swimlanes' => 'nullable|array',
        ]);
        $board->update($validated);
        return response()->json($board);
    }

    public function destroy(Request $request, string $boardId)
    {
        $board = Board::findOrFail($boardId);
        $board->delete();
        return response()->json(['message' => 'Board deleted']);
    }

    public function columns(Request $request, string $boardId)
    {
        $columns = BoardColumn::where('board_id', $boardId)
            ->with('status')
            ->orderBy('order')
            ->get();
        return response()->json($columns);
    }

    public function addColumn(Request $request, string $boardId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status_id' => 'nullable|exists:issue_statuses,id',
            'wip_limit' => 'nullable|integer|min:1',
        ]);

        $maxOrder = BoardColumn::where('board_id', $boardId)->max('order') ?? 0;

        $column = BoardColumn::create([
            'board_id' => $boardId,
            'name' => $validated['name'],
            'status_id' => $validated['status_id'] ?? null,
            'order' => $maxOrder + 1,
            'wip_limit' => $validated['wip_limit'] ?? null,
        ]);

        return response()->json($column, 201);
    }

    public function updateColumn(Request $request, string $boardId, string $columnId)
    {
        $column = BoardColumn::where('board_id', $boardId)->findOrFail($columnId);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status_id' => 'sometimes|nullable|exists:issue_statuses,id',
            'wip_limit' => 'sometimes|nullable|integer|min:1',
        ]);
        $column->update($validated);
        return response()->json($column);
    }

    public function deleteColumn(Request $request, string $boardId, string $columnId)
    {
        $column = BoardColumn::where('board_id', $boardId)->findOrFail($columnId);
        $column->delete();
        return response()->json(['message' => 'Column deleted']);
    }

    public function reorderIssues(Request $request, string $boardId)
    {
        $validated = $request->validate([
            'column_issues' => 'required|array',
            'column_issues.*.issue_id' => 'required|exists:issues,id',
            'column_issues.*.order' => 'required|integer',
        ]);

        foreach ($validated['column_issues'] as $columnData) {
            Issue::where('id', $columnData['issue_id'])->update([
                'sequence' => $columnData['order'],
            ]);
        }

        return response()->json(['message' => 'Issues reordered']);
    }
}
