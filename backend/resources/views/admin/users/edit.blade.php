@extends('admin.layout')

@section('title', 'Редактировать пользователя')

@section('content')
<div class="form-card">
    <h2>✏️ Редактировать пользователя</h2>
    
    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="PATCH">
        
        <div class="form-group">
            <label>Имя *</label>
            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
        </div>
        
        <div class="form-group">
            <label>Фамилия *</label>
            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
        </div>
        
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        </div>
        
        <div class="form-group">
            <label>Новый пароль (оставьте пустым, если не меняете)</label>
            <input type="password" name="password">
        </div>
        
        <div class="form-group">
            <label>Подтверждение пароля</label>
            <input type="password" name="password_confirmation">
        </div>
        
        <div class="form-group">
            <label>Роль *</label>
            <select name="role_id" required>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div class="form-group">
            <label>Статус *</label>
            <select name="status" required>
                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Активный</option>
                <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Заблокирован</option>
                <option value="pending" {{ old('status', $user->status) === 'pending' ? 'selected' : '' }}>Ожидает</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">💾 Сохранить</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
