<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Publishes notifications to a self-hosted ntfy server.
 *
 * ntfy is used as a Firebase-free push transport. The backend publishes
 * a JSON message to a per-user topic; clients subscribe to that topic.
 */
class NtfyService
{
    public function topicForUser(int $userId): string
    {
        return config('ntfy.topic_prefix', 'tasksbase-user-') . $userId;
    }

    /**
     * Publish a message to a user's topic. Never throws — push is best-effort.
     */
    public function notifyUser(int $userId, string $title, string $message, array $tags = []): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        $baseUrl = rtrim((string) config('ntfy.url'), '/');

        if ($baseUrl === '') {
            return false;
        }

        $payload = [
            'topic' => $this->topicForUser($userId),
            'title' => $title,
            'message' => $message,
        ];

        if (!empty($tags)) {
            $payload['tags'] = $tags;
        }

        try {
            $request = Http::timeout(3)->acceptJson()->asJson();

            $user = config('ntfy.user');
            $password = config('ntfy.password');
            $token = config('ntfy.token');

            if (!empty($user) && $password !== null && $password !== '') {
                // Preferred: ntfy user/password (HTTP Basic).
                $request = $request->withBasicAuth((string) $user, (string) $password);
            } elseif (!empty($token)) {
                // Optional: ntfy access token (Bearer).
                $request = $request->withToken((string) $token);
            }

            return $request->post($baseUrl, $payload)->successful();
        } catch (\Throwable $e) {
            Log::warning('ntfy publish failed', ['error' => $e->getMessage(), 'userId' => $userId]);

            return false;
        }
    }
}
