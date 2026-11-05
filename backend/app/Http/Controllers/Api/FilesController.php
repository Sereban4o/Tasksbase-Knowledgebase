<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FilesController extends Controller
{
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('files', $storedName, 'public');

        $model = File::create([
            'uploaded_by' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        return response()->json($model, 201);
    }

    public function download(Request $request, string $fileId)
    {
        $file = File::findOrFail($fileId);

        // Path is stored as 'files/{uuid}.{ext}', full path is storage/app/public/files/{uuid}.{ext}
        $fullPath = storage_path("app/public/" . $file->path);

        if (!file_exists($fullPath)) {
            \Log::error('File not found', ['path' => $fullPath, 'file_id' => $fileId]);
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($fullPath, $file->original_name);
    }

    public function show(Request $request, string $fileId)
    {
        // Support token query param for <img> tags (browser can't send Authorization header)
        $token = $request->query('token');
        if ($token) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                \Illuminate\Support\Facades\Auth::login($accessToken->tokenable);
            }
        }

        $file = File::findOrFail($fileId);

        $fullPath = storage_path("app/public/" . $file->path);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file($fullPath, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline',
        ]);
    }

    public function destroy(Request $request, string $fileId)
    {
        $file = File::findOrFail($fileId);

        $fullPath = storage_path("app/public/" . $file->path);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }
}
