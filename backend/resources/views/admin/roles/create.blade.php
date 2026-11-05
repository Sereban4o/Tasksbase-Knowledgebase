@extends('admin.layout')

@section('title', 'Добавить роль')

@section('content')
<div class="form-card">
    <h2>➕ Добавить роль</h2>
    
    <form method="POST" action="{{ route('admin.roles.store') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Например: Manager">
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3" placeholder="Описание роли...">{{ old('description') }}</textarea>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">💾 Создать</button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
