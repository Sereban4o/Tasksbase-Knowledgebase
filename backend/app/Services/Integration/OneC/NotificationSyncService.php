<?php

namespace App\Services\Integration\OneC;

use App\Models\Notification;
use App\Models\User;

/**
 * Synchronises notifications between Laravel and 1С.
 */
class NotificationSyncService
{
    public function __construct(private OneCClient $client)
    {
    }

    public function push(Notification $notification): void
    {
        if (!$this->client->enabled()) {
            return;
        }

        $this->client->post('notifications', [
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'data' => $notification->data,
        ]);
    }

    public function createFromExternal(array $data): ?Notification
    {
        $userId = $data['user_id'] ?? null;
        if (!$userId && !empty($data['user_email'])) {
            $userId = User::where('email', (string) $data['user_email'])->value('id');
        }

        if (!$userId) {
            return null;
        }

        return Notification::create([
            'user_id' => (int) $userId,
            'type' => (string) ($data['type'] ?? 'one_c'),
            'data' => $data['data'] ?? ['message' => $data['message'] ?? 'Уведомление из 1С'],
            'read' => false,
        ]);
    }
}
