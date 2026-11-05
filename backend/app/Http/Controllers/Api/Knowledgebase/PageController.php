<?php

namespace App\Http\Controllers\Api\Knowledgebase;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\PageComment;
use App\Models\PageLabel;
use App\Models\PageAttachment;
use App\Models\File;
use App\Models\Space;
use App\Events\PageUpdated;
use App\Events\NewComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function index(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        $query = Page::where('space_id', $space->id)
            ->with(['author', 'space', 'parent'])
            ->whereNull('parent_id');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $pages = $query->latest()->paginate(20);

        return response()->json($pages);
    }

    public function spacePages(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        $pages = Page::where('space_id', $space->id)
            ->with(['author', 'space'])
            ->latest()
            ->get();

        return response()->json($pages);
    }

    public function tree(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        $pages = Page::where('space_id', $space->id)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->with(['children', 'author']);
            }, 'author'])
            ->get();

        return response()->json($pages);
    }

    public function show(Request $request, string $pageId)
    {
        $page = Page::with(['space', 'author', 'lastEditor', 'parent', 'children'])
            ->findOrFail($pageId);

        return response()->json($page);
    }

    public function store(Request $request, string $spaceKey)
    {
        $space = Space::where('key', $spaceKey)->firstOrFail();

        if (!$space->canEdit(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:pages,id',
            'title' => 'required|string|max:500',
            'body' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'labels' => 'nullable|array',
        ]);

        $page = Page::create([
            'space_id' => $space->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'author_id' => Auth::id(),
            'last_edited_by' => Auth::id(),
            'title' => $validated['title'],
            'body' => $validated['body'] ?? '',
            'status' => $validated['status'] ?? 'draft',
            'version' => 1,
            'labels' => $validated['labels'] ?? [],
        ]);

        // Create initial version
        PageVersion::create([
            'page_id' => $page->id,
            'version' => 1,
            'author_id' => Auth::id(),
            'summary' => 'Initial version',
            'body_snapshot' => $validated['body'] ?? '',
        ]);

        // Save labels
        if (!empty($validated['labels'])) {
            foreach ($validated['labels'] as $label) {
                PageLabel::create([
                    'page_id' => $page->id,
                    'label' => strtolower($label),
                ]);
            }
        }

        return response()->json($page, 201);
    }

    public function update(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);

        if (!$page->space->canEdit(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:500',
            'body' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'labels' => 'nullable|array',
        ]);

        // Save version before updating
        $newVersion = $page->version + 1;
        PageVersion::create([
            'page_id' => $page->id,
            'version' => $newVersion,
            'author_id' => Auth::id(),
            'summary' => $request->input('version_summary', 'Updated'),
            'body_snapshot' => $validated['body'] ?? $page->body,
        ]);

        $page->update([
            ...$validated,
            'version' => $newVersion,
            'last_edited_by' => Auth::id(),
        ]);

        // Update labels
        if (isset($validated['labels'])) {
            PageLabel::where('page_id', $page->id)->delete();
            foreach ($validated['labels'] as $label) {
                PageLabel::create([
                    'page_id' => $page->id,
                    'label' => strtolower($label),
                ]);
            }
        }

        event(new PageUpdated($page, 'updated'));

        return response()->json($page->load(['space', 'author', 'lastEditor']));
    }

    public function destroy(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);

        if (!$page->space->canEdit(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $page->delete();

        return response()->json(['message' => 'Page deleted']);
    }

    public function publish(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);

        $page->update(['status' => 'published']);

        return response()->json($page);
    }

    public function restore(Request $request, string $pageId, string $version)
    {
        $page = Page::findOrFail($pageId);

        $pageVersion = PageVersion::where('page_id', $pageId)
            ->where('version', $version)
            ->firstOrFail();

        $newVersion = $page->version + 1;

        PageVersion::create([
            'page_id' => $page->id,
            'version' => $newVersion,
            'author_id' => Auth::id(),
            'summary' => "Restored from version {$version}",
            'body_snapshot' => $pageVersion->body_snapshot,
        ]);

        $page->update([
            'body' => $pageVersion->body_snapshot,
            'version' => $newVersion,
            'last_edited_by' => Auth::id(),
        ]);

        return response()->json($page);
    }

    public function versions(Request $request, string $pageId)
    {
        $versions = PageVersion::where('page_id', $pageId)
            ->with('author')
            ->orderBy('version', 'desc')
            ->get();

        return response()->json($versions);
    }

    public function diff(Request $request, string $pageId, string $versionA, string $versionB)
    {
        $version1 = PageVersion::where('page_id', $pageId)
            ->where('version', $versionA)
            ->firstOrFail();

        $version2 = PageVersion::where('page_id', $pageId)
            ->where('version', $versionB)
            ->firstOrFail();

        return response()->json([
            'version_a' => $version1,
            'version_b' => $version2,
            'diff' => $this->computeDiff($version1->body_snapshot, $version2->body_snapshot),
        ]);
    }

    private function computeDiff(string $old, string $new): array
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $diff = [];
        $maxLines = max(count($oldLines), count($newLines));

        for ($i = 0; $i < $maxLines; $i++) {
            $oldLine = $oldLines[$i] ?? null;
            $newLine = $newLines[$i] ?? null;

            if ($oldLine !== $newLine) {
                $diff[] = [
                    'line' => $i + 1,
                    'old' => $oldLine,
                    'new' => $newLine,
                ];
            }
        }

        return $diff;
    }

    public function comments(Request $request, string $pageId)
    {
        $comments = PageComment::where('page_id', $pageId)
            ->with('user')
            ->latest()
            ->get();

        return response()->json($comments);
    }

    public function addComment(Request $request, string $pageId)
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'line_number' => 'nullable|integer',
            'column_number' => 'nullable|integer',
        ]);

        $comment = PageComment::create([
            'page_id' => $pageId,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
            'line_number' => $validated['line_number'] ?? null,
            'column_number' => $validated['column_number'] ?? null,
        ]);

        event(new NewComment($comment, 'page'));

        return response()->json($comment->load('user'), 201);
    }

    public function updateComment(Request $request, string $pageId, string $commentId)
    {
        $comment = PageComment::where('page_id', $pageId)
            ->where('id', $commentId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $comment->update(['body' => $validated['body']]);

        return response()->json($comment);
    }

    public function deleteComment(Request $request, string $pageId, string $commentId)
    {
        $comment = PageComment::where('page_id', $pageId)
            ->where('id', $commentId)
            ->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhereHas('page.space', function ($q) {
                      $q->whereHas('members', function ($q) {
                          $q->where('user_id', Auth::id())
                            ->where('role', 'admin');
                      });
                  });
            })
            ->firstOrFail();

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }

    public function updateLabels(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);

        $validated = $request->validate([
            'labels' => 'required|array',
            'labels.*' => 'string|max:50',
        ]);

        PageLabel::where('page_id', $page->id)->delete();

        foreach ($validated['labels'] as $label) {
            PageLabel::create([
                'page_id' => $page->id,
                'label' => strtolower($label),
            ]);
        }

        $page->update(['labels' => array_map('strtolower', $validated['labels'])]);

        return response()->json($page->load('labels'));
    }

    public function attachments(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);

        if (!$page->space->canView(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $attachments = PageAttachment::with(['file', 'uploader'])
            ->where('page_id', $page->id)
            ->latest()
            ->get();

        return response()->json($attachments);
    }

    public function addAttachment(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);

        if (!$page->space->canEdit(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'file_id' => 'required|exists:files,id',
        ]);

        $file = File::where('id', $validated['file_id'])
            ->where('uploaded_by', Auth::id())
            ->first();

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $attachment = PageAttachment::firstOrCreate(
            ['page_id' => $page->id, 'file_id' => $file->id],
            ['uploaded_by' => Auth::id()]
        );

        return response()->json($attachment->load(['file', 'uploader']), 201);
    }

    public function removeAttachment(Request $request, string $pageId, string $attachmentId)
    {
        $page = Page::findOrFail($pageId);

        $attachment = PageAttachment::where('page_id', $page->id)
            ->where('id', $attachmentId)
            ->firstOrFail();

        if ((int) $attachment->uploaded_by !== (int) Auth::id() && !$page->space->canEdit(Auth::user())) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment removed']);
    }
}
