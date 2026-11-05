<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Project channels - users can subscribe if they are members
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    if (!$user) return false;
    return (bool) \App\Models\Project::where('id', $projectId)
        ->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->orWhere('visibility', 'public')
        ->exists();
});

// Issue channels - specific issue updates
Broadcast::channel('issue.{issueId}', function ($user, $issueId) {
    if (!$user) return false;
    return (bool) \App\Models\Issue::where('id', $issueId)
        ->whereHas('project', function ($q) use ($user) {
            $q->whereHas('members', function ($q2) use ($user) {
                $q2->where('user_id', $user->id);
            });
        })
        ->exists();
});

// Board channels
Broadcast::channel('board.{boardId}', function ($user, $boardId) {
    if (!$user) return false;
    return (bool) \App\Models\Board::where('id', $boardId)
        ->whereHas('project', function ($q) use ($user) {
            $q->whereHas('members', function ($q2) use ($user) {
                $q2->where('user_id', $user->id);
            });
        })
        ->exists();
});

// Space channels
Broadcast::channel('space.{spaceId}', function ($user, $spaceId) {
    if (!$user) return false;
    return (bool) \App\Models\Space::where('id', $spaceId)
        ->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->orWhere('visibility', 'public')
        ->exists();
});

// Page channels
Broadcast::channel('page.{pageId}', function ($user, $pageId) {
    if (!$user) return false;
    return (bool) \App\Models\Page::where('id', $pageId)
        ->whereHas('space', function ($q) use ($user) {
            $q->whereHas('members', function ($q2) use ($user) {
                $q2->where('user_id', $user->id);
            });
        })
        ->exists();
});

// Page presence channel for collaborative editing
Broadcast::channel('page.{pageId}.presence', function ($user, $pageId) {
    if (!$user) return null;
    return ['id' => $user->id, 'name' => $user->full_name];
});

// User-specific channels (notifications)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Chat channels - only members can subscribe
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    if (!$user) return false;
    return (bool) \App\Models\Chat::where('id', $chatId)
        ->whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->exists();
});
