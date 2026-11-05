@extends('admin.layout')

@section('title', 'Редактировать приоритет')

@section('content')
<div class="form-card">
    <h2>✏️ Редактировать приоритет</h2>
    
    <form method="POST" action="{{ route('admin.priorities.update', $priority->id) }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="PATCH">
        
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" value="{{ old('name', $priority->name) }}" required>
        </div>
        
        <div class="form-group">
            <label>Ключ *</label>
            <input type="text" name="key" value="{{ old('key', $priority->key) }}" required>
        </div>
        
        <div class="form-group">
            <label>Цвет *</label>
            <input type="color" name="color" value="{{ old('color', $priority->color) }}" style="height: 40px; cursor: pointer;">
        </div>
        
        <div class="form-group">
            <label>Порядок</label>
            <input type="number" name="order" value="{{ old('order', $priority->order) }}" min="0">
        </div>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_used" value="1" {{ old('is_used', $priority->is_used) ? 'checked' : '' }} style="width: auto;">
                Используется в задачах
            </label>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">💾 Сохранить</button>
            <a href="{{ route('admin.priorities.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
