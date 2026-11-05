<?php

namespace App\Http\Controllers\Api\Knowledgebase;

use App\Http\Controllers\Controller;
use App\Models\IssueKnowledgebaseLink;
use App\Models\Page;
use Illuminate\Http\Request;

class TasksbaseEmbedController extends Controller
{
    public function index(Request $request, string $pageId)
    {
        $links = IssueKnowledgebaseLink::where('page_id', $pageId)
            ->with(['issue' => function ($q) {
                $q->with(['project', 'type', 'status', 'assignee']);
            }])
            ->get();

        return response()->json($links);
    }

    public function attach(Request $request, string $pageId)
    {
        $validated = $request->validate([
            'issue_id' => 'required|exists:issues,id',
            'context' => 'sometimes|in:embedded,referenced',
        ]);

        $page = Page::findOrFail($pageId);

        // Check space access
        if (!$page->space->members()->where('user_id', auth()->id())
            ->whereIn('role', ['admin', 'editor'])->exists()) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $link = IssueKnowledgebaseLink::firstOrCreate(
            ['page_id' => $pageId, 'issue_id' => $validated['issue_id'], 'context' => $validated['context'] ?? 'embedded'],
            ['page_id' => $pageId, 'issue_id' => $validated['issue_id'], 'context' => $validated['context'] ?? 'embedded']
        );

        return response()->json($link->load('issue'), 201);
    }

    public function detach(Request $request, string $pageId, string $linkId)
    {
        $link = IssueKnowledgebaseLink::where('page_id', $pageId)->findOrFail($linkId);
        $link->delete();

        return response()->json(['message' => 'Link removed']);
    }
}
