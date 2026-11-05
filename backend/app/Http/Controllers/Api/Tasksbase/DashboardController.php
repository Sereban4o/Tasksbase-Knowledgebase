<?php

namespace App\Http\Controllers\Api\Tasksbase;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dashboards = Dashboard::where('user_id', Auth::id())
            ->with('widgets')
            ->get();

        return response()->json($dashboards);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $dashboard = Dashboard::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'project_id' => $validated['project_id'] ?? null,
        ]);

        return response()->json($dashboard, 201);
    }

    public function show(Request $request, string $dashboardId)
    {
        $dashboard = Dashboard::where('user_id', Auth::id())
            ->with('widgets')
            ->findOrFail($dashboardId);

        return response()->json($dashboard);
    }

    public function update(Request $request, string $dashboardId)
    {
        $dashboard = Dashboard::where('user_id', Auth::id())->findOrFail($dashboardId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $dashboard->update($validated);

        return response()->json($dashboard);
    }

    public function destroy(Request $request, string $dashboardId)
    {
        $dashboard = Dashboard::where('user_id', Auth::id())->findOrFail($dashboardId);
        $dashboard->delete();

        return response()->json(['message' => 'Dashboard deleted']);
    }

    public function addWidget(Request $request, string $dashboardId)
    {
        $validated = $request->validate([
            'type' => 'required|in:assigned_to_me,issue_count,project_list,velocity_chart,activity_feed',
            'config' => 'nullable|array',
            'width' => 'sometimes|integer|min:1|max:12',
            'height' => 'sometimes|integer|min:1|max:12',
        ]);

        $maxOrder = DashboardWidget::where('dashboard_id', $dashboardId)->max('order') ?? 0;

        $widget = DashboardWidget::create([
            'dashboard_id' => $dashboardId,
            'type' => $validated['type'],
            'config' => $validated['config'] ?? [],
            'order' => $maxOrder + 1,
            'width' => $validated['width'] ?? 6,
            'height' => $validated['height'] ?? 4,
        ]);

        return response()->json($widget, 201);
    }

    public function updateWidget(Request $request, string $dashboardId, string $widgetId)
    {
        $widget = DashboardWidget::where('dashboard_id', $dashboardId)->findOrFail($widgetId);

        $validated = $request->validate([
            'type' => 'sometimes|in:assigned_to_me,issue_count,project_list,velocity_chart,activity_feed',
            'config' => 'nullable|array',
            'width' => 'sometimes|integer|min:1|max:12',
            'height' => 'sometimes|integer|min:1|max:12',
        ]);

        $widget->update($validated);

        return response()->json($widget);
    }

    public function removeWidget(Request $request, string $dashboardId, string $widgetId)
    {
        $widget = DashboardWidget::where('dashboard_id', $dashboardId)->findOrFail($widgetId);
        $widget->delete();

        return response()->json(['message' => 'Widget removed']);
    }

    public function stats(Request $request)
    {
        $userId = Auth::id();
        
        $stats = [
            'total_issues' => Issue::count(),
            'assigned_issues' => Issue::where('assigned_to', $userId)->count(),
            'completed_issues' => Issue::whereHas('status', function($q) {
                $q->where('name', 'Done');
            })->where('assigned_to', $userId)->count(),
            'open_issues' => Issue::whereHas('status', function($q) {
                $q->where('name', 'Open');
            })->count(),
        ];

        return response()->json($stats);
    }

    public function activity(Request $request)
    {
        $userId = Auth::id();
        
        $activity = Issue::where('assigned_to', $userId)
            ->with(['status', 'project'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get(['id', 'summary', 'status_id', 'updated_at']);

        return response()->json($activity);
    }
}
