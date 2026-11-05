<?php

namespace Database\Seeders;

use App\Models\IssueStatus;
use Illuminate\Database\Seeder;

class IssueStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Backlog', 'category' => 'todo', 'color' => '#6b7280'],
            ['name' => 'In Progress', 'category' => 'in_progress', 'color' => '#3b82f6'],
            ['name' => 'In Review', 'category' => 'in_progress', 'color' => '#8b5cf6'],
            ['name' => 'Done', 'category' => 'done', 'color' => '#10b981'],
            ['name' => 'Closed', 'category' => 'done', 'color' => '#059669'],
        ];

        foreach ($statuses as $status) {
            IssueStatus::updateOrCreate(
                ['name' => $status['name']],
                [
                    'category' => $status['category'],
                    'color' => $status['color'],
                ]
            );
        }
    }
}
