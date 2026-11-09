<?php

namespace App\Http\Controllers\Api\Integration\OneC;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\User;
use App\Services\Integration\OneC\IssueSyncService;
use App\Services\Integration\OneC\MappingRepository;
use App\Services\Integration\OneC\NotificationSyncService;
use App\Services\Integration\OneC\UserSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inbound endpoints for the 1С integration. Guarded by the `one.c` middleware
 * (bearer token + HMAC signature). The 1С side calls these to push data; hence
 * they operate on the system level rather than a logged-in user.
 */
class OneCController extends Controller
{
    public function __construct(
        private UserSyncService $users,
        private IssueSyncService $issues,
        private NotificationSyncService $notifications,
        private MappingRepository $map,
    ) {
    }

    public function usersSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'users' => 'required|array',
            'users.*.external_id' => 'nullable',
            'users.*.email' => 'nullable|email',
            'users.*.first_name' => 'nullable|string',
            'users.*.last_name' => 'nullable|string',
        ]);

        $ids = [];
        foreach ($validated['users'] as $row) {
            $user = $this->users->upsertFromExternal($row);
            $ids[] = $user->id;
        }

        return response()->json(['synced' => count($ids), 'user_ids' => $ids]);
    }

    public function issuesPush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'issues' => 'required|array',
            'issues.*.project_key' => 'required|string',
            'issues.*.summary' => 'required|string',
            'issues.*.external_id' => 'nullable',
            'issues.*.description' => 'nullable|string',
            'issues.*.priority' => 'nullable|string',
            'issues.*.type' => 'nullable|string',
            'issues.*.status' => 'nullable|string',
            'issues.*.assignee_email' => 'nullable|email',
        ]);

        $created = [];
        $skipped = [];

        foreach ($validated['issues'] as $row) {
            $issue = $this->issues->upsertFromExternal($row);
            if ($issue) {
                $created[] = $issue->key;
            } else {
                $skipped[] = $row['external_id'] ?? $row['summary'] ?? 'unknown';
            }
        }

        return response()->json(['synced' => count($created), 'keys' => $created, 'skipped' => $skipped]);
    }

    public function issuesPull(Request $request): JsonResponse
    {
        $since = $request->query('since');

        return response()->json(['issues' => $this->issues->changedSince(is_string($since) ? $since : null)]);
    }

    public function issueComment(Request $request, string $externalId): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'author_email' => 'nullable|email',
        ]);

        $issueId = $this->map->findLocalId('issue', $externalId);
        if (!$issueId) {
            abort(404, 'Issue not found for external id');
        }

        $issue = Issue::findOrFail($issueId);

        $authorId = !empty($validated['author_email'])
            ? User::where('email', $validated['author_email'])->value('id')
            : null;

        $comment = IssueComment::create([
            'issue_id' => $issue->id,
            'user_id' => $authorId ?? $issue->created_by,
            'body' => $validated['body'],
        ]);

        return response()->json(['id' => $comment->id, 'issue_id' => $issue->id], 201);
    }

    public function notifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'user_email' => 'nullable|email',
            'type' => 'nullable|string',
            'message' => 'nullable|string',
            'data' => 'nullable|array',
        ]);

        $notification = $this->notifications->createFromExternal($validated);

        if (!$notification) {
            return response()->json(['message' => 'Recipient not resolved'], 422);
        }

        return response()->json(['id' => $notification->id], 201);
    }

    public function chatMessage(Request $request): JsonResponse
    {
        // A message coming from 1С is delivered to the recipient as a notification.
        $validated = $request->validate([
            'user_id' => 'nullable|integer',
            'user_email' => 'nullable|email',
            'text' => 'required|string',
        ]);

        $notification = $this->notifications->createFromExternal([
            'user_id' => $validated['user_id'] ?? null,
            'user_email' => $validated['user_email'] ?? null,
            'type' => 'chat',
            'data' => ['message' => $validated['text']],
        ]);

        if (!$notification) {
            return response()->json(['message' => 'Recipient not resolved'], 422);
        }

        return response()->json(['id' => $notification->id], 201);
    }

    public function fileUpload(Request $request): JsonResponse
    {
        return response()->json(['message' => 'File upload from 1C is not enabled'], 202);
    }

    public function kbPage(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Knowledgebase sync from 1C is not enabled'], 202);
    }
}
