<?php

namespace App\Console\Commands;

use App\Services\Integration\OneC\IssueSyncService;
use Illuminate\Console\Command;

class PullIssuesFromOneC extends Command
{
    protected $signature = 'one-c:pull-issues {--since= : ISO timestamp to pull changes after}';

    protected $description = 'Pull issues changed in 1С and upsert them into TasksBase';

    public function handle(IssueSyncService $service): int
    {
        $since = $this->option('since');
        $count = $service->pull(is_string($since) && $since !== '' ? $since : null);

        $this->info("Pulled {$count} issues from 1С");

        return self::SUCCESS;
    }
}
