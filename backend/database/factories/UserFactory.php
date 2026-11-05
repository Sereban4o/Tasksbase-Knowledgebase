<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        
        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => 'active',
            'email_verified_at' => now(),
            'role_id' => $role->id,
        ];
    }
}
