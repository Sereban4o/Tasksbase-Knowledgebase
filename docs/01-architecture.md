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
- **Realtime:** Laravel Reverb (Pusher protocol) + ntfy (push)
- **UI:** MUI (Material-UI)
- **Drag & Drop:** react-dnd 16.0.1
- **Клиенты:** React SPA, Android (Kotlin/Compose), Windows (Compose Desktop)

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
│   │   │   │   │   ├── Chat/
│   │   │   │   │   ├── Integration/OneC/
│   │   │   │   │   └── Admin/
│   │   │   │   └── Admin/    # Blade контроллеры
│   │   │   ├── Middleware/   # Кастомные мидлвары
│   │   │   └── Requests/     # Form Requests
│   │   ├── Models/           # Eloquent модели
│   │   ├── Services/         # Бизнес-логика
│   │   │   ├── Integration/OneC/  # Синхронизация с 1С
│   │   │   ├── ReleaseService.php # Релизы обновлений
│   │   │   └── DmRepairService.php
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
├── updates/                  # Релизы приложений (latest.json + бинарники)
├── android/                  # Android и Windows (Compose Desktop) клиенты
├── scripts/                  # publish_update.ps1 и служебные скрипты
├── docker-compose.yml        # Docker окружение (dev)
├── docker-compose.prod.yml   # Docker окружение (prod)
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

### 3.4 Интеграция с 1С (machine-to-machine)

Отдельный контур `/api/v1/integration/1c/*` без пользовательской сессии:
- мидлвар `one.c` (`VerifyOneCRequest`);
- Bearer-токен (`ONE_C_TOKEN`) и/или HMAC-подпись `X-Signature = hmac_sha256("{X-Timestamp}.{rawBody}", ONE_C_HMAC_SECRET)`;
- окно анти-реплея `ONE_C_TIMESTAMP_TOLERANCE` (по умолчанию 300с);
- опциональный IP-allowlist `ONE_C_ALLOWED_IPS`;
- rate limit 120 req/min;
- fail-closed: если интеграция включена, но креденшелы не заданы — `503`.

### 3.5 Релизы приложений

Загрузка обновлений (Android/Windows) администратором через **Laravel Blade-админку**: `/admin/releases` (сессия + `EnsureUserIsAdmin`). Контроллер использует `ReleaseService`, сохраняет файл в `updates/<platform>/latest.json` и бинарник. Каталог отдаётся nginx по `/updates/` (с `Content-Disposition: attachment`).

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
| GET | `/admin/releases` | Список релизов (Blade) |
| POST | `/admin/releases` | Загрузить релиз (multipart) |
| DELETE | `/admin/releases/{platform}` | Удалить релиз |

### Chat Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/chat/my` | Мои чаты |
| POST | `/api/v1/chat` | Создать чат (DM/группа/канал) |
| GET | `/api/v1/chat/{id}` | Чат с участниками |
| PATCH | `/api/v1/chat/{id}` | Переименовать/обновить |
| DELETE | `/api/v1/chat/{id}` | Удалить чат для всех участников |
| POST | `/api/v1/chat/{id}/members` | Добавить участников |
| POST | `/api/v1/chat/{id}/members/remove` | Удалить участника (admin) |
| POST | `/api/v1/chat/{id}/leave` | Покинуть чат |
| GET/POST | `/api/v1/chat/{id}/messages` | Лента/отправка сообщений |
| PATCH/DELETE | `/api/v1/chat/{id}/messages/{m}` | Редактировать/удалить сообщение |

### 1С Integration (token + HMAC)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/integration/1c/users/sync` | Синхронизация пользователей |
| POST | `/api/v1/integration/1c/issues/push` | Приём задач из 1С |
| GET/POST | `/api/v1/integration/1c/issues/pull` | Выгрузка задач в 1С |
| POST | `/api/v1/integration/1c/issues/{externalId}/comment` | Комментарий из 1С |
| POST | `/api/v1/integration/1c/notifications` | Уведомление из 1С |
| POST | `/api/v1/integration/1c/chats/messages` | Сообщение из 1С (как уведомление) |

## 5. Кэширование и очереди

- **Cache Driver:** `file` (production: `redis`)
- **Queue Connection:** `redis`
- **Worker:** отдельный контейнер/процесс
- **Scheduled Tasks:** `php artisan schedule:run` (каждую минуту)

## 6. CORS Configuration

- Frontend: `http://localhost:5173`
- Vite proxy: `/api` → `http://app:8000`
- Sanctum CSRF работает через cookies

## 7. Переменные окружения (интеграции)

```bash
# 1С
ONE_C_ENABLED=false
ONE_C_BASE_URL=
ONE_C_TOKEN=
ONE_C_HMAC_SECRET=
ONE_C_ALLOWED_IPS=          # через запятую, пусто = без ограничения
ONE_C_TIMESTAMP_TOLERANCE=300
ONE_C_TIMEOUT=30

# Релизы приложений
UPDATES_PATH=/var/www/updates
UPDATES_MAX_SIZE_KB=512000
UPDATES_VERIFY_SIGNATURE=false
UPDATES_VERIFY_ANDROID=      # напр. apksigner verify --print-certs %s
UPDATES_VERIFY_WINDOWS=      # напр. osslsigncode verify -in %s
```
