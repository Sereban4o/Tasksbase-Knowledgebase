<?php

namespace Tests\Feature;

use App\Events\ChatDeleted;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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

        $raw = DB::table('messages')
            ->where('chat_id', $chatId)
            ->where('sender_id', $this->user->id)
            ->value('body');

        $this->assertNotSame('Hello there', $raw);
        $this->assertSame('Hello there', Crypt::decryptString($raw));
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

        $raw = DB::table('messages')->where('id', $messageId)->value('body');

        $this->assertNotSame('Edited', $raw);
        $this->assertSame('Edited', Crypt::decryptString($raw));
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

    public function test_message_body_is_encrypted_at_rest(): void
    {
        $chatId = $this->createGroupChat();

        $this->withToken($this->token)
            ->postJson("/api/v1/chat/{$chatId}/messages", ['body' => 'super secret text'])
            ->assertStatus(201)
            ->assertJsonPath('body', 'super secret text');

        $raw = DB::table('messages')->where('chat_id', $chatId)->value('body');

        $this->assertNotSame('super secret text', $raw);
        $this->assertSame('super secret text', Crypt::decryptString($raw));
    }

    public function test_message_created_broadcasts_to_member_user_channels(): void
    {
        $chatId = $this->createGroupChat();

        $message = Message::create([
            'chat_id' => $chatId,
            'sender_id' => $this->user->id,
            'body' => 'hello',
            'type' => 'text',
        ]);

        $event = new \App\Events\MessageCreated($message, Chat::findOrFail($chatId));
        $channels = array_map(fn ($channel) => $channel->name, $event->broadcastOn());

        $this->assertContains("private-chat.{$chatId}", $channels);
        $this->assertContains('private-user.' . $this->other->id, $channels);
        $this->assertNotContains('private-user.' . $this->user->id, $channels);
    }

    public function test_chat_members_payload_exposes_profile_fields(): void
    {
        $this->createGroupChat();

        $response = $this->withToken($this->token)->getJson('/api/v1/chat/my');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'members' => [
                            ['id', 'email', 'status', 'pivot' => ['role']],
                        ],
                    ],
                ],
            ]);
    }

    public function test_member_can_leave_group(): void
    {
        $chatId = $this->createGroupChat();

        $this->actingAs($this->other, 'sanctum')
            ->postJson("/api/v1/chat/{$chatId}/leave")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Вы покинули чат');

        $this->assertDatabaseMissing('chat_members', [
            'chat_id' => $chatId,
            'user_id' => $this->other->id,
        ]);

        $this->actingAs($this->other, 'sanctum')
            ->getJson("/api/v1/chat/{$chatId}/messages")
            ->assertStatus(403);
    }

    public function test_admin_role_is_transferred_when_admin_leaves(): void
    {
        $chatId = $this->createGroupChat();

        $this->withToken($this->token)
            ->postJson("/api/v1/chat/{$chatId}/leave")
            ->assertStatus(200);

        $this->assertDatabaseHas('chat_members', [
            'chat_id' => $chatId,
            'user_id' => $this->other->id,
            'role' => 'admin',
        ]);
    }

    public function test_any_member_can_delete_chat_for_everyone(): void
    {
        $chatId = $this->createGroupChat();

        $this->actingAs($this->other, 'sanctum')
            ->deleteJson("/api/v1/chat/{$chatId}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Чат удалён');

        $this->assertSoftDeleted('chats', ['id' => $chatId]);
        $this->assertDatabaseMissing('chat_members', ['chat_id' => $chatId]);

        $this->withToken($this->token)
            ->getJson('/api/v1/chat/my')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_non_member_cannot_delete_chat(): void
    {
        $chatId = $this->createGroupChat();

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/chat/{$chatId}")
            ->assertStatus(403);

        $this->assertDatabaseHas('chats', ['id' => $chatId, 'deleted_at' => null]);
    }

    public function test_chat_deleted_event_broadcasts_to_chat_and_member_channels(): void
    {
        $event = new ChatDeleted(5, 'group', 'Team', [$this->user->id, $this->other->id]);
        $channels = array_map(fn ($channel) => $channel->name, $event->broadcastOn());

        $this->assertContains('private-chat.5', $channels);
        $this->assertContains('private-user.' . $this->user->id, $channels);
        $this->assertContains('private-user.' . $this->other->id, $channels);
    }

    public function test_dm_reuses_existing_two_member_chat(): void
    {
        $first = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/chat', ['type' => 'dm', 'user_ids' => [$this->other->id]]);

        $first->assertStatus(201);
        $chatId = $first->json('id');

        $second = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/chat', ['type' => 'dm', 'user_ids' => [$this->other->id]]);

        $second->assertStatus(200)->assertJsonPath('id', $chatId);
    }

    public function test_dm_does_not_reuse_multi_member_historical_chat(): void
    {
        $third = User::factory()->create();

        $broken = Chat::create(['created_by' => $this->user->id, 'type' => 'dm']);
        $broken->members()->attach([
            $this->user->id => ['role' => 'admin'],
            $this->other->id => ['role' => 'member'],
            $third->id => ['role' => 'member'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/chat', ['type' => 'dm', 'user_ids' => [$this->other->id]]);

        $response->assertStatus(201);
        $this->assertNotSame($broken->id, $response->json('id'));
    }

    public function test_dm_requires_exactly_one_recipient(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/chat', ['type' => 'dm', 'user_ids' => []])
            ->assertStatus(422);

        $second = User::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/chat', ['type' => 'dm', 'user_ids' => [$this->other->id, $second->id]])
            ->assertStatus(422);
    }

    public function test_dm_cannot_target_self(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/chat', ['type' => 'dm', 'user_ids' => [$this->user->id]])
            ->assertStatus(422);
    }

    public function test_repair_command_converts_multi_member_dm_to_group(): void
    {
        $third = User::factory()->create();

        $broken = Chat::create(['created_by' => $this->user->id, 'type' => 'dm']);
        $broken->members()->attach([
            $this->user->id => ['role' => 'admin'],
            $this->other->id => ['role' => 'member'],
            $third->id => ['role' => 'member'],
        ]);

        Artisan::call('chat:repair-dm', ['--strategy' => 'group']);

        $this->assertDatabaseHas('chats', ['id' => $broken->id, 'type' => 'group']);
    }
}
