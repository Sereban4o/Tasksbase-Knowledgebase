<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => strtoupper(fake()->bothify('??#')),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'type' => 'kanban',
            'visibility' => 'public',
            'created_by' => User::factory(),
        ];
    }
}
