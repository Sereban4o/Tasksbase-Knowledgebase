<?php

namespace Tests\Feature;

use App\Models\IssueStatus;
use App\Models\IssueType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneCIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'one_c.enabled' => true,
            'one_c.token' => 'test-token',
            'one_c.hmac_secret' => null,
            'one_c.tolerance' => 300,
        ]);
    }

    public function test_requires_token(): void
    {
        $this->postJson('/api/v1/integration/1c/users/sync', ['users' => []])
            ->assertStatus(401);
    }

    public function test_users_sync_creates_user_and_mapping(): void
    {
        $response = $this->withToken('test-token')->postJson('/api/v1/integration/1c/users/sync', [
            'users' => [
                [
                    'external_id' => '1C-1',
                    'email' => 'ivan@example.com',
                    'first_name' => 'Ivan',
                    'last_name' => 'Ivanov',
                ],
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('synced', 1);

        $this->assertDatabaseHas('users', [
            'email' => 'ivan@example.com',
            'first_name' => 'Ivan',
            'last_name' => 'Ivanov',
        ]);

        $this->assertDatabaseHas('integration_mappings', [
            'entity_type' => 'user',
            'external_id' => '1C-1',
        ]);
    }

    public function test_issues_push_creates_issue(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'created_by' => $user->id,
            'key' => 'TEST',
            'name' => 'Test',
            'type' => 'basic',
            'visibility' => 'public',
        ]);

        IssueType::create(['name' => 'Task']);
        IssueStatus::create(['name' => 'To Do', 'category' => 'todo']);

        $response = $this->withToken('test-token')->postJson('/api/v1/integration/1c/issues/push', [
            'issues' => [
                [
                    'external_id' => '1C-ISS-1',
                    'project_key' => 'TEST',
                    'summary' => 'From 1C',
                    'description' => 'desc',
                    'priority' => 'high',
                ],
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('synced', 1);

        $this->assertDatabaseHas('issues', [
            'summary' => 'From 1C',
            'project_id' => $project->id,
            'key' => 'TEST-1',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('integration_mappings', [
            'entity_type' => 'issue',
            'external_id' => '1C-ISS-1',
        ]);
    }

    public function test_invalid_hmac_signature_is_rejected(): void
    {
        config(['one_c.hmac_secret' => 's3cr3t']);

        $this->withToken('test-token')
            ->withHeaders(['X-Timestamp' => (string) time(), 'X-Signature' => 'bad'])
            ->postJson('/api/v1/integration/1c/users/sync', ['users' => []])
            ->assertStatus(401);
    }

    public function test_fails_closed_when_no_credentials_configured(): void
    {
        config([
            'one_c.enabled' => true,
            'one_c.token' => null,
            'one_c.hmac_secret' => null,
        ]);

        $this->postJson('/api/v1/integration/1c/users/sync', ['users' => []])
            ->assertStatus(503);
    }

    public function test_ip_allowlist_blocks_unknown_ip(): void
    {
        config(['one_c.allowed_ips' => ['10.0.0.1']]);

        $this->withToken('test-token')
            ->postJson('/api/v1/integration/1c/users/sync', ['users' => []])
            ->assertStatus(403);
    }
}
