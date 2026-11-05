<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class FilesController extends Controller
{
    /**
     * Allowed upload extensions (blocks svg/html/php and other executable content).
     */
    private const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,pdf,txt,csv,md,json,doc,docx,xls,xlsx,ppt,pptx,zip,7z,rar,sql';

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:' . self::ALLOWED_MIMES,
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
        if (! $this->resolveUser($request)) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $file = File::findOrFail($fileId);
        $fullPath = storage_path('app/public/' . $file->path);

        if (! file_exists($fullPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($fullPath, $file->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function show(Request $request, string $fileId)
    {
        if (! $this->resolveUser($request)) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $file = File::findOrFail($fileId);
        $fullPath = storage_path('app/public/' . $file->path);

        if (! file_exists($fullPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Only raster images are served inline; everything else is forced as
        // an attachment with a neutral content type (prevents stored XSS via
        // SVG/HTML and other active content).
        $isInlineImage = str_starts_with((string) $file->mime_type, 'image/')
            && ! str_contains((string) $file->mime_type, 'svg');

        return response()->file($fullPath, [
            'Content-Type' => $isInlineImage ? $file->mime_type : 'application/octet-stream',
            'Content-Disposition' => $isInlineImage
                ? 'inline'
                : 'attachment; filename="' . addslashes($file->original_name) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, string $fileId)
    {
        $user = Auth::user();
        $file = File::findOrFail($fileId);

        if ((int) $file->uploaded_by !== (int) $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $fullPath = storage_path('app/public/' . $file->path);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }

    /**
     * Resolve the current user from an existing session, the Authorization
     * header, or a ?token= query parameter (needed for <img> tags, which
     * cannot send headers).
     */
    private function resolveUser(Request $request): ?User
    {
        if (Auth::check()) {
            return Auth::user();
        }

        $token = $request->query('token') ?: $request->bearerToken();

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                Auth::login($accessToken->tokenable);

                return $accessToken->tokenable;
            }
        }

        return null;
    }
}
