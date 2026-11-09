<?php

namespace App\Services;

use App\Models\Chat;

/**
 * Repairs direct-message chats that do not have exactly two members.
 *
 * Historical data (e.g. a DM that accidentally received extra members) makes the
 * DM de-duplication in ChatController::store() return the same chat to many users.
 * This service converts such chats into groups (default) or prunes them down to two
 * members, and removes empty/broken DMs.
 */
class DmRepairService
{
    /**
     * @return array{scanned:int,converted:int,pruned:int,deleted:int}
     */
    public function run(string $strategy = 'group', bool $dryRun = false): array
    {
        $report = [
            'scanned' => 0,
            'converted' => 0,
            'pruned' => 0,
            'deleted' => 0,
        ];

        Chat::where('type', 'dm')
            ->withCount('members')
            ->chunkById(100, function ($chats) use ($strategy, $dryRun, &$report) {
                foreach ($chats as $chat) {
                    $count = (int) $chat->members_count;

                    if ($count === 2) {
                        continue;
                    }

                    $report['scanned']++;

                    if ($count > 2) {
                        if ($strategy === 'prune') {
                            if (!$dryRun) {
                                $this->prune($chat);
                            }
                            $report['pruned']++;
                        } else {
                            if (!$dryRun) {
                                $chat->update([
                                    'type' => 'group',
                                    'name' => $chat->name ?: "Групповой чат #{$chat->id}",
                                ]);
                            }
                            $report['converted']++;
                        }
                    } else {
                        // 0 or 1 member: a DM with fewer than two people is unusable.
                        if (!$dryRun) {
                            $chat->delete();
                        }
                        $report['deleted']++;
                    }
                }
            });

        return $report;
    }

    /**
     * Keep the creator and the oldest member, detach everyone else.
     */
    private function prune(Chat $chat): void
    {
        $keepIds = collect([$chat->created_by]);

        $oldest = $chat->members()->orderBy('chat_members.created_at')->first();
        if ($oldest) {
            $keepIds->push($oldest->id);
        }

        $keep = $keepIds->filter()->unique()->take(2);

        $remove = $chat->members()
            ->pluck('users.id')
            ->reject(fn ($id) => $keep->contains($id))
            ->all();

        if (!empty($remove)) {
            $chat->members()->detach($remove);
        }

        if ($chat->members()->count() < 2) {
            $chat->delete();
        }
    }
}
