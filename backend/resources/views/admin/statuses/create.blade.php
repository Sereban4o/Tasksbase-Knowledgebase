@extends('admin.layout')

@section('title', 'Добавить статус')

@section('content')
<div class="form-card">
    <h2>➕ Добавить статус</h2>
    
    <form method="POST" action="{{ route('admin.statuses.store') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Например: В работе">
        </div>
        
        <div class="form-group">
            <label>Категория</label>
            <input type="text" name="category" value="{{ old('category') }}" placeholder="Например: todo, in_progress">
        </div>
        
        <div class="form-group">
            <label>Цвет *</label>
            <input type="color" name="color" value="{{ old('color', '#0052CC') }}" style="height: 40px; cursor: pointer;">
        </div>
        
        <div class="form-group">
            <label>Порядок</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" step="1" placeholder="0">
            <small style="color: #6B778C;">Меньше — левее на доске</small>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_used" value="1" {{ old('is_used') ? 'checked' : '' }} style="width: auto;">
                Используется в задачах
            </label>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">💾 Создать</button>
            <a href="{{ route('admin.statuses.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
