<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            IssueTypeSeeder::class,
            IssueStatusSeeder::class,
            PermissionSeeder::class,
            BoardSeeder::class,
        ]);
    }
}
