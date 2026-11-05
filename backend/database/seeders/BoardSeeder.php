<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\IssueStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        if (! $admin) {
            $this->command?->warn('Admin user not found. Run UserSeeder first.');

            return;
        }

        $project = Project::updateOrCreate(
            ['key' => 'TS'],
            [
                'name' => 'Tasksbase Demo',
                'description' => 'Демонстрационный проект Kanban',
                'type' => 'kanban',
                'visibility' => 'team',
                'created_by' => $admin->id,
            ]
        );

        // Ensure the creator is an admin member of the project
        $project->members()->syncWithoutDetaching([
            $admin->id => ['role' => 'admin'],
        ]);

        $board = Board::updateOrCreate(
            ['project_id' => $project->id, 'name' => 'Доска задач'],
            [
                'type' => 'kanban',
                'created_by' => $admin->id,
                'order' => 0,
            ]
        );

        $statuses = IssueStatus::query()->get()->keyBy('name');

        $columns = [
            ['name' => 'Backlog', 'status' => 'Backlog', 'wip_limit' => null],
            ['name' => 'In Progress', 'status' => 'In Progress', 'wip_limit' => 5],
            ['name' => 'In Review', 'status' => 'In Review', 'wip_limit' => 3],
            ['name' => 'Done', 'status' => 'Done', 'wip_limit' => null],
        ];

        foreach ($columns as $index => $column) {
            $board->columns()->updateOrCreate(
                ['name' => $column['name']],
                [
                    'status_id' => $statuses->get($column['status'])?->id,
                    'order' => $index,
                    'wip_limit' => $column['wip_limit'],
                ]
            );
        }

        $this->command?->info("Board \"{$board->name}\" seeded for project \"{$project->name}\".");
    }
}
