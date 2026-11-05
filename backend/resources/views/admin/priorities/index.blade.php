@extends('admin.layout')

@section('title', 'Приоритеты задач')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="color: #172B4D;">Приоритеты задач</h1>
    <a href="{{ route('admin.priorities.create') }}" class="btn btn-primary">➕ Добавить приоритет</a>
</div>

<div class="filters">
    <input type="text" placeholder="Поиск по названию или ключу..." name="search" value="{{ request('search') }}" style="flex: 1; max-width: 300px;">
    <button type="submit" class="btn btn-primary btn-sm">🔍 Найти</button>
    <a href="{{ route('admin.priorities.index') }}" class="btn btn-secondary btn-sm">✕ Сбросить</a>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Ключ</th>
                <th>Цвет</th>
                <th>Порядок</th>
                <th>Используется</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($priorities as $priority)
            <tr>
                <td>{{ $priority->id }}</td>
                <td><strong>{{ $priority->name }}</strong></td>
                <td><span class="badge badge-info">{{ $priority->key }}</span></td>
                <td>
                    <span style="display: inline-block; width: 24px; height: 24px; background: {{ $priority->color }}; border-radius: 4px; vertical-align: middle;"></span>
                    {{ $priority->color }}
                </td>
                <td>{{ $priority->order }}</td>
                <td>
                    @if($priority->is_used)
                        <span class="badge badge-success">Да</span>
                    @else
                        <span class="badge badge-warning">Нет</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('admin.priorities.edit', $priority->id) }}" class="btn btn-secondary btn-sm">✏️</a>
                        <form method="POST" action="{{ route('admin.priorities.delete', $priority->id) }}" style="display: inline;"
                              onsubmit="return confirm('Вы уверены?')">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: #6B778C;">Приоритеты не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $priorities->links() }}
</div>
@endsection
