<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Админ-панель') - Tasksbase-Knowledgebase</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #333; }
        
        /* Header */
        .admin-header {
            background: linear-gradient(135deg, #0052CC 0%, #0747A6 100%);
            color: white;
            padding: 0 2rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-header h1 { font-size: 1.5rem; font-weight: 600; }
        .admin-header .user-info { display: flex; align-items: center; gap: 1rem; }
        .admin-header a { color: white; text-decoration: none; }
        
        /* Navigation */
        .admin-nav {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0 2rem;
            display: flex;
            gap: 0;
        }
        .admin-nav a {
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .admin-nav a:hover { color: #0052CC; background: #f5f7fa; }
        .admin-nav a.active { color: #0052CC; border-bottom-color: #0052CC; }
        
        /* Content */
        .admin-content { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        
        /* Alerts */
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #e6f9e6; color: #2d7a2d; border: 1px solid #a3d9a3; }
        .alert-error { background: #ffe6e6; color: #c00; border: 1px solid #ffa3a3; }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #0052CC; color: white; }
        .btn-primary:hover { background: #0065FF; }
        .btn-danger { background: #DE350B; color: white; }
        .btn-danger:hover { background: #FF5C34; }
        .btn-secondary { background: #EBECF0; color: #42526E; }
        .btn-secondary:hover { background: #DFE1E6; }
        .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }
        
        /* Table */
        .data-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .data-table table { width: 100%; border-collapse: collapse; }
        .data-table th {
            background: #FAFBFC;
            padding: 0.875rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #42526E;
            border-bottom: 2px solid #EBECF0;
        }
        .data-table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #EBECF0;
        }
        .data-table tr:hover { background: #F4F5F7; }
        
        /* Forms */
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #42526E; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #DFE1E6;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0052CC;
            box-shadow: 0 0 0 2px rgba(0,82,204,0.2);
        }
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        .form-card h2 { margin-bottom: 1.5rem; color: #172B4D; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card .stat-value { font-size: 2rem; font-weight: 700; color: #0052CC; }
        .stat-card .stat-label { font-size: 0.875rem; color: #6B778C; margin-top: 0.25rem; }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success { background: #E3FCEF; color: #006644; }
        .badge-warning { background: #FFFBE5; color: #B78700; }
        .badge-danger { background: #FFEBE6; color: #BF2600; }
        .badge-info { background: #DEEBFF; color: #0052CC; }
        
        /* Filters */
        .filters {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .filters input,
        .filters select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #DFE1E6;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        /* Pagination */
        .pagination { margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.5rem; }
        .pagination a {
            padding: 0.5rem 0.875rem;
            background: white;
            border: 1px solid #DFE1E6;
            border-radius: 6px;
            text-decoration: none;
            color: #0052CC;
        }
        .pagination a:hover { background: #EBECF0; }
        .pagination a.active { background: #0052CC; color: white; border-color: #0052CC; }
    </style>
    @stack('styles')
</head>
<body>
    <header class="admin-header">
        <h1>🛠️ Админ-панель</h1>
        <div class="user-info">
            <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            <a href="/" target="_blank">🌐 Сайт</a>
            <form method="POST" action="/admin/logout" style="display: inline;">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white;">Выйти</button>
            </form>
        </div>
    </header>
    
    <nav class="admin-nav">
        <a href="{{ route('admin.dashboard') }}" class="{{ !request()->routeIs('admin.*') ? 'active' : '' }}">📊 Главная</a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">👥 Пользователи</a>
        <a href="{{ route('admin.statuses.index') }}" class="{{ request()->routeIs('admin.statuses.*') ? 'active' : '' }}">📋 Статусы</a>
        <a href="{{ route('admin.priorities.index') }}" class="{{ request()->routeIs('admin.priorities.*') ? 'active' : '' }}">🎯 Приоритеты</a>
        <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">🔐 Роли</a>
        <a href="{{ route('admin.spaces.index') }}" class="{{ request()->routeIs('admin.spaces.*') ? 'active' : '' }}">📚 Пространства</a>
        <a href="{{ route('admin.releases.index') }}" class="{{ request()->routeIs('admin.releases.*') ? 'active' : '' }}">🚀 Релизы</a>
    </nav>
    
    <main class="admin-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        
        @yield('content')
    </main>
    
    @stack('scripts')
</body>
</html>
