<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $other;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->other = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    private function createGroupChat(): int
    {
        return $this->withToken($this->token)
            ->postJson('/api/v1/chat', [
                'name' => 'Team Chat',
                'type' => 'group',
                'user_ids' => [$this->other->id],
            ])
            ->assertStatus(201)
            ->json('id');
    }

    public function test_chats_require_authentication(): void
    {
        $this->getJson('/api/v1/chat/my')->assertStatus(401);
    }

    public function test_can_create_group_chat(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/chat', [
                'name' => 'Team Chat',
                'type' => 'group',
                'user_ids' => [$this->other->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Team Chat')
            ->assertJsonPath('type', 'group')
            ->assertJsonCount(2, 'members');

        $this->assertDatabaseHas('chat_members', [
            'chat_id' => $response->json('id'),
            'user_id' => $this->user->id,
            'role' => 'admin',
        ]);
    }

    public function test_can_list_my_chats(): void
    {
        $this->createGroupChat();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/chat/my');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_can_send_message(): void
    {
        $chatId = $this->createGroupChat();

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/chat/{$chatId}/messages", [
                'body' => 'Hello there',
            ]);

        $response->assertStatus(201)->assertJsonPath('body', 'Hello there');

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chatId,
            'sender_id' => $this->user->id,
            'body' => 'Hello there',
        ]);
    }

    public function test_can_list_messages(): void
    {
        $chatId = $this->createGroupChat();

        $this->withToken($this->token)
            ->postJson("/api/v1/chat/{$chatId}/messages", ['body' => 'First'])
            ->assertStatus(201);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/chat/{$chatId}/messages");

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_can_update_own_message(): void
    {
        $chatId = $this->createGroupChat();

        $messageId = $this->withToken($this->token)
            ->postJson("/api/v1/chat/{$chatId}/messages", ['body' => 'Original'])
            ->json('id');

        $response = $this->withToken($this->token)
            ->patchJson("/api/v1/chat/{$chatId}/messages/{$messageId}", [
                'body' => 'Edited',
            ]);

        $response->assertStatus(200)->assertJsonPath('body', 'Edited');

        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'body' => 'Edited',
        ]);
    }

    public function test_can_delete_own_message(): void
    {
        $chatId = $this->createGroupChat();

        $messageId = $this->withToken($this->token)
            ->postJson("/api/v1/chat/{$chatId}/messages", ['body' => 'To delete'])
            ->json('id');

        $this->withToken($this->token)
            ->deleteJson("/api/v1/chat/{$chatId}/messages/{$messageId}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Сообщение удалено');

        $this->assertSoftDeleted('messages', ['id' => $messageId]);
    }

    public function test_non_member_cannot_read_messages(): void
    {
        $chatId = $this->createGroupChat();

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/chat/{$chatId}/messages")
            ->assertStatus(403);
    }
}
