@extends('admin.layout')

@section('title', 'Главная')

@section('content')
<h1 style="margin-bottom: 1.5rem; color: #172B4D;">Обзор системы</h1>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value">{{ $stats['total_users'] }}</div>
        <div class="stat-label">Всего пользователей</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #00875A;">{{ $stats['active_users'] }}</div>
        <div class="stat-label">Активных</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #DE350B;">{{ $stats['suspended_users'] }}</div>
        <div class="stat-label">Заблокированных</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #FF9800;">{{ $stats['total_issues'] }}</div>
        <div class="stat-label">Задач</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: #6B778C;">{{ $stats['total_spaces'] }}</div>
        <div class="stat-label">Пространств Knowledgebase</div>
    </div>
</div>

<div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <h2 style="margin-bottom: 1rem; color: #172B4D;">Быстрые действия</h2>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">➕ Добавить пользователя</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">👥 Управление пользователями</a>
        <a href="{{ route('admin.statuses.index') }}" class="btn btn-secondary">📋 Управление статусами</a>
        <a href="{{ route('admin.priorities.index') }}" class="btn btn-secondary">🎯 Управление приоритетами</a>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">🔐 Управление ролями</a>
    </div>
</div>
@endsection
