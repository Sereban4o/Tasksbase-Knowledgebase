<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_projects_require_authentication(): void
    {
        $this->getJson('/api/v1/tasksbase/projects')->assertStatus(401);
    }

    public function test_can_list_projects(): void
    {
        Project::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/tasksbase/projects');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_can_create_project(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/tasksbase/projects', [
                'name' => 'New Project',
                'key' => 'np',
                'type' => 'kanban',
                'visibility' => 'team',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('key', 'NP')
            ->assertJsonPath('name', 'New Project');

        $this->assertDatabaseHas('projects', [
            'key' => 'NP',
            'created_by' => $this->user->id,
        ]);

        // Creator is attached as project admin
        $this->assertDatabaseHas('project_members', [
            'project_id' => $response->json('id'),
            'user_id' => $this->user->id,
            'role' => 'admin',
        ]);
    }

    public function test_can_show_project(): void
    {
        $project = Project::factory()->create([
            'created_by' => $this->user->id,
            'visibility' => 'public',
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/tasksbase/projects/{$project->key}");

        $response->assertStatus(200)
            ->assertJsonPath('key', $project->key);
    }

    public function test_can_update_project(): void
    {
        $project = Project::factory()->create([
            'created_by' => $this->user->id,
            'visibility' => 'team',
        ]);
        $project->members()->attach($this->user->id, ['role' => 'admin']);

        $response = $this->withToken($this->token)
            ->patchJson("/api/v1/tasksbase/projects/{$project->key}", [
                'name' => 'Renamed Project',
            ]);

        $response->assertStatus(200)->assertJsonPath('name', 'Renamed Project');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Renamed Project',
        ]);
    }

    public function test_non_owner_cannot_delete_project(): void
    {
        $project = Project::factory()->create([
            'created_by' => User::factory()->create()->id,
        ]);

        $this->withToken($this->token)
            ->deleteJson("/api/v1/tasksbase/projects/{$project->key}")
            ->assertStatus(403);
    }

    public function test_can_delete_owned_project(): void
    {
        $project = Project::factory()->create(['created_by' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/v1/tasksbase/projects/{$project->key}");

        $response->assertStatus(200)->assertJsonPath('message', 'Project deleted');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
