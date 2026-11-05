<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Requests\StoreIssueCommentRequest;
use App\Models\Issue;
use App\Models\IssueStatus;
use App\Models\IssueType;
use App\Models\IssueAttachment;
use App\Models\IssueComment;
use App\Models\IssueLink;
use App\Models\Project;
use App\Models\User;
use App\Models\ActivityLog;
use App\Support\HtmlSanitizer;
use App\Events\IssueUpdated;
use App\Events\NewComment;
use App\Events\BoardUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Issues",
 *     description="API Endpoints for Issues"
 * )
 */

class IssueController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $query = Issue::with(['project', 'type', 'status', 'assignee']);

        $user = Auth::user();
        if (!$user->isAdmin()) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->visibleTo($user);
            });
        }

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        // Filter by assignee
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('summary', 'ilike', "%{$request->search}%")
                  ->orWhere('description', 'ilike', "%{$request->search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function show(Request $request, string $issueId)
    {
        $issue = Issue::with([
            'project', 'type', 'status', 'assignee', 'reporter', 'creator',
            'subtasks', 'epic', 'comments.user', 'attachments.file',
            'links.source', 'links.target', 'links.creator',
            'incomingLinks.source', 'incomingLinks.target', 'incomingLinks.creator',
        ])->findOrFail($issueId);

        if (!Auth::user()->isAdmin() && !$issue->project?->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        return response()->json($issue);
    }

    public function store(StoreIssueRequest $request)
    {
        $validated = $request->validated();

        $project = Project::where('key', $validated['project_key'])->firstOrFail();

        $user = Auth::user();
        if (!$user->isAdmin() && !$project->hasPermission($user, 'create_issues')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        return DB::transaction(function () use ($validated, $project) {
            // Generate issue key using max sequence for this project
            $lastIssue = Issue::where('project_id', $project->id)
                ->orderByDesc('id')
                ->first();
            
            if ($lastIssue) {
                // Extract number from key like "TS-1" -> 1
                $parts = explode('-', $lastIssue->key);
                $sequence = intval(end($parts)) + 1;
            } else {
                $sequence = 1;
            }
            
            $key = "{$project->key}-{$sequence}";

            $issue = Issue::create([
                'project_id' => $project->id,
                'issue_type_id' => $validated['issue_type_id']
                    ?? IssueType::query()->orderBy('id')->value('id'),
                'status_id' => $validated['status_id']
                    ?? IssueStatus::query()->orderBy('sort_order')->orderBy('id')->value('id'),
                'created_by' => Auth::id(),
                'assigned_to' => $validated['assignee_id'] ?? null,
                'reporter_id' => Auth::id(),
                'parent_id' => $validated['parent_id'] ?? null,
                'epic_id' => $validated['epic_id'] ?? null,
                'summary' => $validated['summary'],
                'description' => HtmlSanitizer::clean($validated['description'] ?? null),
                'key' => $key,
                'priority' => $validated['priority'] ?? 'medium',
                'start_date' => $validated['start_date'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'story_points' => $validated['story_points'] ?? null,
                'custom_fields' => $validated['custom_fields'] ?? null,
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Issue::class,
                'subject_id' => $issue->id,
                'action' => 'created',
                'properties' => ['key' => $key, 'summary' => $validated['summary']],
            ]);

            return response()->json($issue->load([
                'project', 'type', 'status', 'assignee', 'reporter', 'creator',
            ]), 201);
        });
    }

    public function moveInBoard(Request $request, string $issueId)
    {
        $validated = $request->validate([
            'column_id' => 'nullable|exists:board_columns,id',
            'status_id' => 'nullable|exists:issue_statuses,id',
            'order' => 'nullable|integer',
        ]);

        $issue = Issue::findOrFail($issueId);

        // If status_id is provided directly (virtual columns like Review)
        if (!empty($validated['status_id'])) {
            $oldStatusId = $issue->status_id;
            $issue->update([
                'status_id' => $validated['status_id'],
            ]);
        } else {
            $column = \App\Models\BoardColumn::findOrFail($validated['column_id']);
            $board = $column->board;

            $oldStatusId = $issue->status_id;

            // Update issue status and sequence
            $issue->update([
                'status_id' => $column->status_id,
                'sequence' => $validated['order'],
            ]);

            // Broadcast board update
            event(new BoardUpdated($board, 'issue_moved', $validated['column_id'], $issue->id, $validated['order']));
        }

        // Broadcast issue update
        event(new IssueUpdated($issue, 'status_changed', $board->id ?? 0));

        return response()->json($issue->load(['project', 'type', 'status', 'assignee']));
    }

    public function update(UpdateIssueRequest $request, string $issueId)
    {
        $issue = Issue::findOrFail($issueId);

        $user = Auth::user();
        if (!$user->isAdmin() && !$issue->project->hasPermission($user, 'edit_issues')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validated();

        if (array_key_exists('description', $validated)) {
            $validated['description'] = HtmlSanitizer::clean($validated['description']);
        }

        // Map assignee_id to assigned_to
        if (isset($validated['assignee_id'])) {
            $validated['assigned_to'] = $validated['assignee_id'];
            unset($validated['assignee_id']);
        }

        $changes = [];
        $original = $issue->getAttributes();

        foreach ($validated as $key => $value) {
            if (data_get($original, $key) != $value) {
                $changes[$key] = [
                    'old' => data_get($original, $key),
                    'new' => $value,
                ];
            }
        }

        $issue->update($validated);

        if (!empty($changes)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Issue::class,
                'subject_id' => $issue->id,
                'action' => 'updated',
                'changes' => $changes,
            ]);
        }

        return response()->json($issue->load([
            'project', 'type', 'status', 'assignee', 'reporter', 'creator',
        ]));
    }

    public function destroy(Request $request, string $issueId)
    {
        $issue = Issue::findOrFail($issueId);

        if (!$issue->project->hasPermission(Auth::user(), 'delete_issues')) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $issue->delete();

        return response()->json(['message' => 'Issue deleted successfully']);
    }

    public function comments(Request $request, string $issueId)
    {
        $issue = Issue::findOrFail($issueId);
        if (!Auth::user()->isAdmin() && !$issue->project?->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $comments = IssueComment::where('issue_id', $issueId)
            ->with('user')
            ->latest()
            ->get();

        return response()->json($comments);
    }

    public function addComment(StoreIssueCommentRequest $request, string $issueId)
    {
        $issue = Issue::findOrFail($issueId);
        if (!Auth::user()->isAdmin() && !$issue->project?->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validated();

        $comment = IssueComment::create([
            'issue_id' => $issueId,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        event(new NewComment($comment, 'issue'));

        return response()->json($comment->load('user'), 201);
    }

    public function addAttachment(Request $request, string $issueId)
    {
        $issue = Issue::findOrFail($issueId);
        if (!Auth::user()->isAdmin() && !$issue->project?->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'file_id' => 'required|exists:files,id',
        ]);

        $attachment = IssueAttachment::create([
            'issue_id' => $issueId,
            'file_id' => $validated['file_id'],
            'uploaded_by' => Auth::id(),
        ]);

        return response()->json($attachment, 201);
    }

    public function linkIssue(Request $request, string $issueId)
    {
        $issue = Issue::findOrFail($issueId);
        if (!Auth::user()->isAdmin() && !$issue->project?->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'linked_issue_id' => 'required|exists:issues,id',
            'link_type' => 'nullable|string|max:50',
        ]);

        $link = IssueLink::create([
            'issue_id' => $issueId,
            'linked_issue_id' => $validated['linked_issue_id'],
            'link_type' => $validated['link_type'] ?? 'relates',
        ]);

        return response()->json($link, 201);
    }

    public function moveIssue(Request $request, string $issueId)
    {
        $validated = $request->validate([
            'status_id' => 'nullable|exists:issue_statuses,id',
            'column_id' => 'nullable|integer',
            'order' => 'nullable|integer',
        ]);

        $issue = Issue::findOrFail($issueId);

        if (!Auth::user()->isAdmin() && !$issue->project?->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        if (isset($validated['status_id'])) {
            $issue->update(['status_id' => $validated['status_id']]);
        }

        event(new IssueUpdated($issue, 'status_changed'));

        return response()->json($issue->load(['project', 'type', 'status', 'assignee']));
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'issue_ids' => 'required|array',
            'issue_ids.*' => 'exists:issues,id',
            'updates' => 'required|array',
        ]);

        $count = Issue::whereIn('id', $validated['issue_ids'])->update($validated['updates']);

        return response()->json(['message' => "{$count} issues updated"]);
    }

    public function transition(Request $request, string $issueId)
    {
        $validated = $request->validate([
            'comment_id' => 'nullable|exists:issue_comments,id',
        ]);

        $query = IssueComment::where('issue_id', $issueId);

        if (!empty($validated['comment_id'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('id', $validated['comment_id'])
                  ->orWhere('parent_comment_id', $validated['comment_id']);
            });
        }

        $query->where(function ($q) {
            $q->where('user_id', Auth::id())
              ->orWhereHas('issue', function ($query) {
                  $query->whereHas('project', function ($query) {
                      $query->whereHas('members', function ($query) {
                          $query->where('user_id', Auth::id())
                                ->where('role', 'admin');
                      });
                  });
              });
        });

        $comment = $query->firstOrFail();
        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }

    public function links(Request $request, string $issueId)
    {
        $links = IssueLink::with(['source', 'target', 'creator'])
            ->where(function ($q) use ($issueId) {
                $q->where('source_issue_id', $issueId)
                  ->orWhere('target_issue_id', $issueId);
            })
            ->get();

        return response()->json($links);
    }

    public function addLink(Request $request, string $issueId)
    {
        $validated = $request->validate([
            'target_issue_id' => 'required|exists:issues,id',
            'link_type' => 'required|string|in:blocks,is_blocked_by,relates_to,duplicates,is_related_to,clones',
        ]);

        $link = IssueLink::create([
            'source_issue_id' => $issueId,
            'target_issue_id' => $validated['target_issue_id'],
            'link_type' => $validated['link_type'],
            'created_by' => Auth::id(),
        ]);

        return response()->json($link->load(['source', 'target']), 201);
    }

    public function removeLink(Request $request, string $issueId, string $linkId)
    {
        $link = IssueLink::where('id', $linkId)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $link->delete();

        return response()->json(['message' => 'Link removed']);
    }
}
