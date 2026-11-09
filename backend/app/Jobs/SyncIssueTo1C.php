<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Services\Integration\OneC\IssueSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncIssueTo1C implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Issue $issue)
    {
    }

    public function handle(IssueSyncService $service): void
    {
        // 1С pulls changed issues via the /integration/1c/issues/pull endpoint;
        // this job simply marks the issue for the next pull by touching a mapping.
        $service->markForSync($this->issue);
    }
}
