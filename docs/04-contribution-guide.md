# Руководство по вкладу в проект

## 1. Начало работы

### 1.1 Установка

```bash
# Клонировать репозиторий
git clone https://github.com/youruser/tasksbase-knowledgebase.git
cd tasksbase-knowledgebase

# Запустить через Docker
docker compose up -d

# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Frontend
cd ../frontend
npm install
```

### 1.2 Запуск разработки

```bash
# Terminal 1: Backend + Docker services
docker compose up -d

# Terminal 2: Frontend dev server
cd frontend
npm run dev

# Terminal 3: Laravel queue worker (если нужно)
cd backend
php artisan queue:work
```

## 2. Стиль кода

### 2.1 Backend (PHP)

- **PSR-12** — основной стиль
- **Laravel Pint** — форматтер
- **PHP 8.3+** — типизация, readonly классы

```bash
# Проверить стиль
./vendor/bin/pint --test

# Исправить стиль
./vendor/bin/pint
```

**Примеры:**

```php
// ✅ Правильно
public function store(Request $request): CreatedResponse
{
    $validated = $request->validate([
        'summary' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    $issue = Issue::create($validated);

    return response()->json($issue, 201);
}

// ❌ Неправильно
public function store(Request $request)
{
    $data = $request->validate([...]);
    $issue = new Issue();
    $issue->summary = $data['summary'];
    $issue->save();
    return $issue;
}
```

### 2.2 Frontend (TypeScript)

- **ESLint** + **Prettier**
- **React 18** — functional components + hooks
- **TypeScript strict mode**

```bash
# Проверить
npm run lint

# Исправить
npm run lint:fix
```

**Примеры:**

```tsx
// ✅ Правильно
interface IssueCardProps {
  issue: Issue;
  onDragStart: (issueId: number) => void;
}

const IssueCard: React.FC<IssueCardProps> = ({ issue, onDragStart }) => {
  const { mutate: updateIssue } = useUpdateIssue();

  const handleMove = useCallback(
    (status: IssueStatus) => {
      updateIssue({ id: issue.id, status });
    },
    [issue.id, updateIssue]
  );

  return (
    <Draggable issueId={issue.id} onDragStart={() => onDragStart(issue.id)}>
      <Box sx={{ mb: 2 }}>
        <Typography>{issue.summary}</Typography>
      </Box>
    </Draggable>
  );
};

export default IssueCard;

// ❌ Неправильно
function IssueCard(props) {
  const issue = props.issue;
  const updateIssue = useUpdateIssue();
  
  return (
    <div>
      <p>{issue.summary}</p>
    </div>
  );
}
```

## 3. Git Workflow

### 3.1 Branching Strategy

```
main (production)
  ├── feature/add-kanban-drag-drop
  ├── feature/knowledgebase-pages
  ├── bugfix/fix-issue-key-generation
  └── refactor/simplify-auth
```

**Правила:**
- `main` — всегда production-ready
- `feature/*` — новые функции
- `bugfix/*` — исправления багов
- `refactor/*` — рефакторинг

### 3.2 Commit Messages

**Формат:** `<type>(<scope>): <subject>`

```
feat(tasksbase): add drag-and-drop to kanban board
fix(backend): resolve issue key collision
docs: add architecture documentation
refactor(auth): simplify sanctum token handling
chore(deps): update react-dnd to 16.0.1
```

**Types:**
- `feat` — новая функция
- `fix` — исправление бага
- `docs` — изменения в документации
- `style` — форматирование (без изменений логики)
- `refactor` — рефакторинг
- `test` — добавление/изменение тестов
- `chore` — настройка, зависимости

### 3.3 Pre-commit Hooks

```bash
# Перед коммитом автоматически запускается:
# 1. Prettier (форматирование)
# 2. ESLint (lint frontend)
# 3. Laravel Pint (lint backend)

# Если есть ошибки — коммит не состоится!
```

## 4. Добавление новой функции

### 4.1 Checklist

1. **Создать branch**
   ```bash
   git checkout -b feature/my-feature
   ```

2. **Backend**
   - [ ] Создать миграцию (если нужна БД)
   - [ ] Создать Model
   - [ ] Создать Resource (API resources)
   - [ ] Создать Controller method
   - [ ] Добавить route
   - [ ] Написать Form Request (валидация)
   - [ ] Добавить тесты

3. **Frontend**
   - [ ] Создать TypeScript interface
   - [ ] Создать API client function
   - [ ] Создать компонент/страницу
   - [ ] Добавить routing (если нужно)
   - [ ] Написать стили (MUI/theme)
   - [ ] Добавить тесты (если нужно)

4. **Документация**
   - [ ] Обновить API docs
   - [ ] Добавить примеры использования
   - [ ] Обновить README (если нужно)

5. **Тесты**
   ```bash
   # Backend
   cd backend && php artisan test
   
   # Frontend
   cd frontend && npm run test
   ```

6. **Commit & Push**
   ```bash
   git add .
   git commit -m "feat(tasksbase): add my new feature"
   git push origin feature/my-feature
   ```

### 4.2 Пример: Добавление нового поля к Issue

```bash
# 1. Миграция
php artisan make:migration add_estimate_to_issues_table

# 2. Model update
# app/Models/Issue.php - добавить в $fillable

# 3. Resource update
# app/Http/Resources/IssueResource.php

# 4. Form Request
# app/Http/Requests/StoreIssueRequest.php

# 5. Frontend
# frontend/src/modules/tasksbase/issues/types.ts
# frontend/src/modules/tasksbase/issues/components/IssueForm.tsx
```

## 5. Тестирование

### 5.1 Backend Tests

```bash
cd backend

# Запустить все тесты
php artisan test

# Запустить конкретный тест
php artisan test tests/Feature/IssueTest.php

# С покрытием
php artisan test --coverage-html=coverage
```

**Пример теста:**

```php
// tests/Feature/IssueTest.php
public function test_user_can_create_issue(): void
{
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/issues', [
            'project_id' => $project->id,
            'summary' => 'Test Issue',
            'description' => 'Test Description',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('summary', 'Test Issue');
}
```

### 5.2 Frontend Tests

```bash
cd frontend

# Запустить тесты
npm run test

# В режиме watch
npm run test -- --watch
```

## 6. Code Review Checklist

### Backend
- [ ] Валидация через Form Request
- [ ] Типизация return types
- [ ] Использование Eloquent (не raw SQL)
- [ ] Нет N+1 запросов (with())
- [ ] Транзакции при множественных записях
- [ ] Events для async действий
- [ ] Error handling

### Frontend
- [ ] TypeScript strict types
- [ ] Нет `any`
- [ ] React hooks правильно использованы
- [ ] Нет inline styles (использовать MUI sx)
- [ ] Accessibility (aria labels)
- [ ] Loading states
- [ ] Error states

## 7. Деплой

```bash
# Production build
cd frontend && npm run build

# Deploy backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Restart services
sudo systemctl restart php8.3-fpm nginx
```

## 8. Частые проблемы

### Проблема: Миграции не применяются
```bash
# Решение
php artisan migrate:fresh --seed
```

### Проблема: CORS errors
```bash
# Проверить .env
APP_URL=http://localhost:8000
# Vite proxy настроен в vite.config.ts
```

### Проблема: Token expired
```typescript
// Проверить localStorage
localStorage.getItem('auth_token')
// Обновить токен
POST /api/v1/auth/refresh
```

## 9. Контакты

- **Issues:** GitHub Issues
- **Discussions:** GitHub Discussions
- **Security:** security@example.com
