<?php

namespace App\Services\Integration\OneC;

use App\Models\Issue;
use App\Models\IssueStatus;
use App\Models\IssueType;
use App\Models\Project;
use App\Models\User;

/**
 * Synchronises issues (tasks) between Laravel and 1С.
 */
class IssueSyncService
{
    private const PRIORITIES = ['lowest', 'low', 'medium', 'high', 'highest'];

    public function __construct(
        private MappingRepository $map,
        private OneCClient $client,
    ) {
    }

    public function upsertFromExternal(array $data): ?Issue
    {
        $externalId = trim((string) ($data['external_id'] ?? $data['id'] ?? ''));
        $projectKey = trim((string) ($data['project_key'] ?? ''));

        $project = $projectKey !== '' ? Project::where('key', $projectKey)->first() : null;
        if (!$project) {
            return null;
        }

        $existingId = $externalId !== '' ? $this->map->findLocalId('issue', $externalId) : null;
        $issue = $existingId ? Issue::find($existingId) : null;

        $assigneeId = $this->resolveUserId($data['assignee_email'] ?? null);

        if ($issue) {
            $issue->fill([
                'summary' => (string) ($data['summary'] ?? $issue->summary),
                'description' => $data['description'] ?? $issue->description,
                'priority' => $this->resolvePriority($data['priority'] ?? $issue->priority),
                'assigned_to' => $assigneeId ?? $issue->assigned_to,
            ])->save();
        } else {
            $type = $this->resolveType($data['type'] ?? null);
            $status = $this->resolveStatus($data['status'] ?? null);

            $sequence = ((int) Issue::where('project_id', $project->id)->max('sequence')) + 1;

            $issue = Issue::create([
                'project_id' => $project->id,
                'issue_type_id' => $type->id,
                'status_id' => $status->id,
                'created_by' => $project->created_by,
                'assigned_to' => $assigneeId,
                'summary' => (string) ($data['summary'] ?? 'Без названия'),
                'description' => $data['description'] ?? null,
                'priority' => $this->resolvePriority($data['priority'] ?? 'medium'),
                'key' => $project->key . '-' . $sequence,
                'sequence' => $sequence,
            ]);
        }

        if ($externalId !== '') {
            $this->map->link('issue', $issue->id, $externalId, ['synced_at' => now()->toIso8601String()]);
        }

        return $issue;
    }

    /**
     * Issues changed after the given ISO timestamp, for 1С to pull.
     */    public function changedSince(?string $since): array
    {
        $issues = Issue::query()
            ->when($since, fn ($q) => $q->where('updated_at', '>', $since))
            ->with('project:id,key')
            ->limit(500)
            ->get();

        // Batch-load mappings in one query instead of one query per issue.
        $externalIds = $this->map->externalIdsFor('issue', $issues->pluck('id')->all());

        return $issues
            ->map(fn (Issue $issue) => [
                'external_id' => $externalIds[$issue->id] ?? null,
                'project_key' => $issue->project?->key,
                'key' => $issue->key,
                'summary' => $issue->summary,
                'description' => $issue->description,
                'priority' => $issue->priority,
                'updated_at' => $issue->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    public function markForSync(Issue $issue): void
    {
        // 1С pulls by updated_at; touch so the change is picked up on the next pull.
        $issue->touch();
    }

    /**
     * Pull issues changed in 1С and upsert them locally.
     */
    public function pull(?string $since = null): int
    {
        $response = $this->client->get('issues', array_filter(['since' => $since]));
        $rows = is_array($response) ? ($response['issues'] ?? $response) : [];

        $count = 0;
        foreach ((array) $rows as $row) {
            if (is_array($row) && $this->upsertFromExternal($row)) {
                $count++;
            }
        }

        return $count;
    }

    private function resolveType(?string $name): IssueType
    {
        $name = trim((string) $name);

        if ($name !== '') {
            $type = IssueType::where('name', 'ilike', $name)->first();
            if ($type) {
                return $type;
            }
        }

        return IssueType::first() ?? IssueType::create(['name' => $name !== '' ? $name : 'Task']);
    }

    private function resolveStatus(?string $name): IssueStatus
    {
        $name = trim((string) $name);

        if ($name !== '') {
            $status = IssueStatus::where('name', 'ilike', $name)->first();
            if ($status) {
                return $status;
            }
        }

        return IssueStatus::first() ?? IssueStatus::create(['name' => $name !== '' ? $name : 'To Do', 'category' => 'todo']);
    }

    private function resolvePriority(?string $priority): string
    {
        $priority = strtolower(trim((string) $priority));

        return in_array($priority, self::PRIORITIES, true) ? $priority : 'medium';
    }

    private function resolveUserId(?string $email): ?int
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }

        return User::where('email', $email)->value('id');
    }
}
