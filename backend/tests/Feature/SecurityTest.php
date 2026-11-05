<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\File;
use App\Models\Page;
use App\Models\Project;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create(['role_id' => null]);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_admin_api_forbidden_for_non_admin(): void
    {
        $this->actingAs($this->otherUser, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    public function test_admin_api_allowed_for_admin(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(200);
    }

    public function test_private_board_is_not_accessible_by_non_member(): void
    {
        $project = Project::factory()->create([
            'created_by' => $this->user->id,
            'visibility' => 'private',
        ]);
        $project->members()->attach($this->user->id, ['role' => 'admin']);

        $board = Board::create([
            'project_id' => $project->id,
            'name' => 'Secret board',
            'type' => 'kanban',
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->otherUser, 'sanctum')
            ->getJson("/api/v1/tasksbase/boards/{$board->id}")
            ->assertStatus(403);

        // The board must not appear in the foreign user's list
        $this->actingAs($this->otherUser, 'sanctum')
            ->getJson('/api/v1/tasksbase/boards')
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_private_space_page_is_not_accessible_by_non_member(): void
    {
        $space = Space::create([
            'created_by' => $this->user->id,
            'key' => 'PRV',
            'name' => 'Private space',
            'type' => 'knowledge',
            'visibility' => 'private',
        ]);
        $space->members()->attach($this->user->id, ['role' => 'admin']);

        $page = Page::create([
            'space_id' => $space->id,
            'author_id' => $this->user->id,
            'title' => 'Secret page',
            'body' => '<p>secret</p>',
            'status' => 'draft',
            'version' => 1,
            'labels' => [],
        ]);

        $this->actingAs($this->otherUser, 'sanctum')
            ->getJson("/api/v1/knowledgebase/pages/{$page->id}")
            ->assertStatus(403);
    }

    public function test_file_requires_authentication(): void
    {
        $file = File::create([
            'uploaded_by' => $this->user->id,
            'original_name' => 'doc.txt',
            'stored_name' => 'doc-' . uniqid() . '.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
            'path' => 'files/doc.txt',
        ]);

        $this->getJson("/api/v1/files/{$file->id}")->assertStatus(401);
    }

    public function test_non_owner_cannot_delete_file(): void
    {
        $file = File::create([
            'uploaded_by' => $this->user->id,
            'original_name' => 'doc.txt',
            'stored_name' => 'doc-' . uniqid() . '.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
            'path' => 'files/doc.txt',
        ]);

        $this->actingAs($this->otherUser, 'sanctum')
            ->deleteJson("/api/v1/files/{$file->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('files', ['id' => $file->id]);
    }

    public function test_upload_rejects_disallowed_type(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('evil.html', 1, 'text/html');

        $this->withToken($this->token)
            ->postJson('/api/v1/files/upload', ['file' => $file])
            ->assertStatus(422);
    }

    public function test_page_body_is_sanitized_on_store(): void
    {
        $space = Space::create([
            'created_by' => $this->user->id,
            'key' => 'SAN',
            'name' => 'Sanitize',
            'type' => 'knowledge',
            'visibility' => 'team',
        ]);
        $space->members()->attach($this->user->id, ['role' => 'admin']);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/spaces/{$space->key}/pages", [
                'title' => 'XSS',
                'body' => '<p>ok</p><script>alert(1)</script><img src="x" onerror="alert(1)">',
            ]);

        $response->assertStatus(201);

        $page = Page::findOrFail($response->json('id'));
        $this->assertStringNotContainsString('<script', (string) $page->body);
        $this->assertStringNotContainsString('onerror', (string) $page->body);
        $this->assertStringContainsString('ok', (string) $page->body);
    }
}
