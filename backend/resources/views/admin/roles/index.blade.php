@extends('admin.layout')

@section('title', 'Роли')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="color: #172B4D;">Роли</h1>
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">➕ Добавить роль</a>
</div>

<div class="filters">
    <input type="text" placeholder="Поиск по названию или описанию..." name="search" value="{{ request('search') }}" style="flex: 1; max-width: 300px;">
    <button type="submit" class="btn btn-primary btn-sm">🔍 Найти</button>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-sm">✕ Сбросить</a>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Пользователей</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($roles as $role)
            <tr>
                <td>{{ $role->id }}</td>
                <td><strong>{{ $role->name }}</strong></td>
                <td>{{ $role->description ?? '-' }}</td>
                <td>
                    <span class="badge badge-info">{{ $role->users_count }}</span>
                </td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-secondary btn-sm">✏️</a>
                        @if($role->name !== 'admin' && $role->users_count == 0)
                        <form method="POST" action="{{ route('admin.roles.delete', $role->id) }}" style="display: inline;"
                              onsubmit="return confirm('Вы уверены?')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 2rem; color: #6B778C;">Роли не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $roles->links() }}
</div>
@endsection
