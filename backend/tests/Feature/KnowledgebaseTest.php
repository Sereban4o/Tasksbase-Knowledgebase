<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgebaseTest extends TestCase
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

    private function createSpace(string $key = 'KB'): array
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/knowledgebase/spaces', [
                'name' => 'Knowledge Base',
                'key' => $key,
                'type' => 'knowledge',
                'visibility' => 'team',
            ]);

        return [$response, $key];
    }

    public function test_spaces_require_authentication(): void
    {
        $this->getJson('/api/v1/knowledgebase/spaces')->assertStatus(401);
    }

    public function test_can_create_space(): void
    {
        [$response, $key] = $this->createSpace('kb');

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Knowledge Base')
            ->assertJsonPath('key', strtoupper($key));

        $this->assertDatabaseHas('spaces', [
            'key' => strtoupper($key),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_list_spaces(): void
    {
        $this->createSpace('KB1');
        $this->createSpace('KB2');

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/knowledgebase/spaces');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_can_show_space(): void
    {
        [$response] = $this->createSpace('KBS');
        $key = $response->json('key');

        $this->withToken($this->token)
            ->getJson("/api/v1/knowledgebase/spaces/{$key}")
            ->assertStatus(200)
            ->assertJsonPath('key', $key);
    }

    public function test_can_create_page_in_space(): void
    {
        [$response] = $this->createSpace('KBP');
        $key = $response->json('key');

        $page = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/spaces/{$key}/pages", [
                'title' => 'Getting Started',
                'body' => '<p>Hello world</p>',
                'status' => 'draft',
            ]);

        $page->assertStatus(201)
            ->assertJsonPath('title', 'Getting Started')
            ->assertJsonPath('version', 1);

        $this->assertDatabaseHas('pages', [
            'title' => 'Getting Started',
            'space_id' => $response->json('id'),
        ]);
    }

    public function test_can_list_space_pages(): void
    {
        [$response] = $this->createSpace('KBL');
        $key = $response->json('key');

        $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/spaces/{$key}/pages", ['title' => 'Page A'])
            ->assertStatus(201);

        $this->withToken($this->token)
            ->getJson("/api/v1/knowledgebase/spaces/{$key}/pages")
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_update_page(): void
    {
        [$response] = $this->createSpace('KBU');
        $key = $response->json('key');

        $pageId = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/spaces/{$key}/pages", ['title' => 'Original'])
            ->json('id');

        $response = $this->withToken($this->token)
            ->patchJson("/api/v1/knowledgebase/pages/{$pageId}", [
                'title' => 'Updated',
                'body' => '<p>new body</p>',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('title', 'Updated')
            ->assertJsonPath('version', 2);
    }

    public function test_can_delete_page(): void
    {
        [$response] = $this->createSpace('KBD');
        $key = $response->json('key');

        $pageId = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/spaces/{$key}/pages", ['title' => 'Temp'])
            ->json('id');

        $this->withToken($this->token)
            ->deleteJson("/api/v1/knowledgebase/pages/{$pageId}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Page deleted');

        $this->assertSoftDeleted('pages', ['id' => $pageId]);
    }

    public function test_can_attach_file_to_page(): void
    {
        [$spaceResponse] = $this->createSpace('KBA');
        $key = $spaceResponse->json('key');

        $pageId = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/spaces/{$key}/pages", ['title' => 'With file'])
            ->json('id');

        $file = File::create([
            'uploaded_by' => $this->user->id,
            'original_name' => 'doc.txt',
            'stored_name' => 'doc-' . uniqid() . '.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
            'path' => 'files/doc.txt',
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/pages/{$pageId}/attachments", [
                'file_id' => $file->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('file.original_name', 'doc.txt');

        $this->assertDatabaseHas('page_attachments', [
            'page_id' => $pageId,
            'file_id' => $file->id,
        ]);
    }

    public function test_can_list_and_remove_page_attachment(): void
    {
        [$spaceResponse] = $this->createSpace('KBB');
        $key = $spaceResponse->json('key');

        $pageId = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/spaces/{$key}/pages", ['title' => 'Files'])
            ->json('id');

        $file = File::create([
            'uploaded_by' => $this->user->id,
            'original_name' => 'report.pdf',
            'stored_name' => 'report-' . uniqid() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'path' => 'files/report.pdf',
        ]);

        $attachId = $this->withToken($this->token)
            ->postJson("/api/v1/knowledgebase/pages/{$pageId}/attachments", [
                'file_id' => $file->id,
            ])
            ->json('id');

        $this->withToken($this->token)
            ->getJson("/api/v1/knowledgebase/pages/{$pageId}/attachments")
            ->assertStatus(200)
            ->assertJsonCount(1);

        $this->withToken($this->token)
            ->deleteJson("/api/v1/knowledgebase/pages/{$pageId}/attachments/{$attachId}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Attachment removed');

        $this->assertDatabaseMissing('page_attachments', ['id' => $attachId]);
    }
}
