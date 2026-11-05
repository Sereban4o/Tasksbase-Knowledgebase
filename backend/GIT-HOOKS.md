# Git Hooks для Backend

## Установка

```bash
# Скопировать hooks в .git/hooks
cp .git-hooks/* .git/hooks/
chmod +x .git/hooks/*
```

## Или через global hooks

```bash
# Добавить в ~/.gitconfig
[core]
    hooksPath = \"/path/to/tasksbase-knowledgebase/backend/.git-hooks\"
```

## Доступные хуки

### pre-commit
- Запускает Laravel Pint для проверки стиля кода
- Если есть ошибки — коммит не состоится!

### commit-msg
- Проверяет формат commit message (Conventional Commits)
- Формат: `type(scope): subject`

## Типы commit messages

- `feat` — новая функция
- `fix` — исправление бага
- `docs` — документация
- `style` — форматирование
- `refactor` — рефакторинг
- `test` — тесты
- `chore` — настройка

## Примеры

```bash
feat(tasksbase): add kanban board
fix(backend): resolve issue key collision
docs: add architecture documentation
```
