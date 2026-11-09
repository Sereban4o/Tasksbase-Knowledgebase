# Анализ проекта и предложения по улучшению

## 📊 Критичность: 🔴 Критические | 🟡 Средние | 🟢 Низкие

---

## 1. 🔴 КРИТИЧЕСКИЕ ПРОБЛЕМЫ

### 1.1 Отсутствие тестов

**Проблема:** В проекте нет ни одного теста.

**Риски:**
- Любое изменение может сломать существующий функционал
- Невозможно безопасно рефакторить
- Нет покрытия критических путей (аутентификация, создание задач)

**Решение:**
```bash
# Добавить PHPUnit тесты
cd backend
php artisan make:test IssueTest
php artisan make:test AuthTest

# Добавить Pest (опционально, более читаемый синтаксис)
composer require --dev pestphp/pest
```

**Приоритетные тесты:**
- `AuthTest` — login, register, refresh token
- `IssueTest` — create, update, delete, permissions
- `BoardTest` — move issue, create board
- `ApiTestCase` — базовый класс для API тестов

---

### 1.2 Баг в методе `transition()` в IssueController

**Файл:** `backend/app/Http/Controllers/Api/tasksbase/IssueController.php`

**Проблема:**
```php
public function transition(Request $request, string $issueId)
{
    $comment = IssueComment::where('issue_id', $issueId)
        ->where(function ($q) use ($commentId) {  // ❌ $commentId не определен!
            $q->where('id', $commentId)
              ->orWhere('parent_comment_id', $commentId);
        })
        // ...
}
```

**Решение:**
```php
public function transition(Request $request, string $issueId)
{
    $validated = $request->validate([
        'comment_id' => 'nullable|exists:issue_comments,id',
    ]);

    $query = IssueComment::where('issue_id', $issueId);
    
    if (!empty($validated['comment_id'])) {
        $query->where(function ($q) use ($validated) {
            $q->where('id', $validated['comment_id'])
              ->orWhere('parent_comment_id', $validated['comment_id']);
        });
    }

    $comment = $query->firstOrFail();
    $comment->delete();

    return response()->json(['message' => 'Comment deleted']);
}
```

---

### 1.3 Дублирование роутов в API

**Файл:** `backend/routes/api.php`

**Проблема:**
```php
// Страница создаётся ДВАЖДЫ
Route::post('/pages', [PageController::class, 'store']);  // line ~110
Route::post('/pages', [PageController::class, 'store']);  // line ~115
```

**Решение:** Удалить дубликат.

---

### 1.4 Тестовые роуты в production коде

**Файл:** `backend/routes/api.php`

**Проблема:**
```php
// Test route
Route::post('/test-body', function() {
    return response()->json([...]);
});

// Token test route
require __DIR__.'/test.php';
```

**Решение:**
- Удалить или перенести в `routes/testing.php`
- Добавить middleware для отключения в production

---

### 1.5 `console.log` в продакшн коде

**Файл:** `frontend/src/modules/shared/api/apiClient.ts`

**Проблема:**
```typescript
console.log('API_BASE_URL:', API_BASE_URL);
console.log('window.location:', window.location.href);
```

**Решение:**
```typescript
// Удалить console.log из продакшн кода
// Или использовать:
const isDev = import.meta.env.DEV;
if (isDev) {
  console.log('API_BASE_URL:', API_BASE_URL);
}
```

---

## 2. 🟡 СРЕДНИЕ ПРОБЛЕМЫ

### 2.1 N+1 запросы в моделях

**Файл:** `backend/app/Models/Issue.php`

**Проблема:**
```php
public function getSubtasksCompletedAttribute(): int
{
    return $this->subtasks()->where('status_id', function ($query) {
        $query->select('id')->from('issue_statuses')
            ->where('category', 'done');
    })->count();  // ❌ Запрос для КАЖДОЙ issue
}
```

**Решение:**
```php
// В контроллере использовать withCount
Issue::withCount([
    'subtasks as subtasks_completed' => function ($query) {
        $query->selectRaw('count(*)')
            ->join('issue_statuses', 'issues.status_id', '=', 'issue_statuses.id')
            ->where('issue_statuses.category', 'done')
            ->whereColumn('issue_statuses.id', 'subtasks.status_id');
    }
])->get();
```

---

### 2.2 Валидация в контроллерах вместо Form Requests

**Проблема:** Валидация размазана по контроллерам.

**Решение:**
```bash
# Создать Form Requests
php artisan make:request StoreIssueRequest
php artisan make:request UpdateIssueRequest
php artisan make:request StoreBoardRequest
```

```php
// app/Http/Requests/StoreIssueRequest.php
class StoreIssueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_key' => 'required|string|exists:projects,key',
            'summary' => 'required|string|max:500',
            // ...
        ];
    }
}

// В контроллере
public function store(StoreIssueRequest $request)
{
    // $request->validated() уже валидирован
}
```

---

### 2.3 Отсутствие пагинации в index()

**Файл:** `backend/app/Http/Controllers/Api/tasksbase/IssueController.php`

**Проблема:**
```php
public function index(Request $request)
{
    $issues = Issue::select('id', 'key', 'summary', ...)
        ->latest()
        ->limit(10)  // ❌ Жёсткий лимит
        ->get();
}
```

**Решение:**
```php
public function index(Request $request)
{
    return Issue::with(['project', 'type', 'status', 'assignee'])
        ->latest()
        ->paginate($request->get('per_page', 20));
}
```

---

### 2.4 Несоответствие WebSocket технологий

**Проблема:**
- Frontend использует `socket.io-client`
- Backend настроен на `pusher` + `redis`
- В документации указан `laravel-echo`

**Решение:** Выбрать ОДНУ технологию:

**Вариант A — Laravel Echo + Pusher (рекомендуется):**
```bash
# Frontend
npm install laravel-echo pusher-js

# Backend
# Уже настроено в broadcasting.php
```

**Вариант B — Socket.IO:**
```bash
# Backend - установить пакет
composer require beyondcode/laravel-websockets

# Frontend
# Уже есть socket.io-client
```

---

### 2.5 Дублирование Dockerfile

**Проблема:**
- `Dockerfile` настроен для PHP-FPM
- `docker-compose.yml` использует built-in PHP server
- `worker` использует тот же Dockerfile

**Решение:**
```dockerfile
# Dockerfile.production
FROM php:8.3-fpm

# ... установка зависимостей ...

# PHP-FPM для production
CMD ["php-fpm"]
```

```yaml
# docker-compose.prod.yml
services:
  app:
    build:
      context: ./backend
      dockerfile: Dockerfile.production
    # PHP-FPM + Nginx
  
  worker:
    command: php artisan queue:work
```

---

### 2.6 Отсутствие health checks

**Проблема:** Docker не проверяет состояние сервисов.

**Решение:**
```yaml
# docker-compose.yml
services:
  app:
    healthcheck:
      test: ["CMD", "php", "/var/www/html/artisan", "up"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  postgres:
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      timeout: 5s
      retries: 5
```

---

### 2.7 `composer.lock` в .gitignore

**Файл:** `.gitignore`

**Проблема:**
```
# Composer
composer.lock  # ❌ Игнорируется
```

**Решение:**
```
# Composer (только для библиотек)
backend/vendor/
!backend/composer.lock  # ✅ Коммитить
```

---

## 3. 🟢 НИЗКИЕ / УЛУЧШЕНИЯ

### 3.1 Кэширование роутов

**Проблема:** Роуты кэшируются, но при удалении `routes.php` кэш остаётся.

**Решение:**
```bash
# Добавить в deploy скрипт
php artisan route:clear
php artisan route:cache
```

---

### 3.2 Оптимизация загрузки изображений

**Проблема:** Аватары и файлы загружаются без оптимизации.

**Решение:**
```bash
# Добавить пакет для обработки изображений
composer require intervention/image
```

```php
// В сервис провайдере
$image = Image::make($request->file('avatar'))
    ->resize(400, 400)
    ->optimize();
```

---

### 3.3 Rate Limiting

**Проблема:** Нет ограничений на запросы.

**Решение:**
```php
// bootstrap/app.php
->withRouting(
    // ...
)
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleAPI('60,1');  // 60 requests per minute
})
```

---

### 3.4 API Versioning

**Проблема:** Версия `v1` встроена в путь, но нет стратегии миграции.

**Решение:**
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    // Текущая версия
});

// Future: routes/api-v2.php
Route::prefix('v2')->group(function () {
    // Новая версия с breaking changes
});
```

---

### 3.5 Логирование запросов

**Проблема:** Нет логирования API запросов.

**Решение:**
```bash
# Добавить пакет
composer require spatie/laravel-activitylog
```

```php
// Middleware
class LogApiRequest
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        ActivityLog::create([
            'event' => 'api_request',
            'subject_type' => ApiRequest::class,
            'properties' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->status(),
                'user_id' => Auth::id(),
            ],
        ]);
        
        return $response;
    }
}
```

---

### 3.6 Структура frontend

**Текущая проблема:** Нет разделения на containers/presenters.

**Рекомендуемая структура:**
```
frontend/src/modules/tasksbase/issues/
├── api/
│   ├── issuesApi.ts
│   └── types.ts
├── components/
│   ├── IssueCard.tsx
│   ├── IssueForm.tsx
│   └── IssueList.tsx
├── containers/
│   ├── KanbanBoard.tsx      # Бизнес-логика
│   └── IssueDetail.tsx
├── hooks/
│   ├── useIssues.ts
│   └── useIssueActions.ts
└── store/
    └── issueStore.ts
```

---

### 3.7 Environment Variables

**Проблема:** Hardcoded значения в коде.

**Решение:**
```bash
# .env.example
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME="tasksbaseKnowledgebase"
VITE_MAX_UPLOAD_SIZE=52428800  # 50MB
```

```typescript
// frontend/src/config/env.ts
export const config = {
  apiUrl: import.meta.env.VITE_API_URL,
  appName: import.meta.env.VITE_APP_NAME,
  maxUploadSize: parseInt(import.meta.env.VITE_MAX_UPLOAD_SIZE),
};
```

---

### 3.8 Документация API

**Проблема:** Нет Swagger/OpenAPI документации.

**Решение:**
```bash
# Backend
composer require darkaonline/l5-swagger

# Генерация документации через аннотации
/**
 * @OA\Post(
 *     path="/api/v1/issues",
 *     summary="Create issue",
 *     @OA\RequestBody(
 *         @OA\MediaType(mediaType="application/json")
 *     ),
 *     @OA\Response(response=201)
 * )
 */
```

---

## 4. 📋 Чеклист приоритетов

### 🔴 Срочно (до следующего спринта)
- [ ] Исправить баг в `transition()`
- [ ] Удалить тестовые роуты
- [ ] Удалить дубликат роута pages
- [ ] Убрать `console.log` из продакшн
- [ ] Добавить базовые API тесты

### 🟡 В течение месяца
- [ ] Внедрить Form Requests
- [ ] Добавить пагинацию
- [ ] Исправить N+1 запросы
- [ ] Выбрать единую WebSocket технологию
- [ ] Добавить health checks
- [ ] Закоммитить composer.lock

### 🟢 По мере возможностей
- [ ] Добавить Swagger документацию
- [ ] Внедрить rate limiting
- [ ] Оптимизировать загрузку файлов
- [ ] Рефакторить структуру frontend
- [ ] Добавить CI/CD пайплайн
- [ ] Настроить мониторинг (Sentry)

---

## ✅ Выполненные улучшения

### Спринт 1: Стабильность (завершён)
- [x] Исправлен баг в `transition()` - undefined variable
- [x] Удалены тестовые роуты из production
- [x] Удалён дубликат роута pages
- [x] Убраны console.log из продакшн кода
- [x] Установлен Laravel Echo и Pusher JS

### Спринт 2: Производительность (завершён)
- [x] Исправлены N+1 запросы в Issue model
- [x] Добавлена полная пагинация с фильтрами
- [x] Внедрены Form Request классы для валидации

### Спринт 3: Архитектура (завершён)
- [x] Выбрана технология WebSocket (Laravel Echo + Pusher)
- [x] Переписан useLaravelEcho hook
- [x] Добавлен rate limiting (60 req/min)

### Спринт 4: DevOps (завершён)
- [x] Добавлены health checks в Docker
- [x] Исправлен .gitignore для composer.lock
- [x] Установлен l5-swagger для API документации
- [x] Созданы базовые тесты (IssueTest, AuthTest)

### Спринт 5: Чаты, релизы, интеграция 1С (завершён)
- [x] Удаление чата для всех участников (событие `ChatDeleted`, soft-delete + detach)
- [x] Фикс DM-дедупа (переиспользование только чатов ровно с 2 участниками) + команда `chat:repair-dm`
- [x] Загрузка релизов из админки (Android/Windows): Blade-раздел `/admin/releases`, манифест `latest.json`
- [x] Интеграция с 1С: token + HMAC, IP-allowlist, rate-limit, fail-closed
- [x] Оптимизация N+1 в выгрузке задач в 1С — пакетная загрузка `external_id`
- [x] Безопасность релизов: whitelist расширений, `Content-Disposition: attachment`, проверка подписи (опционально)
- [x] Убран токен из URL в переходе на Laravel Admin Panel

---

## 5. 🎯 Рекомендуемый план действий

### Спринт 1: Стабильность
1. Исправить критические баги (transition, дубликаты)
2. Удалить тестовый код
3. Добавить 10-15 базовых тестов

### Спринт 2: Производительность
1. Исправить N+1 запросы
2. Добавить пагинацию
3. Внедрить кэширование

### Спринт 3: Архитектура
1. Выбрать WebSocket технологию
2. Рефакторить валидацию (Form Requests)
3. Улучшить структуру frontend

### Спринт 4: DevOps
1. Настроить CI/CD
2. Добавить health checks
3. Настроить мониторинг
