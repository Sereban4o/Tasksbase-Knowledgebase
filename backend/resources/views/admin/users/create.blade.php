@extends('admin.layout')

@section('title', 'Добавить пользователя')

@section('content')
<div class="form-card">
    <h2>➕ Добавить пользователя</h2>
    
    <form method="POST" action="{{ route('admin.users.store') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        
        <div class="form-group">
            <label>Имя *</label>
            <input type="text" name="first_name" value="{{ old('first_name') }}" required>
        </div>
        
        <div class="form-group">
            <label>Фамилия *</label>
            <input type="text" name="last_name" value="{{ old('last_name') }}" required>
        </div>
        
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
        </div>
        
        <div class="form-group">
            <label>Пароль *</label>
            <input type="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label>Подтверждение пароля *</label>
            <input type="password" name="password_confirmation" required>
        </div>
        
        <div class="form-group">
            <label>Роль *</label>
            <select name="role_id" required>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">💾 Создать</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
