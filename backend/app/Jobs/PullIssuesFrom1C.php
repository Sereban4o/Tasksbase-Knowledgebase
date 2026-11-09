<?php

namespace App\Jobs;

use App\Services\Integration\OneC\IssueSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PullIssuesFrom1C implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $since = null)
    {
    }

    public function handle(IssueSyncService $service): void
    {
        $service->pull($this->since);
    }
}
