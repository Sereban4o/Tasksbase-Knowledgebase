<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Tasksbase\ProjectController;
use App\Http\Controllers\Api\Tasksbase\IssueController;
use App\Http\Controllers\Api\Tasksbase\BoardController;
use App\Http\Controllers\Api\Tasksbase\SprintController;
use App\Http\Controllers\Api\Tasksbase\EpicController;
use App\Http\Controllers\Api\Tasksbase\DashboardController;
use App\Http\Controllers\Api\Tasksbase\ReportController;
use App\Http\Controllers\Api\Tasksbase\FilterController;
use App\Http\Controllers\Api\Knowledgebase\SpaceController;
use App\Http\Controllers\Api\Knowledgebase\PageController;
use App\Http\Controllers\Api\Knowledgebase\TasksbaseEmbedController;
use App\Http\Controllers\Api\FilesController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Chat\ChatController;
use App\Http\Controllers\Api\Chat\MessageController;
use App\Http\Controllers\Api\Integration\OneC\OneCController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ─── Public Auth Routes ───
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed');

    // ─── 1С Integration (token + HMAC, no user session) ───
    Route::prefix('integration/1c')->middleware(['one.c', 'throttle:120,1'])->group(function () {
        Route::post('/users/sync', [OneCController::class, 'usersSync']);
        Route::post('/issues/push', [OneCController::class, 'issuesPush']);
        Route::match(['get', 'post'], '/issues/pull', [OneCController::class, 'issuesPull']);
        Route::post('/issues/{externalId}/comment', [OneCController::class, 'issueComment']);
        Route::post('/notifications', [OneCController::class, 'notifications']);
        Route::post('/chats/messages', [OneCController::class, 'chatMessage']);
        Route::post('/files/upload', [OneCController::class, 'fileUpload']);
        Route::post('/kb/pages', [OneCController::class, 'kbPage']);
    });

    // ─── Protected Auth Routes ───
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/avatar', [AuthController::class, 'uploadAvatar']);

        // Users
        Route::get('/users/all', [AuthController::class, 'allUsers']);

        // Notifications
        Route::get('/auth/notifications', [NotificationController::class, 'index']);
        Route::patch('/auth/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/auth/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::get('/auth/activity', [NotificationController::class, 'activity']);

        // ─── Tasksbase Routes ───
        Route::prefix('tasksbase')->group(function () {
            // Projects
            Route::get('/projects', [ProjectController::class, 'index']);
            Route::get('/projects/{key}', [ProjectController::class, 'show']);
            Route::post('/projects', [ProjectController::class, 'store']);
            Route::patch('/projects/{key}', [ProjectController::class, 'update']);
            Route::delete('/projects/{key}', [ProjectController::class, 'destroy']);

            // Issues
            Route::get('/issues', [IssueController::class, 'index']);
            Route::get('/issues/{id}', [IssueController::class, 'show']);
            Route::post('/issues', [IssueController::class, 'store']);
            Route::patch('/issues/{id}', [IssueController::class, 'update']);
            Route::delete('/issues/{id}', [IssueController::class, 'destroy']);
            Route::get('/issues/{id}/comments', [IssueController::class, 'comments']);
            Route::post('/issues/{id}/comments', [IssueController::class, 'addComment']);
            Route::post('/issues/{id}/attachments', [IssueController::class, 'addAttachment']);
            Route::post('/issues/{id}/links', [IssueController::class, 'linkIssue']);
            Route::post('/issues/{id}/move', [IssueController::class, 'moveIssue']);

            // Boards
            Route::get('/boards', [BoardController::class, 'index']);
            Route::get('/boards/{id}', [BoardController::class, 'show']);
            Route::get('/boards/{id}/columns', [BoardController::class, 'columns']);
            Route::post('/boards', function (Request $request) {
                $validated = $request->validate([
                    'project_key' => 'required|exists:projects,key',
                    'name' => 'required|string|max:255',
                    'type' => 'required|in:kanban,scrum',
                ]);
                
                $project = \App\Models\Project::where('key', $validated['project_key'])->firstOrFail();

                $user = \Illuminate\Support\Facades\Auth::user();
                if (!$user->isAdmin() && !$project->hasPermission($user, 'edit_boards')) {
                    return response()->json(['message' => 'Permission denied'], 403);
                }

                $board = $project->boards()->create([
                    'name' => $validated['name'],
                    'type' => $validated['type'],
                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                ]);
                
                return response()->json($board->load('columns'), 201);
            });
            Route::patch('/boards/{id}', [BoardController::class, 'update']);
            Route::delete('/boards/{id}', [BoardController::class, 'destroy']);

            // Sprints
            Route::get('/sprints', [SprintController::class, 'index']);
            Route::get('/sprints/{id}', [SprintController::class, 'show']);
            Route::post('/sprints', [SprintController::class, 'store']);
            Route::patch('/sprints/{id}', [SprintController::class, 'update']);
            Route::delete('/sprints/{id}', [SprintController::class, 'destroy']);

            // Epics
            Route::get('/epics', [EpicController::class, 'index']);
            Route::get('/epics/{id}', [EpicController::class, 'show']);
            Route::post('/epics', [EpicController::class, 'store']);
            Route::patch('/epics/{id}', [EpicController::class, 'update']);
            Route::delete('/epics/{id}', [EpicController::class, 'destroy']);

            // Dashboard
            Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
            Route::get('/dashboard/activity', [DashboardController::class, 'activity']);

            // Report
            Route::get('/report/completion', [ReportController::class, 'completion']);
            Route::get('/report/workload', [ReportController::class, 'workload']);

            // Filter
            Route::get('/filter/save', [FilterController::class, 'save']);
            Route::get('/filter/list', [FilterController::class, 'list']);
        });

        // ─── Knowledgebase Routes ───
        Route::prefix('knowledgebase')->group(function () {
            // Spaces
            Route::get('/spaces', [SpaceController::class, 'index']);
            Route::get('/spaces/{key}', [SpaceController::class, 'show']);
            Route::post('/spaces', [SpaceController::class, 'store']);
            Route::patch('/spaces/{key}', [SpaceController::class, 'update']);
            Route::delete('/spaces/{key}', [SpaceController::class, 'destroy']);

            // Pages
            Route::get('/pages', [PageController::class, 'index']);
            Route::get('/spaces/{spaceKey}/pages', [PageController::class, 'spacePages']);
            Route::post('/spaces/{spaceKey}/pages', [PageController::class, 'store']);
            Route::get('/pages/{id}', [PageController::class, 'show']);
            Route::patch('/pages/{id}', [PageController::class, 'update']);
            Route::delete('/pages/{id}', [PageController::class, 'destroy']);

            // Page attachments
            Route::get('/pages/{id}/attachments', [PageController::class, 'attachments']);
            Route::post('/pages/{id}/attachments', [PageController::class, 'addAttachment']);
            Route::delete('/pages/{id}/attachments/{attachmentId}', [PageController::class, 'removeAttachment']);

            // Embeds
            Route::get('/tasksbase-embed', [TasksbaseEmbedController::class, 'index']);
        });

        // ─── Chat Routes ───
        Route::prefix('chat')->group(function () {
            // Chats
            Route::get('/my', [ChatController::class, 'myChats']);
            Route::get('/search-users', [ChatController::class, 'searchUsers']);
            Route::get('/', [ChatController::class, 'index']);
            Route::post('/', [ChatController::class, 'store']);
            Route::get('/{chat}', [ChatController::class, 'show'])->whereNumber('chat');
            Route::patch('/{chat}', [ChatController::class, 'update']);
            Route::delete('/{chat}', [ChatController::class, 'destroy']);
            
            // Chat members
            Route::post('/{chat}/members', [ChatController::class, 'addMembers']);
            Route::post('/{chat}/members/remove', [ChatController::class, 'removeMember']);
            Route::post('/{chat}/leave', [ChatController::class, 'leaveChat'])->whereNumber('chat');
            Route::post('/{chat}/read', [ChatController::class, 'markAsRead']);

            // Messages
            Route::get('/{chat}/messages', [MessageController::class, 'index']);
            Route::post('/{chat}/messages', [MessageController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/{chat}/messages/{message}', [MessageController::class, 'show']);
            Route::patch('/{chat}/messages/{message}', [MessageController::class, 'update']);
            Route::delete('/{chat}/messages/{message}', [MessageController::class, 'destroy']);
            Route::post('/{chat}/messages/{message}/react', [MessageController::class, 'addReaction']);
            Route::delete('/{chat}/messages/{message}/react/{emoji}', [MessageController::class, 'removeReaction']);
        });

        // ─── File Routes (inside sanctum for upload/delete) ───
        Route::post('/files/upload', [FilesController::class, 'upload']);
        Route::delete('/files/{id}', [FilesController::class, 'destroy']);
    });

    // ─── Public File View (supports ?token= for <img> tags) ───
    Route::get('/files/{id}', [FilesController::class, 'show']);
    Route::get('/files/{id}/download', [FilesController::class, 'download']);

    // ─── Search Routes ───
    Route::get('/search', [SearchController::class, 'index']);

    // ─── Admin Routes ───
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        // User Management
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'userShow']);
        Route::post('/users', [AdminController::class, 'userStore']);
        Route::patch('/users/{id}', [AdminController::class, 'userUpdate']);
        Route::delete('/users/{id}', [AdminController::class, 'userDelete']);

        // Status Management
        Route::get('/statuses', [AdminController::class, 'statuses']);
        Route::post('/statuses', [AdminController::class, 'storeStatus']);
        Route::patch('/statuses/{id}', [AdminController::class, 'updateStatus']);
        Route::delete('/statuses/{id}', [AdminController::class, 'deleteStatus']);

        // Priority Management
        Route::get('/priorities', [AdminController::class, 'priorities']);
        Route::post('/priorities', [AdminController::class, 'storePriority']);
        Route::patch('/priorities/{id}', [AdminController::class, 'updatePriority']);
        Route::delete('/priorities/{id}', [AdminController::class, 'deletePriority']);

        // Role Management
        Route::get('/roles', [AdminController::class, 'roles']);
        Route::post('/roles', [AdminController::class, 'storeRole']);
        Route::patch('/roles/{id}', [AdminController::class, 'updateRole']);
        Route::delete('/roles/{id}', [AdminController::class, 'deleteRole']);

        // Space Management
        Route::get('/spaces', [AdminController::class, 'spaces']);
        Route::post('/spaces', [AdminController::class, 'storeSpace']);
        Route::patch('/spaces/{id}', [AdminController::class, 'updateSpace']);
        Route::delete('/spaces/{id}', [AdminController::class, 'destroySpace']);
    });
});
