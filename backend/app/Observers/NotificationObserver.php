<?php

namespace App\Observers;

use App\Jobs\PushNotificationTo1C;
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

        // Mirror the notification to 1С when the integration is enabled.
        if (config('one_c.enabled')) {
            PushNotificationTo1C::dispatch($notification);
        }
    }
}
