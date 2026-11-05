<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueStatus;
use App\Models\IssueType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    protected $model = Issue::class;

    public function definition(): array
    {
        $issueType = IssueType::firstOrCreate(
            ['name' => 'Task'],
            ['is_epic' => false]
        );
        $status = IssueStatus::firstOrCreate(
            ['name' => 'To Do'],
            ['category' => 'todo']
        );
        $project = Project::factory();

        return [
            'project_id' => $project,
            'issue_type_id' => $issueType->id,
            'status_id' => $status->id,
            'created_by' => User::factory(),
            'assigned_to' => null,
            'reporter_id' => User::factory(),
            'summary' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'key' => strtoupper(fake()->bothify('??#')),
            'sequence' => fake()->numberBetween(1, 100),
            'priority' => fake()->randomElement(['lowest', 'low', 'medium', 'high', 'highest']),
            'start_date' => fake()->optional() ? fake()->date() : null,
            'due_date' => fake()->optional() ? fake()->date() : null,
            'story_points' => fake()->optional() ? fake()->numberBetween(1, 13) : null,
        ];
    }
}
