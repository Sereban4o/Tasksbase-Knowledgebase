<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Middleware\EnsureUserIsAdmin;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// API root
Route::get('/', function () {
    return response()->json(['message' => 'Tasksbase-Knowledgebase API Backend']);
});

// Login route (for Laravel's internal redirects)
Route::get('/login', function () {
    return redirect('/');
})->name('login');

// Admin Auth Routes (public)
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Panel routes (Blade views) - protected
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Users
    Route::get('/users', [DashboardController::class, 'users'])->name('users.index');
    Route::get('/users/create', [DashboardController::class, 'userCreate'])->name('users.create');
    Route::post('/users', [DashboardController::class, 'userStore'])->name('users.store');
    Route::get('/users/{user}/edit', [DashboardController::class, 'userEdit'])->name('users.edit');
    Route::patch('/users/{user}', [DashboardController::class, 'userUpdate'])->name('users.update');
    Route::delete('/users/{user}', [DashboardController::class, 'userDelete'])->name('users.delete');
    Route::patch('/users/{user}/toggle-status', [DashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
    
    // Statuses
    Route::get('/statuses', [DashboardController::class, 'statuses'])->name('statuses.index');
    Route::get('/statuses/create', [DashboardController::class, 'statusCreate'])->name('statuses.create');
    Route::post('/statuses', [DashboardController::class, 'statusStore'])->name('statuses.store');
    Route::get('/statuses/{status}/edit', [DashboardController::class, 'statusEdit'])->name('statuses.edit');
    Route::patch('/statuses/{status}', [DashboardController::class, 'statusUpdate'])->name('statuses.update');
    Route::delete('/statuses/{status}', [DashboardController::class, 'statusDelete'])->name('statuses.delete');
    
    // Priorities
    Route::get('/priorities', [DashboardController::class, 'priorities'])->name('priorities.index');
    Route::get('/priorities/create', [DashboardController::class, 'priorityCreate'])->name('priorities.create');
    Route::post('/priorities', [DashboardController::class, 'priorityStore'])->name('priorities.store');
    Route::get('/priorities/{priority}/edit', [DashboardController::class, 'priorityEdit'])->name('priorities.edit');
    Route::patch('/priorities/{priority}', [DashboardController::class, 'priorityUpdate'])->name('priorities.update');
    Route::delete('/priorities/{priority}', [DashboardController::class, 'priorityDelete'])->name('priorities.delete');
    
    // Roles
    Route::get('/roles', [DashboardController::class, 'roles'])->name('roles.index');
    Route::get('/roles/create', [DashboardController::class, 'roleCreate'])->name('roles.create');
    Route::post('/roles', [DashboardController::class, 'roleStore'])->name('roles.store');
    Route::get('/roles/{role}/edit', [DashboardController::class, 'roleEdit'])->name('roles.edit');
    Route::patch('/roles/{role}', [DashboardController::class, 'roleUpdate'])->name('roles.update');
    Route::delete('/roles/{role}', [DashboardController::class, 'roleDelete'])->name('roles.delete');
    
    // Spaces
    Route::get('/spaces', [DashboardController::class, 'spaces'])->name('spaces.index');
    Route::get('/spaces/create', [DashboardController::class, 'spaceCreate'])->name('spaces.create');
    Route::post('/spaces', [DashboardController::class, 'spaceStore'])->name('spaces.store');
    Route::get('/spaces/{space}/edit', [DashboardController::class, 'spaceEdit'])->name('spaces.edit');
    Route::patch('/spaces/{space}', [DashboardController::class, 'spaceUpdate'])->name('spaces.update');
    Route::delete('/spaces/{space}', [DashboardController::class, 'spaceDelete'])->name('spaces.delete');

    // App releases (updates)
    Route::get('/releases', [ReleaseController::class, 'index'])->name('releases.index');
    Route::post('/releases', [ReleaseController::class, 'store'])->name('releases.store');
    Route::delete('/releases/{platform}', [ReleaseController::class, 'destroy'])->name('releases.delete');
});
