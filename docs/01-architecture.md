# Архитектура проекта tasksbase-Knowledgebase Clone

## 1. Общее описание

Полнофункциональный клон tasksbase + Knowledgebase с канбан-досками, управлением задачами и системой страниц.

**Стек технологий:**
- **Backend:** Laravel 12 + PHP 8.3
- **Frontend:** React 18 + TypeScript + Vite
- **Database:** PostgreSQL 16
- **Cache/Queue:** Redis 7
- **Search:** Meilisearch
- **Auth:** Laravel Sanctum (stateless)
- **UI:** MUI (Material-UI)
- **Drag & Drop:** react-dnd 16.0.1

## 2. Структура проекта

```
tasksbase-knowledgebase/
├── backend/                  # Laravel 12 API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/      # API контроллеры
│   │   │   │   │   ├── Auth/
│   │   │   │   │   ├── tasksbase/
│   │   │   │   │   ├── Knowledgebase/
│   │   │   │   │   └── Admin/
│   │   │   │   └── Admin/    # Blade контроллеры
│   │   │   ├── Middleware/   # Кастомные мидлвары
│   │   │   └── Requests/     # Form Requests
│   │   ├── Models/           # Eloquent модели
│   │   └── Services/         # Бизнес-логика
│   ├── database/
│   │   ├── migrations/       # Миграции БД
│   │   └── seeders/          # Сидеры
│   ├── resources/views/      # Blade шаблоны
│   ├── routes/
│   │   ├── api.php           # API routes
│   │   └── web.php           # Web routes (Admin Panel)
│   └── public/               # Web root
├── frontend/                 # React 18 SPA
│   ├── src/
│   │   ├── modules/
│   │   │   ├── auth/         # Аутентификация
│   │   │   ├── tasksbase/         # tasksbase модуль
│   │   │   │   ├── boards/   # Канбан доски
│   │   │   │   ├── issues/   # Задачи
│   │   │   │   └── projects/ # Проекты
│   │   │   ├── knowledgebase/   # Knowledgebase модуль
│   │   │   └── shared/       # Общие компоненты
│   │   ├── store/            # Zustand stores
│   │   └── api/              # API клиенты
│   └── dist/                 # Собранный бандл
├── nginx/                    # Конфиг Nginx
├── docker-compose.yml        # Docker окружение
└── docs/                     # Документация
```

## 3. Аутентификация

### 3.1 API (Stateless Sanctum)

- Токен хранится в `localStorage` как `auth_token`
- Отправляется в заголовке `Authorization: Bearer {token}`
- Мидлвар: `StartSanctumSession`
- Эндпоинты:
  - `POST /api/v1/auth/login` — вход
  - `POST /api/v1/auth/logout` — выход
  - `POST /api/v1/auth/refresh` — обновление токена

### 3.2 Admin Panel (Session-based)

- Laravel session cookies
- Мидлвар: `auth` + `EnsureUserIsAdmin`
- URL: `/admin/login`
- Credentials: `admin@example.com` / `password`

### 3.3 Роли

- `admin` (role_id: 1) — полный доступ
- `user` — стандартный доступ к API

## 4. API Endpoints

### tasksbase Module

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/issues` | Список задач | ✅ |
| POST | `/api/v1/issues` | Создать задачу | ✅ |
| GET | `/api/v1/issues/{id}` | Детали задачи | ✅ |
| PATCH | `/api/v1/issues/{id}` | Обновить задачу | ✅ |
| DELETE | `/api/v1/issues/{id}` | Удалить задачу | ✅ |
| POST | `/api/v1/issues/{id}/move` | Переместить (kanban) | ✅ |
| GET | `/api/v1/boards` | Список досок | ✅ |
| POST | `/api/v1/boards` | Создать доску | ✅ |
| GET | `/api/v1/sprints` | Список спринтов | ✅ |

### Knowledgebase Module

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/spaces` | Список пространств | ✅ |
| POST | `/api/v1/spaces` | Создать пространство | ✅ |
| GET | `/api/v1/pages` | Список страниц | ✅ |
| POST | `/api/v1/pages` | Создать страницу | ✅ |
| PATCH | `/api/v1/pages/{id}` | Обновить страницу | ✅ |

### Admin Panel

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin` | Dashboard |
| GET | `/admin/users` | Список пользователей |
| POST | `/admin/users` | Создать пользователя |
| PATCH | `/admin/users/{id}` | Обновить пользователя |

## 5. Кэширование и очереди

- **Cache Driver:** `file` (production: `redis`)
- **Queue Connection:** `redis`
- **Worker:** отдельный контейнер/процесс
- **Scheduled Tasks:** `php artisan schedule:run` (каждую минуту)

## 6. CORS Configuration

- Frontend: `http://localhost:5173`
- Vite proxy: `/api` → `http://app:8000`
- Sanctum CSRF работает через cookies
