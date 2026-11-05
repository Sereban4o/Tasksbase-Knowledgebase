@extends('admin.layout')

@section('title', 'Статусы задач')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="color: #172B4D;">Статусы задач</h1>
    <a href="{{ route('admin.statuses.create') }}" class="btn btn-primary">➕ Добавить статус</a>
</div>

<div class="filters">
    <input type="text" placeholder="Поиск по названию..." name="search" value="{{ request('search') }}" style="flex: 1; max-width: 300px;">
    <select name="category">
        <option value="">Все категории</option>
        @foreach($categories as $category)
            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">🔍 Найти</button>
    <a href="{{ route('admin.statuses.index') }}" class="btn btn-secondary btn-sm">✕ Сбросить</a>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Категория</th>
                <th>Цвет</th>
                <th>Используется</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statuses as $status)
            <tr>
                <td>{{ $status->id }}</td>
                <td><strong>{{ $status->name }}</strong></td>
                <td>{{ $status->category ?? '-' }}</td>
                <td>
                    <span style="display: inline-block; width: 24px; height: 24px; background: {{ $status->color }}; border-radius: 4px; vertical-align: middle;"></span>
                    {{ $status->color }}
                </td>
                <td>
                    @if($status->is_used)
                        <span class="badge badge-success">Да</span>
                    @else
                        <span class="badge badge-warning">Нет</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('admin.statuses.edit', $status->id) }}" class="btn btn-secondary btn-sm">✏️</a>
                        <form method="POST" action="{{ route('admin.statuses.delete', $status->id) }}" style="display: inline;"
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
                <td colspan="6" style="text-align: center; padding: 2rem; color: #6B778C;">Статусы не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $statuses->links() }}
</div>
@endsection
