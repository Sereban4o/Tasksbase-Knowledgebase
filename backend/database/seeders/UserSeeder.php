<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin role if not exists
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator']
        );

        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'password' => bcrypt('password'),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'status' => 'active',
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]
        );

        // Regular users
        foreach (['john', 'jane', 'bob', 'alice'] as $i => $name) {
            User::updateOrCreate(
                ['email' => "{$name}@example.com"],
                [
                    'password' => bcrypt('password'),
                    'first_name' => ucfirst($name),
                    'last_name' => 'User',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
