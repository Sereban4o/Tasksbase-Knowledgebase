<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\IssueStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['created_by' => $this->user->id]);
        $this->project->members()->attach($this->user->id, ['role' => 'admin']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    private function makeBoard(array $attributes = []): Board
    {
        return Board::create(array_merge([
            'project_id' => $this->project->id,
            'name' => 'Test Board',
            'type' => 'kanban',
            'created_by' => $this->user->id,
        ], $attributes));
    }

    public function test_boards_require_authentication(): void
    {
        $this->getJson('/api/v1/tasksbase/boards')->assertStatus(401);
    }

    public function test_can_list_boards(): void
    {
        $this->makeBoard();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/tasksbase/boards');

        $response->assertStatus(200)->assertJsonCount(1);
    }

    public function test_can_create_board(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/tasksbase/boards', [
                'project_key' => $this->project->key,
                'name' => 'Sprint Board',
                'type' => 'scrum',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Sprint Board')
            ->assertJsonPath('type', 'scrum')
            ->assertJsonPath('project_id', $this->project->id);

        $this->assertDatabaseHas('boards', [
            'name' => 'Sprint Board',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_can_show_board_with_columns(): void
    {
        $board = $this->makeBoard();

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/tasksbase/boards/{$board->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $board->id)
            ->assertJsonPath('name', 'Test Board');
    }

    public function test_can_list_board_columns(): void
    {
        $board = $this->makeBoard();
        $status = IssueStatus::create([
            'name' => 'Backlog',
            'category' => 'todo',
            'color' => '#6b7280',
        ]);

        BoardColumn::create([
            'board_id' => $board->id,
            'name' => 'Backlog',
            'status_id' => $status->id,
            'order' => 0,
        ]);
        BoardColumn::create([
            'board_id' => $board->id,
            'name' => 'Done',
            'order' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/tasksbase/boards/{$board->id}/columns");

        $response->assertStatus(200)->assertJsonCount(2);
        $response->assertJsonPath('0.name', 'Backlog');
    }

    public function test_can_update_board(): void
    {
        $board = $this->makeBoard();

        $response = $this->withToken($this->token)
            ->patchJson("/api/v1/tasksbase/boards/{$board->id}", [
                'name' => 'Renamed Board',
            ]);

        $response->assertStatus(200)->assertJsonPath('name', 'Renamed Board');
    }

    public function test_can_delete_board(): void
    {
        $board = $this->makeBoard();

        $response = $this->withToken($this->token)
            ->deleteJson("/api/v1/tasksbase/boards/{$board->id}");

        $response->assertStatus(200)->assertJsonPath('message', 'Board deleted');

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }
}
