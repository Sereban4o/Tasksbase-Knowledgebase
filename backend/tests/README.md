# Тесты проекта Tasksbase-Knowledgebase

## Запуск тестов

```bash
cd backend

# Запустить все тесты
php artisan test

# Запустить конкретный тест
php artisan test tests/Feature/IssueTest.php
php artisan test tests/Feature/AuthTest.php

# Запустить с покрытием
php artisan test --coverage-html=coverage
```

## Структура тестов

```
tests/
├── TestCase.php              # Базовый класс
├── Feature/
│   ├── AuthTest.php          # Тесты аутентификации
│   └── IssueTest.php         # Тесты задач
└── Unit/                     # Модульные тесты (в будущем)
```

## Покрытие

- **AuthTest**: register, login, logout, profile
- **IssueTest**: CRUD, пагинация, фильтрация, комментарии, перемещение

## Добавление новых тестов

```bash
# Создать тест
php artisan make:test IssueTest

# Создать Unit тест
php artisan make:test --unit IssueTest
```

## Пример

```php
public function test_can_create_issue(): void
{
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/issues', [
            'project_key' => $this->project->key,
            'summary' => 'Test Issue',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('summary', 'Test Issue');
}
```
