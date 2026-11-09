# WebSocket и Real-time события

## 1. Обзор

Проект использует Laravel Reverb (Pusher-протокол, `laravel-echo` + `pusher-js` на клиенте) для real-time событий.

## 2. Архитектура

```
┌─────────────┐     WebSocket      ┌──────────────┐
│   Browser   │ ◄────────────────► │  Laravel     │
│  (Echo.js)  │                    │  Application │
└─────────────┘                    └──────────────┘
                                         │
                                         │ Redis Pub/Sub
                                         ▼
                                   ┌──────────────┐
                                   │    Redis     │
                                   │  Channel:    │
                                   │  issue.*     │
                                   └──────────────┘
```

## 3. Каналы и события

### 3.1 Issue Events

**Канал:** `issues.{projectId}`

| Событие | Описание | Payload |
|---------|----------|---------|
| `IssueCreated` | Новая задача | `{ issue: Issue, createdBy: User }` |
| `IssueUpdated` | Задача обновлена | `{ issue: Issue, changes: object }` |
| `IssueDeleted` | Задача удалена | `{ issueId: number }` |
| `IssueMoved` | Задача перемещена (kanban) | `{ issue: Issue, oldStatus: string, newStatus: string }` |
| `IssueCommentAdded` | Добавлен комментарий | `{ comment: Comment, issueId: number }` |

### 3.2 Board Events

**Канал:** `boards.{boardId}`

| Событие | Описание | Payload |
|---------|----------|---------|
| `BoardUpdated` | Доска обновлена | `{ board: Board }` |
| `ColumnReordered` | Колонки переставлены | `{ columns: Column[] }` |

### 3.3 Sprint Events

**Канал:** `sprints.{sprintId}`

| Событие | Описание | Payload |
|---------|----------|---------|
| `SprintStarted` | Спринт начат | `{ sprint: Sprint }` |
| `SprintFinished` | Спринт завершен | `{ sprint: Sprint }` |

### 3.4 Knowledgebase Events

**Канал:** `spaces.{spaceId}`

| Событие | Описание | Payload |
|---------|----------|---------|
| `PageCreated` | Страница создана | `{ page: Page, author: User }` |
| `PageUpdated` | Страница обновлена | `{ page: Page, version: number }` |
| `PageDeleted` | Страница удалена | `{ pageId: number }` |

### 3.5 Chat Events

**Каналы:** `chat.{chatId}` (участники чата) и `user.{userId}` (персональные уведомления).

| Событие | Канал | Описание | Payload |
|---------|-------|----------|---------|
| `MessageCreated` | `chat.*` + `user.*` | Новое сообщение | `{ id, chat_id, sender_id, body, type, sender, files, chat_name, body_preview }` |
| `MessageUpdated` | `chat.*` | Сообщение изменено | сообщение |
| `MessageDeleted` | `chat.*` | Сообщение удалено | `{ id, chat_id }` |
| `ChatDeleted` | `chat.*` + `user.*` | Чат удалён для всех участников | `{ chat_id, chat_type, chat_name }` |

> На клиентах `ChatDeleted` инвалидирует список чатов и закрывает открытый чат. Событие рассылается на канал чата и на персональные каналы каждого участника (чтобы список обновился, даже если чат не открыт).

## 4. Frontend Integration

### 4.1 Инициализация Echo

```typescript
// src/api/echo.ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_KEY,
  cluster: import.meta.env.VITE_PUSHER_CLUSTER,
  forceTLS: true,
  auth: {
    headers: {
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  },
});
```

### 4.2 Подписка на канал

```typescript
// В компоненте
useEffect(() => {
  const channel = echo.channel(`issues.${projectId}`);
  
  channel.listen('IssueCreated', (data) => {
    // Обновить список задач
    issuesQuery.invalidateQueries();
  });
  
  channel.listen('IssueMoved', (data) => {
    // Обновить канбан доску
    setIssues(prev => prev.map(issue => 
      issue.id === data.issue.id ? data.issue : issue
    ));
  });
  
  return () => {
    channel.stopListening('IssueCreated');
    channel.stopListening('IssueMoved');
  };
}, [projectId]);
```

### 4.3 Отправка событий

```php
// В контроллере
public function store(Request $request)
{
    $issue = Issue::create($request->validated());
    
    // Dispatch event
    event(new IssueCreated($issue, $request->user()));
    
    return response()->json($issue, 201);
}
```

## 5. Event Classes

```php
// app/Events/IssueCreated.php
class IssueCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public Issue $issue,
        public User $creator
    ) {}
    
    public function broadcastOn(): Channel
    {
        return new Channel("issues.{$this->issue->project_id}");
    }
    
    public function broadcastWith(): array
    {
        return [
            'issue' => IssueResource::make($this->issue),
            'createdBy' => UserResource::make($this->creator),
        ];
    }
}
```

## 6. Fallback Mode

Если Redis/Broadcast не настроен:

- **Polling:** каждые 5 секунд `GET /api/v1/issues`
- **Manual refresh:** кнопка "Обновить"
- **Optimistic UI:** изменения показываются сразу, откат при ошибке

## 7. Настройка

### Backend

```bash
# .env
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

```bash
# Запустить worker
php artisan queue:work --queue=redis,default
```

### Frontend

```bash
# .env
VITE_PUSHER_KEY=your-pusher-key
VITE_PUSHER_CLUSTER=your-cluster
```

```bash
npm install laravel-echo pusher-js
```
