<?php

namespace App\Services\Integration\OneC;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Synchronises users between Laravel and 1С.
 */
class UserSyncService
{
    public function __construct(
        private MappingRepository $map,
        private OneCClient $client,
    ) {
    }

    public function upsertFromExternal(array $data): User
    {
        $externalId = trim((string) ($data['external_id'] ?? $data['id'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        $existingId = $externalId !== '' ? $this->map->findLocalId('user', $externalId) : null;
        $user = $existingId ? User::find($existingId) : null;
        if (!$user && $email !== '') {
            $user = User::where('email', $email)->first();
        }

        $attributes = [
            'first_name' => (string) ($data['first_name'] ?? ''),
            'last_name' => (string) ($data['last_name'] ?? ''),
        ];
        if ($email !== '') {
            $attributes['email'] = $email;
        }

        if ($user) {
            $user->fill($attributes)->save();
        } else {
            $user = User::create($attributes + [
                'email' => $email !== '' ? $email : ($externalId !== '' ? $externalId . '@1c.local' : Str::random(8) . '@1c.local'),
                'password' => Hash::make(Str::random(24)),
                'status' => 'active',
            ]);
        }

        if ($externalId !== '') {
            $this->map->link('user', $user->id, $externalId, ['synced_at' => now()->toIso8601String()]);
        }

        return $user;
    }

    public function push(User $user): void
    {
        if (!$this->client->enabled()) {
            return;
        }

        $response = $this->client->post('users', [
            'external_id' => $this->map->findExternalId('user', $user->id),
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'status' => $user->status,
        ]);

        if (is_array($response) && !empty($response['external_id'])) {
            $this->map->link('user', $user->id, (string) $response['external_id']);
        }
    }
}
