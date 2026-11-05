<?php

namespace Database\Seeders;

use App\Models\IssueType;
use Illuminate\Database\Seeder;

class IssueTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Epic', 'icon' => '🟣', 'color' => '#6366f1', 'is_epic' => true, 'is_subtask' => false],
            ['name' => 'Story', 'icon' => '📖', 'color' => '#3b82f6', 'is_epic' => false, 'is_subtask' => false],
            ['name' => 'Task', 'icon' => '📋', 'color' => '#10b981', 'is_epic' => false, 'is_subtask' => false],
            ['name' => 'Bug', 'icon' => '🐛', 'color' => '#ef4444', 'is_epic' => false, 'is_subtask' => false],
            ['name' => 'Sub-task', 'icon' => '📌', 'color' => '#6b7280', 'is_epic' => false, 'is_subtask' => true],
            ['name' => 'Spike', 'icon' => '🔬', 'color' => '#f59e0b', 'is_epic' => false, 'is_subtask' => false],
        ];

        foreach ($types as $type) {
            IssueType::updateOrCreate(
                ['name' => $type['name']],
                [
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'is_epic' => $type['is_epic'],
                    'is_subtask' => $type['is_subtask'],
                ]
            );
        }
    }
}
