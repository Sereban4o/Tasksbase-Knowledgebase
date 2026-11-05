<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Board::select('id', 'project_id', 'name', 'type', 'created_by', 'order')
            ->orderBy('created_at', 'desc');

        if (!$user->isAdmin()) {
            $projectIds = Project::visibleTo($user)->pluck('id');
            $query->whereIn('project_id', $projectIds);
        }

        return response()->json($query->get());
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

        if (!$this->userCanAccessBoard($board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $board->setRelation('columns', $board->columns->sortBy(function ($column) {
            return sprintf('%010d-%010d', $column->status?->sort_order ?? 999999999, $column->order ?? 0);
        })->values());

        return response()->json($board);
    }

    public function update(Request $request, string $boardId)
    {
        $board = Board::findOrFail($boardId);

        if (!$this->userCanAccessBoard($board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

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

        if (!$this->userCanAccessBoard($board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $board->delete();
        return response()->json(['message' => 'Board deleted']);
    }

    public function columns(Request $request, string $boardId)
    {
        $board = Board::findOrFail($boardId);

        if (!$this->userCanAccessBoard($board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $columns = BoardColumn::where('board_columns.board_id', $boardId)
            ->leftJoin('issue_statuses', 'issue_statuses.id', '=', 'board_columns.status_id')
            ->orderByRaw('issue_statuses.sort_order IS NULL')
            ->orderBy('issue_statuses.sort_order')
            ->orderBy('board_columns.order')
            ->select('board_columns.*')
            ->with('status')
            ->get();

        return response()->json($columns);
    }

    public function addColumn(Request $request, string $boardId)
    {
        $board = Board::findOrFail($boardId);

        if (!$this->userCanAccessBoard($board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

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

        if (!$this->userCanAccessBoard($column->board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

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

        if (!$this->userCanAccessBoard($column->board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $column->delete();
        return response()->json(['message' => 'Column deleted']);
    }

    public function reorderIssues(Request $request, string $boardId)
    {
        $board = Board::findOrFail($boardId);

        if (!$this->userCanAccessBoard($board)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

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

    private function userCanAccessBoard(Board $board): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isAdmin()
            || ($board->project && $board->project->canView($user));
    }
}
