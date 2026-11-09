@extends('admin.layout')

@section('title', 'Релизы приложений')

@section('content')
<h1 style="margin-bottom: 1.5rem; color: #172B4D;">🚀 Релизы приложений</h1>

@if($errors->any())
    <div class="alert alert-error">
        <ul style="margin: 0; padding-left: 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-card" style="max-width: 100%; margin-bottom: 2rem;">
    <h2>Загрузить новый релиз</h2>
    <form method="POST" action="{{ route('admin.releases.store') }}" enctype="multipart/form-data">
        @csrf
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; min-width: 160px;">
                <label>Платформа</label>
                <select name="platform" required>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform }}" {{ old('platform') === $platform ? 'selected' : '' }}>
                            {{ ucfirst($platform) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0; min-width: 170px;">
                <label>Версия (напр. 1.0.2)</label>
                <input type="text" name="version" value="{{ old('version') }}" placeholder="1.0.2" required>
            </div>

            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label>Version code</label>
                <input type="number" name="versionCode" min="0" value="{{ old('versionCode') }}">
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 240px;">
                <label>Описание изменений</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Что нового">
            </div>

            <div class="form-group" style="margin-bottom: 0; min-width: 240px;">
                <label>Файл релиза</label>
                <input type="file" name="file" required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <button type="submit" class="btn btn-primary">⬆️ Загрузить</button>
            </div>
        </div>
        <p style="margin-top: 1rem; color: #6B778C; font-size: 0.8125rem;">
            Каталог релизов: <code>{{ $path }}</code>. Разрешённые типы: APK/AAB (Android), MSI/EXE/ZIP (Windows).
        </p>
    </form>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Платформа</th>
                <th>Версия</th>
                <th>Version code</th>
                <th>Файл</th>
                <th>SHA-256</th>
                <th>Загружен</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($releases as $release)
            <tr>
                <td><span class="badge badge-info">{{ ucfirst($release['platform'] ?? '') }}</span></td>
                <td><strong>{{ $release['version'] ?? '—' }}</strong></td>
                <td>{{ $release['versionCode'] ?? '—' }}</td>
                <td style="font-family: monospace; font-size: 0.8125rem;">{{ $release['path'] ?? '—' }}</td>
                <td style="font-family: monospace; font-size: 0.8125rem;">
                    {{ !empty($release['sha256']) ? substr($release['sha256'], 0, 12) . '…' : '—' }}
                </td>
                <td>
                    {{ !empty($release['uploaded_at']) ? \Illuminate\Support\Carbon::parse($release['uploaded_at'])->format('d.m.Y H:i') : '—' }}
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.releases.delete', $release['platform']) }}"
                          style="display: inline;" onsubmit="return confirm('Удалить релиз {{ $release['platform'] }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Удалить</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: #6B778C;">Релизов пока нет</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
