<?php

namespace Tests\Feature;

use App\Models\IssueStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_status_sort_order(): void
    {
        $admin = User::factory()->create();

        $status = IssueStatus::create([
            'name' => 'Todo',
            'category' => 'todo',
            'color' => '#123456',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/statuses/{$status->id}", [
            'name' => 'Todo',
            'category' => 'todo',
            'color' => '#123456',
            'sort_order' => 7,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('issue_statuses', [
            'id' => $status->id,
            'sort_order' => 7,
        ]);
    }

    public function test_non_admin_cannot_manage_statuses(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $status = IssueStatus::create([
            'name' => 'Todo',
            'category' => 'todo',
            'color' => '#123456',
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->patch("/admin/statuses/{$status->id}", [
                'name' => 'Todo',
                'category' => 'todo',
                'color' => '#123456',
                'sort_order' => 99,
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('issue_statuses', [
            'id' => $status->id,
            'sort_order' => 0,
        ]);
    }
}
