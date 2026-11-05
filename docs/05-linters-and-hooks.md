# Линтеры и Pre-commit Hooks

## 📋 Обзор

Проект использует автоматическую проверку кода перед каждым коммитом.

## 🔧 Настройки

### Frontend (React + TypeScript)

**Инструменты:**
- **ESLint** — проверка кода
- **Prettier** — форматирование
- **Husky + lint-staged** — pre-commit хуки

**Конфигурационные файлы:**
```
frontend/
├── .eslintrc.cjs           # ESLint config
├── .prettierrc             # Prettier config
├── .prettierignore         # Files to ignore
├── .husky/
│   └── pre-commit          # Pre-commit hook
└── package.json            # lint-staged config
```

**Команды:**

```bash
cd frontend

# Проверка кода
npm run lint              # ESLint
npm run format:check      # Prettier

# Исправление
npm run lint:fix          # ESLint auto-fix
npm run format            # Prettier auto-format
```

**Правила ESLint:**
- PSR-12 для PHP
- TypeScript strict mode
- React Hooks правила
- Prettier integration

### Backend (Laravel)

**Инструменты:**
- **Laravel Pint** — форматирование PHP

**Конфигурационные файлы:**
```
backend/
├── pint.json             # Pint config
├── .git-hooks/           # Git hooks template
└── composer.json         # Scripts
```

**Команды:**

```bash
cd backend

# Проверка
composer lint             # Pint --test

# Исправление
composer lint:fix         # Pint
```

**Правила Pint:**
- PSR-12 preset
- Single line trailing commas
- Ordered imports
- No unused imports
- Laravel conventions

## 🎯 Pre-commit Hooks

### Автоматическая проверка

Перед **каждым** коммитом автоматически запускаются:

1. **Prettier** — форматирование TS/TSX/CSS
2. **ESLint** — проверка TypeScript кода
3. **Laravel Pint** — проверка PHP кода

**Если есть ошибки — коммит не состоится!**

### Установка

```bash
# Frontend (автоматически через Husky)
cd frontend
npx husky init

# Backend (ручная установка)
cd backend
cp .git-hooks/* .git/hooks/
chmod +x .git/hooks/*
```

### Исключения

Если нужно пропустить проверку (не рекомендуется):

```bash
git commit --no-verify -m "message"
```

## 📝 Стиль кода

### TypeScript

```typescript
// ✅ Правильно
const user: User = {
  id: 1,
  name: 'John',
  email: 'john@example.com',
};

// ❌ Неправильно
const user = {
  id: 1,
  name: 'John',
  email: 'john@example.com'
}
```

### PHP

```php
// ✅ Правильно
public function store(Request $request): CreatedResponse
{
    $validated = $request->validate([
        'name' => 'required|string',
    ]);

    return response()->json($validated, 201);
}

// ❌ Неправильно
public function store(Request $request)
{
    $data = $request->validate([...]);
    return $data;
}
```

## 🚀 CI/CD Integration

### GitHub Actions

```yaml
name: Lint

on: [push, pull_request]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Frontend Lint
        run: |
          cd frontend
          npm ci
          npm run lint
          npm run format:check

      - name: Backend Lint
        run: |
          cd backend
          composer install
          composer lint
```

## 🔍 Отладка

### Отключить хуки временно

```bash
# Добавить в .git/config
[core]
    hooksPath = /dev/null  # Linux/Mac
    hooksPath = NUL        # Windows
```

### Проверить работу хуков

```bash
# Тестовый коммит
echo "test" > test.txt
git add test.txt
git commit -m "test: hooks test"

# Проверить вывод
# Должно быть:
# 🚀 Running pre-commit hooks...
# ✅ All checks passed!
```

## 📚 Документация

- [ESLint Docs](https://eslint.org/)
- [Prettier Docs](https://prettier.io/)
- [Husky Docs](https://typicode.github.io/husky/)
- [Laravel Pint](https://laravel.com/docs/pint)
