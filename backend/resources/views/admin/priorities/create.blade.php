@extends('admin.layout')

@section('title', 'Добавить приоритет')

@section('content')
<div class="form-card">
    <h2>➕ Добавить приоритет</h2>
    
    <form method="POST" action="{{ route('admin.priorities.store') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Например: Высокий">
        </div>
        
        <div class="form-group">
            <label>Ключ *</label>
            <input type="text" name="key" value="{{ old('key') }}" required placeholder="Например: HIGH">
        </div>
        
        <div class="form-group">
            <label>Цвет *</label>
            <input type="color" name="color" value="{{ old('color', '#FF9800') }}" style="height: 40px; cursor: pointer;">
        </div>
        
        <div class="form-group">
            <label>Порядок</label>
            <input type="number" name="order" value="{{ old('order', 0) }}" min="0">
        </div>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_used" value="1" {{ old('is_used') ? 'checked' : '' }} style="width: auto;">
                Используется в задачах
            </label>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">💾 Создать</button>
            <a href="{{ route('admin.priorities.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
