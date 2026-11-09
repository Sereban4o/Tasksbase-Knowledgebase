# ER-диаграмма базы данных

## 1. Диаграмма (Mermaid)

```mermaid
erDiagram
    users ||--o{ issues : "creates"
    users ||--o{ issues : "assigned to"
    users ||--o{ pages : "creates"
    users ||--o{ spaces : "creates"
    users ||--o{ issue_comments : "writes"
    users ||--o{ page_comments : "writes"
    
    roles ||--o{ users : "assigned"
    roles ||--o{ permissions : "has"
    
    projects ||--o{ issues : "contains"
    projects ||--o{ boards : "has"
    projects ||--o{ issue_custom_fields : "defines"
    projects ||--o{ issue_transitions : "defines"
    
    issue_types ||--o{ issues : "type"
    issue_statuses ||--o{ issues : "status"
    issue_statuses ||--o{ issue_transitions : "transition"
    
    boards ||--o{ sprints : "contains"
    sprints ||--o{ issues : "includes"
    
    spaces ||--o{ pages : "contains"
    spaces ||--o{ space_members : "has"
    pages ||--o{ pages : "parent-child"
    pages ||--o{ page_versions : "has"
    pages ||--o{ page_comments : "has"
    pages ||--o{ issue_knowledgebase_links : "links"
    
    issues ||--o{ issues : "parent-child"
    issues ||--o{ issues : "epic-link"
    issues ||--o{ issue_comments : "has"
    issues ||--o{ issue_attachments : "has"
    issues ||--o{ issue_links : "links"
    
    pages ||--o{ page_attachments : "has"
    
    users {
        bigint id PK
        string email UK
        string password
        string first_name
        string last_name
        string avatar
        enum status
        bigint role_id FK
        timestamp email_verified_at
    }
    
    roles {
        bigint id PK
        string name UK
        string description
    }
    
    permissions {
        bigint id PK
        string name UK
        string group
    }
    
    role_has_permissions {
        bigint role_id FK
        bigint permission_id FK
    }
    
    projects {
        bigint id PK
        string key UK
        string name
        string description
        bigint created_by FK
        enum type
        enum visibility
    }
    
    issue_types {
        bigint id PK
        string name
        string icon
        string color
        boolean is_epic
        boolean is_subtask
    }
    
    issue_statuses {
        bigint id PK
        string name
        string category
        string color
    }
    
    issues {
        bigint id PK
        bigint project_id FK
        bigint issue_type_id FK
        bigint status_id FK
        bigint created_by FK
        bigint assigned_to FK
        bigint reporter_id FK
        bigint parent_id FK
        bigint epic_id FK
        string key UK
        string summary
        text description
        enum priority
        date due_date
        integer story_points
        decimal time_spent_hours
    }
    
    boards {
        bigint id PK
        bigint project_id FK
        string name
        enum type
        json configuration
    }
    
    sprints {
        bigint id PK
        bigint board_id FK
        string name
        date start_date
        date end_date
        date activated_date
        enum status
    }
    
    spaces {
        bigint id PK
        bigint created_by FK
        string key UK
        string name
        text description
        enum type
        enum visibility
    }
    
    pages {
        bigint id PK
        bigint space_id FK
        bigint parent_id FK
        bigint author_id FK
        bigint last_edited_by FK
        string title
        text body
        enum status
        integer version
        json labels
    }
    
    page_versions {
        bigint id PK
        bigint page_id FK
        integer version
        bigint author_id FK
        text body_snapshot
    }
    
    issue_comments {
        bigint id PK
        bigint issue_id FK
        bigint user_id FK
        text body
        bigint parent_comment_id FK
    }
    
    page_comments {
        bigint id PK
        bigint page_id FK
        bigint user_id FK
        text body
        integer line_number
        boolean resolved
    }
    
    space_members {
        bigint id PK
        bigint space_id FK
        bigint user_id FK
        enum role
    }
    
    issue_links {
        bigint id PK
        bigint source_issue_id FK
        bigint target_issue_id FK
        string link_type
    }
    
    issue_attachments {
        bigint id PK
        bigint issue_id FK
        bigint file_id FK
        bigint uploaded_by FK
    }
    
    page_attachments {
        bigint id PK
        bigint page_id FK
        bigint file_id FK
        bigint uploaded_by FK
    }
    
    issue_knowledgebase_links {
        bigint id PK
        bigint issue_id FK
        bigint page_id FK
        string context
    }

    integration_mappings {
        bigint id PK
        string integration
        string entity_type
        bigint entity_id
        string external_id
        json meta
    }
```

## 2. Описание таблиц

### Основные сущности

#### `users`
Пользователи системы. Поле `role_id` определяет роль (1 = admin).

#### `roles`
Роли: admin, user, и кастомные.

#### `projects`
Проекты tasksbase. Содержат задачи, доски, спринты.

#### `issues`
Задачи проекта. Поддерживают:
- Иерархию (parent-child)
- Epic linking
- Комментарии
- Вложения
- Связи (linked issues)
- Work tracking (time tracking)

#### `boards`
Канбан/Scrum доски.

#### `sprints`
Спринты для Scrum.

#### `spaces`
Пространства Knowledgebase.

#### `pages`
Страницы Knowledgebase с версионированием.

#### `integration_mappings`
Маппинг локальных сущностей на внешние идентификаторы (интеграция с 1С):
- `integration` — идентификатор интеграции (`one_c`);
- `entity_type` — `user`, `issue`, `project`, `page` и т.п.;
- `entity_id` — локальный id; `external_id` — идентификатор в 1С;
- `meta` — дополнительная служебная информация (JSON);
- unique `(integration, entity_type, external_id)`, индекс `(integration, entity_type, entity_id)`.

### Связи и ограничения

- **Issues:** `assigned_to`, `created_by`, `parent_id`, `epic_id` — nullable FK
- **Pages:** `parent_id` — self-referencing для иерархии
- **Comments:** `parent_comment_id` — вложенные комментарии
- **Issue Links:** unique constraint на (source, target, type)
- **Space Members:** unique constraint на (space_id, user_id)

### Индексы

```sql
-- Issues
(project_id, status_id)
(project_id, issue_type_id)
(assigned_to)
(epic_id)
(parent_id)

-- Pages
(space_id, status)
(parent_id)

-- Integration mappings
(integration, entity_type, entity_id)
UNIQUE (integration, entity_type, external_id)
```
