<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\IssueStatus;
use App\Models\IssueType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['created_by' => $this->user->id]);
        // Add user as admin member of the project (required for permission checks)
        $this->project->members()->attach($this->user->id, ['role' => 'admin']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_list_issues(): void
    {
        Issue::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withHeader('Accept', 'application/json')
            ->withToken($this->token)
            ->get('/api/v1/tasksbase/issues');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_issues_by_project(): void
    {
        Issue::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);

        $otherProject = Project::factory()->create();
        Issue::factory()->count(2)->create([
            'project_id' => $otherProject->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/tasksbase/issues?project_id=' . $this->project->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_issues(): void
    {
        Issue::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'summary' => 'Test Issue Summary',
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/tasksbase/issues?search=Test');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_single_issue(): void
    {
        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/tasksbase/issues/{$issue->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $issue->id)
            ->assertJsonPath('key', $issue->key);
    }

    public function test_can_create_issue(): void
    {
        $issueType = IssueType::create(['name' => 'Task', 'is_epic' => false]);
        $status = IssueStatus::create(['name' => 'To Do', 'category' => 'todo']);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/tasksbase/issues', [
                'project_key' => $this->project->key,
                'issue_type_id' => $issueType->id,
                'status_id' => $status->id,
                'summary' => 'New Test Issue',
                'description' => 'Test Description',
                'priority' => 'medium',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('summary', 'New Test Issue')
            ->assertJsonPath('project_id', $this->project->id);

        $this->assertDatabaseHas('issues', [
            'summary' => 'New Test Issue',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_can_update_issue(): void
    {
        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/v1/tasksbase/issues/{$issue->id}", [
                'summary' => 'Updated Summary',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('summary', 'Updated Summary');

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'summary' => 'Updated Summary',
        ]);
    }

    public function test_can_delete_issue(): void
    {
        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/v1/tasksbase/issues/{$issue->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Issue deleted successfully');

        $this->assertSoftDeleted('issues', ['id' => $issue->id]);
    }

    public function test_can_add_comment_to_issue(): void
    {
        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/tasksbase/issues/{$issue->id}/comments", [
                'body' => 'Test comment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('body', 'Test comment');

        $this->assertDatabaseHas('issue_comments', [
            'issue_id' => $issue->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_move_issue_in_board(): void
    {
        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
        ]);

        $status = IssueStatus::firstOrCreate(['name' => 'In Progress'], ['category' => 'in_progress']);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/tasksbase/issues/{$issue->id}/move", [
                'status_id' => $status->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status_id', $status->id);
    }
}
