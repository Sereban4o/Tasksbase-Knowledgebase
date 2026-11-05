<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\NtfyService;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        $data = (array) ($notification->data ?? []);
        $summary = $data['summary'] ?? $data['message'] ?? $notification->type ?? 'Уведомление';

        app(NtfyService::class)->notifyUser(
            (int) $notification->user_id,
            'Tasksbase',
            (string) $summary,
        );
    }
}
