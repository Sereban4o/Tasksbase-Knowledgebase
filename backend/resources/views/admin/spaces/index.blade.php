@extends('admin.layout')

@section('title', 'Пространства')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2 style="font-size: 1.5rem; font-weight: 600;">📚 Пространства</h2>
    <a href="{{ route('admin.spaces.create') }}" class="btn btn-primary">+ Создать пространство</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Ключ</th>
                <th>Название</th>
                <th>Тип</th>
                <th>Видимость</th>
                <th>Статей</th>
                <th>Участников</th>
                <th>Создатель</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($spaces as $space)
            <tr>
                <td><strong>{{ $space->key }}</strong></td>
                <td>{{ $space->name }}</td>
                <td>
                    <span class="badge badge-info">{{ ucfirst($space->type) }}</span>
                </td>
                <td>
                    <span class="badge {{ $space->visibility === 'public' ? 'badge-success' : ($space->visibility === 'team' ? 'badge-warning' : 'badge-danger') }}">
                        {{ ucfirst($space->visibility) }}
                    </span>
                </td>
                <td>{{ $space->pages_count }}</td>
                <td>{{ $space->members_count }}</td>
                <td>{{ $space->creator->first_name ?? '' }} {{ $space->creator->last_name ?? '' }}</td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('admin.spaces.edit', $space->id) }}" class="btn btn-sm btn-secondary">✏️</a>
                        <form action="{{ route('admin.spaces.delete', $space->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить это пространство?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 2rem; color: #666;">
                    Пространства не найдены. Создайте первое пространство.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($spaces->hasPages())
<div class="pagination" style="margin-top: 1.5rem;">
    {{ $spaces->links() }}
</div>
@endif
@endsection
