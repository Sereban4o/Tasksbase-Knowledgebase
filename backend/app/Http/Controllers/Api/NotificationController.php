<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->with(['actor', 'subject'])
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, string $notificationId)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($notificationId);

        $notification->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return response()->json($notification);
    }

    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function activity(Request $request)
    {
        $activities = ActivityLog::where('user_id', Auth::id())
            ->with(['subject'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($activities);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
