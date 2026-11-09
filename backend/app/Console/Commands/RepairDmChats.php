<?php

namespace App\Console\Commands;

use App\Services\DmRepairService;
use Illuminate\Console\Command;

class RepairDmChats extends Command
{
    protected $signature = 'chat:repair-dm
        {--strategy=group : group|prune}
        {--dry-run : Show what would be changed without touching data}';

    protected $description = 'Repair direct-message chats that do not have exactly two members';

    public function handle(DmRepairService $service): int
    {
        $strategy = (string) $this->option('strategy');

        if (!in_array($strategy, ['group', 'prune'], true)) {
            $this->error('Invalid strategy. Allowed: group, prune.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $report = $service->run($strategy, $dryRun);

        $this->table(
            ['Scanned', 'Converted to group', 'Pruned', 'Deleted'],
            [[$report['scanned'], $report['converted'], $report['pruned'], $report['deleted']]],
        );

        $this->info($dryRun ? 'Dry run complete (no changes applied).' : 'Repair complete.');

        return self::SUCCESS;
    }
}
