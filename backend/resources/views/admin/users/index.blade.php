@extends('admin.layout')

@section('title', 'Пользователи')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="color: #172B4D;">Пользователи</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">➕ Добавить пользователя</a>
</div>

<div class="filters">
    <input type="text" placeholder="Поиск по имени или email..." name="search" value="{{ request('search') }}" style="flex: 1; max-width: 300px;">
    <select name="status">
        <option value="">Все статусы</option>
        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Активные</option>
        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Заблокированные</option>
        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Ожидающие</option>
    </select>
    <select name="role">
        <option value="">Все роли</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">🔍 Найти</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">✕ Сбросить</a>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Статус</th>
                <th>Дата создания</th>
                <th style="width: 200px;">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>
                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                </td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="badge badge-info">{{ $user->role->name ?? 'Нет роли' }}</span>
                </td>
                <td>
                    @if($user->status === 'active')
                        <span class="badge badge-success">Активный</span>
                    @elseif($user->status === 'suspended')
                        <span class="badge badge-danger">Заблокирован</span>
                    @else
                        <span class="badge badge-warning">Ожидает</span>
                    @endif
                </td>
                <td>{{ $user->created_at->format('d.m.Y') }}</td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-secondary btn-sm">✏️</a>
                        <form method="POST" action="{{ route('admin.users.toggle-status', $user->id) }}" style="display: inline;">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="PATCH">
                            <button type="submit" class="btn btn-sm {{ $user->status === 'active' ? 'btn-danger' : 'btn-success' }}" 
                                    style="{{ $user->status === 'active' ? 'background: #00875A; color: white;' : 'background: #DE350B; color: white;' }}">
                                {{ $user->status === 'active' ? '🚫' : '✅' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.delete', $user->id) }}" style="display: inline;" 
                              onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: #6B778C;">Пользователи не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $users->links() }}
</div>
@endsection
