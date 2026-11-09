<?php

namespace App\Services\Integration\OneC;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for outgoing Laravel -> 1С calls.
 */
class OneCClient
{
    public function enabled(): bool
    {
        return (bool) config('one_c.enabled') && (string) config('one_c.base_url') !== '';
    }

    public function get(string $path, array $query = []): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        try {
            $response = $this->client()->get($path, $query);
        } catch (\Throwable $e) {
            Log::warning('1C request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }

        if (!$response->successful()) {
            Log::warning('1C request returned error', ['path' => $path, 'status' => $response->status()]);

            return null;
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : null;
    }

    public function post(string $path, array $payload): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        try {
            $response = $this->client()->post($path, $payload);
        } catch (\Throwable $e) {
            Log::warning('1C request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }

        if (!$response->successful()) {
            Log::warning('1C request returned error', ['path' => $path, 'status' => $response->status()]);

            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function client()
    {
        $request = Http::baseUrl(rtrim((string) config('one_c.base_url'), '/'))
            ->timeout((int) config('one_c.timeout', 30))
            ->acceptJson()
            ->asJson();

        $token = (string) config('one_c.token');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }
}
