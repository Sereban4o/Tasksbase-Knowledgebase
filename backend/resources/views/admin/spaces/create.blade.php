@extends('admin.layout')

@section('title', 'Создать пространство')

@section('content')
<h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">📚 Создать пространство</h2>

<div class="form-card">
    <form method="POST" action="{{ route('admin.spaces.store') }}">
        @csrf
        
        <div class="form-group">
            <label for="key">Ключ *</label>
            <input type="text" id="key" name="key" value="{{ old('key') }}" placeholder="Например: DOCS" required>
            @error('key')<span style="color: #c00; font-size: 0.875rem;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="name">Название *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Название пространства" required>
            @error('name')<span style="color: #c00; font-size: 0.875rem;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="3" placeholder="Описание пространства">{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
            <label for="type">Тип *</label>
            <select id="type" name="type" required>
                <option value="">Выберите тип</option>
                <option value="project" {{ old('type') === 'project' ? 'selected' : '' }}>Проект</option>
                <option value="personal" {{ old('type') === 'personal' ? 'selected' : '' }}>Личное</option>
                <option value="knowledge" {{ old('type') === 'knowledge' ? 'selected' : '' }}>Знания</option>
            </select>
            @error('type')<span style="color: #c00; font-size: 0.875rem;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="visibility">Видимость *</label>
            <select id="visibility" name="visibility" required>
                <option value="">Выберите видимость</option>
                <option value="private" {{ old('visibility') === 'private' ? 'selected' : '' }}>Приватное</option>
                <option value="public" {{ old('visibility') === 'public' ? 'selected' : '' }}>Публичное</option>
                <option value="team" {{ old('visibility') === 'team' ? 'selected' : '' }}>Команда</option>
            </select>
            @error('visibility')<span style="color: #c00; font-size: 0.875rem;">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="avatar">URL аватара</label>
            <input type="text" id="avatar" name="avatar" value="{{ old('avatar') }}" placeholder="https://example.com/avatar.png">
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Создать</button>
            <a href="{{ route('admin.spaces.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
