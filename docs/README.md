# Документация проекта tasksbase-Knowledgebase

## 📚 Содержание

| Документ | Описание |
|----------|----------|
| [01. Архитектура](./01-architecture.md) | Общая архитектура, стек технологий, аутентификация, API endpoints |
| [02. Схема базы данных](./02-database-schema.md) | ER-диаграмма, описание таблиц, индексы, связи |
| [03. WebSocket события](./03-websocket-events.md) | Real-time events, каналы, frontend integration |
| [04. Руководство по вкладу](./04-contribution-guide.md) | Стиль кода, git workflow, добавление функций, тестирование |
| [05. Линтеры и хуки](./05-linters-and-hooks.md) | ESLint, Prettier, Laravel Pint, pre-commit hooks |
| [06. Анализ и улучшения](./06-improvements.md) | Критические баги, производительность, архитектура |

## 🚀 Быстрый старт

```bash
# 1. Запустить Docker
docker compose up -d

# 2. Backend
cd backend
composer install
php artisan migrate --seed

# 3. Frontend
cd ../frontend
npm install
npm run dev

# 4. Открыть
# Frontend: http://localhost:5173
# Admin: http://localhost:8000/admin/login
```

## 📁 Структура документации

```
docs/
├── README.md               # Эта страница
├── 01-architecture.md      # Архитектура системы, API, интеграции
├── 02-database-schema.md   # ER-диаграмма БД
├── 03-websocket-events.md  # Real-time события (включая чат)
├── 04-contribution-guide.md # Гайд для разработчиков
├── 05-linters-and-hooks.md # Линтеры и хуки
└── 06-improvements.md      # Анализ и выполненные улучшения
```

## 🔧 Стек технологий

- **Backend:** Laravel 12 + PHP 8.3
- **Frontend:** React 18 + TypeScript + Vite
- **Database:** PostgreSQL 16
- **Cache:** Redis 7
- **Search:** Meilisearch
- **Auth:** Laravel Sanctum

## 📝 Стиль кода

- **Backend:** PSR-12 + Laravel Pint
- **Frontend:** ESLint + Prettier
- **Git:** Conventional Commits
- **Branches:** Git Flow

## 🐛 Сообщить о проблеме

Откройте GitHub Issue с меткой `bug`.

## 💡 Предложить улучшение

Откройте GitHub Issue с меткой `enhancement`.
