<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;

/**
 * Manages app release artifacts and their `latest.json` manifests.
 *
 * Layout (served statically by nginx at /updates/):
 *   /updates/{platform}/latest.json
 *   /updates/{platform}/{binary}
 */
class ReleaseService
{
    public function basePath(): string
    {
        $configured = (string) config('updates.path', '/var/www/updates');
        $fallback = base_path('../updates');

        if (is_dir($configured)) {
            return rtrim($configured, '/');
        }

        if (is_dir($fallback)) {
            return rtrim($fallback, '/');
        }

        return rtrim($configured, '/');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function manifests(): array
    {
        $result = [];

        foreach ((array) config('updates.platforms', []) as $platform) {
            $manifest = $this->manifest($platform);
            if ($manifest !== null) {
                $result[] = $manifest;
            }
        }

        return $result;
    }

    public function manifest(string $platform): ?array
    {
        $file = $this->basePath() . '/' . $platform . '/latest.json';
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Store an uploaded binary and (re)write the platform manifest.
     *
     * @return array<string, mixed>
     */
    public function store(string $platform, UploadedFile $file, string $version, int $versionCode, ?string $notes): array
    {
        // Verify the signature of the temporary upload before publishing it.
        $this->assertSignature($platform, (string) $file->getRealPath());

        $base = $this->basePath();
        if (!is_dir($base) && !@mkdir($base, 0775, true) && !is_dir($base)) {
            throw new \RuntimeException('Каталог релизов недоступен: ' . $base);
        }
        if (!is_writable($base)) {
            throw new \RuntimeException('Каталог релизов недоступен для записи: ' . $base);
        }

        $dir = $base . '/' . $platform;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = $this->safeFilename($file->getClientOriginalName());
        $file->move($dir, $filename);

        $absolute = $dir . '/' . $filename;

        $manifest = [
            'sha256' => hash_file('sha256', $absolute),
            'version' => $version,
            'path' => "/updates/{$platform}/{$filename}",
            'versionCode' => $versionCode,
            'platform' => $platform,
            'notes' => $notes ?? '',
            'size' => filesize($absolute),
            'uploaded_at' => now()->toIso8601String(),
        ];

        file_put_contents(
            $dir . '/latest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
        );

        return $manifest;
    }

    /**
     * Remove the platform manifest and any referenced binary.
     */
    public function delete(string $platform): bool
    {
        $dir = $this->basePath() . '/' . $platform;
        $manifest = $this->manifest($platform);

        if ($manifest && !empty($manifest['path'])) {
            $binary = $dir . '/' . basename((string) $manifest['path']);
            if (is_file($binary)) {
                @unlink($binary);
            }
        }

        $latest = $dir . '/latest.json';
        if (is_file($latest)) {
            return @unlink($latest);
        }

        return false;
    }

    private function safeFilename(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? $name;

        return $name !== '' ? $name : 'release.bin';
    }

    /**
     * Run the configured signature verifier for the platform.
     *
     * @throws \RuntimeException when verification is required but cannot be performed.
     */
    private function assertSignature(string $platform, string $path): void
    {
        if (!config('updates.verify_signature')) {
            return;
        }

        $template = trim((string) config('updates.signature_commands.' . $platform, ''));

        if ($template === '') {
            throw new \RuntimeException(
                'Проверка подписи включена, но верификатор для платформы ' . $platform . ' не настроен',
            );
        }

        $command = str_replace('%s', escapeshellarg($path), $template);
        $process = Process::fromShellCommandline($command, null, null, null, 120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                'Проверка подписи не пройдена: ' . trim($process->getErrorOutput() ?: $process->getOutput()),
            );
        }
    }
}
