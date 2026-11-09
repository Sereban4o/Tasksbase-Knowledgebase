<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminReleaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->member = User::factory()->create(['role_id' => null]);

        $this->dir = sys_get_temp_dir() . '/updates-test-' . uniqid();
        mkdir($this->dir, 0775, true);

        config([
            'updates.path' => $this->dir,
            'updates.platforms' => ['android', 'windows'],
            'updates.max_size_kb' => 512000,
            'updates.allowed_extensions' => [
                'android' => ['apk', 'aab'],
                'windows' => ['msi', 'exe', 'zip'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*/*') ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob($this->dir . '/*') ?: [] as $sub) {
            @rmdir($sub);
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    public function test_non_admin_cannot_access_releases(): void
    {
        $this->actingAs($this->member)
            ->get('/admin/releases')
            ->assertStatus(403);
    }

    public function test_admin_can_view_releases_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/releases')
            ->assertStatus(200)
            ->assertSee('Релизы приложений');
    }

    public function test_admin_can_upload_release(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/releases', [
                'platform' => 'android',
                'version' => '1.0.2',
                'versionCode' => 3,
                'notes' => 'Bugfix release',
                'file' => UploadedFile::fake()->create('app-debug.apk', 10),
            ])
            ->assertRedirect();

        $manifest = json_decode((string) file_get_contents($this->dir . '/android/latest.json'), true);

        $this->assertSame('1.0.2', $manifest['version']);
        $this->assertSame(3, $manifest['versionCode']);
        $this->assertSame('android', $manifest['platform']);
        $this->assertStringContainsString('/updates/android/', $manifest['path']);
        $this->assertNotEmpty($manifest['sha256']);
        $this->assertFileExists($this->dir . '/android/app-debug.apk');
    }

    public function test_upload_rejects_disallowed_extension(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/releases', [
                'platform' => 'android',
                'version' => '1.0.0',
                'file' => UploadedFile::fake()->create('payload.php', 5, 'text/x-php'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFileDoesNotExist($this->dir . '/android/latest.json');
    }

    public function test_admin_can_delete_release(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/releases', [
                'platform' => 'android',
                'version' => '1.0.3',
                'file' => UploadedFile::fake()->create('app.apk', 10),
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->delete('/admin/releases/android')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFileDoesNotExist($this->dir . '/android/latest.json');
    }

    public function test_upload_rejects_unknown_platform(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/releases', [
                'platform' => 'ios',
                'version' => '1.0.0',
                'file' => UploadedFile::fake()->create('app.ipa', 10),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('platform');
    }

    public function test_upload_rejects_when_signature_required_without_verifier(): void
    {
        config([
            'updates.verify_signature' => true,
            'updates.signature_commands' => ['android' => '', 'windows' => ''],
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/releases', [
                'platform' => 'android',
                'version' => '1.0.0',
                'file' => UploadedFile::fake()->create('app.apk', 10),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFileDoesNotExist($this->dir . '/android/latest.json');
    }
}
