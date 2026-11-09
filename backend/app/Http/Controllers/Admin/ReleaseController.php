<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReleaseService;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function __construct(private ReleaseService $releases)
    {
    }

    public function index()
    {
        return view('admin.releases.index', [
            'releases' => $this->releases->manifests(),
            'platforms' => (array) config('updates.platforms', []),
            'path' => $this->releases->basePath(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|string|in:' . implode(',', (array) config('updates.platforms', [])),
            'version' => 'required|string|max:64',
            'versionCode' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:5000',
            'file' => 'required|file|max:' . (int) config('updates.max_size_kb', 512000),
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowed = (array) config('updates.allowed_extensions.' . $validated['platform'], []);

        if (!empty($allowed) && !in_array($extension, $allowed, true)) {
            return back()->with('error', 'Недопустимый тип файла для платформы. Разрешено: ' . implode(', ', $allowed));
        }

        try {
            $this->releases->store(
                $validated['platform'],
                $file,
                $validated['version'],
                (int) ($validated['versionCode'] ?? 0),
                $validated['notes'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Релиз загружен');
    }

    public function destroy(string $platform)
    {
        if (!in_array($platform, (array) config('updates.platforms', []), true)) {
            return back()->with('error', 'Неизвестная платформа');
        }

        $deleted = $this->releases->delete($platform);

        return back()->with(
            $deleted ? 'success' : 'error',
            $deleted ? 'Релиз удалён' : 'Релиз не найден',
        );
    }
}
