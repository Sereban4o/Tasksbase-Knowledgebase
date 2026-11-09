<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Integration\OneC\NotificationSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushNotificationTo1C implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Notification $notification)
    {
    }

    public function handle(NotificationSyncService $service): void
    {
        $service->push($this->notification);
    }
}
