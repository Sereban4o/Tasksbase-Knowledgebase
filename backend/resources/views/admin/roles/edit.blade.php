@extends('admin.layout')

@section('title', 'Редактировать роль')

@section('content')
<div class="form-card">
    <h2>✏️ Редактировать роль</h2>
    
    <form method="POST" action="{{ route('admin.roles.update', $role->id) }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="PATCH">
        
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" required>
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3">{{ old('description', $role->description) }}</textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">💾 Сохранить</button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
