# Релиз и деплой через Git — по порядку действий

> **Параметры деплоя**
> - Внешний IP (публичный)
> - Внутренний IP сервера (Ubuntu + Docker)
> - Репозиторий: `https://github.com/Sereban4o/Tasksbase-Knowledgebase`
> - Каталог проекта на сервере: `/opt/tasksbase`
> - Доступ: HTTP по IP, HTTPS пока нет. WebSocket идёт через nginx (`ws://`), при включении TLS станет `wss://`.
> - Push-уведомления: **ntfy** на порту **8081** (с авторизацией).

Стек: Laravel 13 (PHP 8.5) + React 19 + PostgreSQL 18 + Redis + Reverb + Meilisearch.
Публикуются **80** (nginx: SPA + API + WebSocket) и **8081** (ntfy push). SPA собирается внутри `nginx`-образа.

---

## Шаг 1. Открыть порты

### 1.1 Публичные (firewall + NAT роутера → Внутренний IP)

| Порт | Назначение | Обязательность |
|------|-----------|----------------|
| **22/tcp** | SSH | да (по возможности ограничить по IP) |
| **80/tcp** | Сайт + API + WebSocket (`ws://`) | да |
| **8081/tcp** | ntfy — push-уведомления | да |
| **443/tcp** | HTTPS/`wss` | опционально (позже) |

### 1.2 Внутренние — НЕ открывать в интернет

`5432` (PostgreSQL), `6379` (Redis), `7700` (Meilisearch), `8080` (Reverb), `9000` (PHP-FPM).
В прод-`docker-compose.prod.yml` они не публикуются — доступны только внутри Docker-сети.

### 1.3 UFW на сервере

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 8081/tcp
# sudo ufw allow 443/tcp   # когда включите HTTPS
sudo ufw enable
sudo ufw status
```

> Если сервер за NAT-роутером: пробросьте на `Внутренний IP` **80**, **8081** и **22** (SSH).

---

## Шаг 2. Подготовить сервер (один раз)

### 2.1 Проверить Docker

```bash
docker --version
docker compose version
git --version
```

### 2.2 Создать каталог проекта

```bash
sudo mkdir -p /opt/tasksbase
sudo chown $USER:$USER /opt/tasksbase
```

### 2.3 Настроить доступ к репозиторию (deploy key, read-only)

```bash
ssh-keygen -t ed25519 -C "deploy@tasksbase" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
# Добавить ключ в GitHub → Settings → Deploy keys (read-only)
```

---

## Шаг 3. Первый деплой

### 3.1 Клонировать репозиторий

```bash
git clone git@github.com:Sereban4o/Tasksbase-Knowledgebase.git /opt/tasksbase
cd /opt/tasksbase
git checkout master        # или нужный тег: git checkout v1.0.0
```

### 3.2 Создать прод-окружение

```bash
cp backend/.env.production.example backend/.env.production
```

Сгенерировать `APP_KEY`:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Отредактировать `backend/.env.production`:
- `APP_KEY=base64:...`
- заменить `CHANGE_ME_*`: `POSTGRES_PASSWORD`, `DB_PASSWORD` (должны совпадать), `REVERB_APP_SECRET`, `MEILI_MASTER_KEY`
- `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`, `CORS_ALLOWED_ORIGINS` — уже указывают на `Внешний IP`
- `NTFY_USER` / `NTFY_PASSWORD` — логин/пароль публикации в ntfy (должны совпадать с `NTFY_PASSWORD` сервиса `ntfy` в compose)

> `backend/.env.production` в git **не хранится** (в `.gitignore`). Создаётся один раз; `git pull`/`git checkout` его не перезаписывают.

### 3.3 Собрать и запустить

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

### 3.4 Инициализировать БД

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force   # опционально
```

> `--seed` создаёт демо-данные (пользователи, доска). Демо-админ `admin@example.com` / `password` — **смените пароль**.

### 3.5 Проверить

```bash
curl -fsS http://localhost/up && echo OK
docker compose -f docker-compose.prod.yml ps
```

- Сайт: `http://Внешний IP`
- Админка: `http://Внешний IP/admin/login`

### 3.6 Push через ntfy (без Firebase)

Сервис `ntfy` в `docker-compose.prod.yml` слушает **8081** и работает с включённой авторизацией:
- `NTFY_AUTH_DEFAULT_ACCESS=deny-all` — без логина нельзя ни читать, ни публиковать;
- при старте создаётся пользователь `tasksbase` с паролем из `NTFY_PASSWORD` (роль admin);
- auth-база — SQLite в volume `ntfydata` (`/var/cache/ntfy/auth.db`);
- топик на пользователя: `tasksbase-user-{id}`.

Проверка:

```bash
# без авторизации — ожидается 403
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8081 \
  -H 'Content-Type: application/json' \
  -d '{"topic":"tasksbase-user-1","message":"test"}'

# с авторизацией — ожидается 200
curl -s -o /dev/null -w "%{http_code}\n" -u tasksbase:tasksbase -X POST http://localhost:8081 \
  -H 'Content-Type: application/json' \
  -d '{"topic":"tasksbase-user-1","message":"test"}'
```

Смена пароля ntfy:
1. задать новый `NTFY_PASSWORD` в `docker-compose.prod.yml` (сервис `ntfy`) **и** в `backend/.env.production`;
2. удалить auth-базу и пересоздать контейнер:

```bash
docker compose -f docker-compose.prod.yml exec ntfy rm -f /var/cache/ntfy/auth.db
docker compose -f docker-compose.prod.yml up -d --force-recreate ntfy app
```

3. обновить пароль ntfy в аккаунтах клиентов (Android/Windows).

> Полный сброс подписок/паролей: удалить volume `ntfydata`.

---

## Шаг 4. Разработка фичи (ветки)

### 4.1 Создать ветку от `master`

```bash
git checkout master
git pull --ff-only
git checkout -b feature/short-name
```

### 4.2 Работа и локальные проверки

```bash
(cd backend && php artisan test)
(cd frontend && npm run build)
```

### 4.3 Коммит и пуш ветки

```bash
git add -A
git commit -m "feat: describe change"
git push -u origin feature/short-name
```

### 4.4 Pull Request → merge в `master`

- Открыть PR в GitHub, дождаться ревью/CI, влить в `master`.

---

## Шаг 5. Подготовка релиза (локально/CI)

> `master` = прод. Тег = неизменяемый срез для деплоя.

### 5.1 Обновить `master`

```bash
git checkout master
git pull --ff-only
```

### 5.2 Прогнать тесты и сборку

```bash
(cd backend && php artisan test)
(cd frontend && npm run build)
```

### 5.3 Поставить тег версии (SemVer)

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
```

Типы версий: `v1.0.1` — фиксы, `v1.1.0` — фичи (обратно совместимо), `v2.0.0` — breaking changes.

### 5.4 Отправить ветку и тег

```bash
git push origin master
git push origin v1.0.0        # или все теги: git push origin --tags
```

---

## Шаг 6. Деплой релиза на сервере (по порядку)

### 6.1 Перейти в каталог и подтянуть теги

```bash
cd /opt/tasksbase
git fetch --tags --prune
```

### 6.2 Переключиться на релизный тег

```bash
git checkout v1.0.0
git describe --tags --always      # проверить развёртываемую версию
```

> `git checkout <тег>` → detached HEAD. Это нормально для прода. Возврат на ветку: `git checkout master`.

### 6.3 Пересобрать образы

```bash
docker compose -f docker-compose.prod.yml build --pull
docker compose -f docker-compose.prod.yml up -d
```

> SPA собирается внутри `nginx`-образа. Изменения фронтенда подхватываются только пересборкой. `REVERB_APP_KEY` вшивается в бандл на этапе сборки.

### 6.4 Применить миграции

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

### 6.5 Обновить кэш

```bash
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

> Если меняли `backend/.env.production`: сначала `php artisan config:clear`, затем `config:cache` и `up -d --force-recreate app worker reverb`.

### 6.6 Проверить релиз (Шаг 7 ниже)

---

## Шаг 7. Проверка после деплоя

```bash
curl -fsS http://localhost/up && echo OK
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f --tail=100
```

Чек-лист:
- [ ] `http://Внешний IP` открывает SPA
- [ ] Логин работает (`/api/v1/auth/login`)
- [ ] WebSocket подключается (`ws://Внешний IP/app/...`)
- [ ] Загрузка файлов/вложений работает
- [ ] ntfy: без логина — 403, с логином — 200; push приходит в клиенты
- [ ] `docker compose ... ps` — все сервисы `Up`

---

## Шаг 8. Откат (rollback)

### 8.1 Переключиться на предыдущий стабильный тег

```bash
cd /opt/tasksbase
git fetch --tags
git checkout v0.9.0               # предыдущий рабочий тег
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
```

### 8.2 Про миграции

Откат кода **не откатывает миграции**. Варианты:
- `php artisan migrate:rollback` — только если у миграций есть рабочий `down()`;
- восстановление БД из бэкапа (см. Шаг 11.3);
- предпочтительно — «forward-fix» новым тегом.

---

## Шаг 9. Hotfix

```bash
git checkout -b hotfix/critical-bug master
# ... правки + тесты ...
git commit -am "fix: critical bug"
git checkout master
git merge --no-ff hotfix/critical-bug
git tag -a v1.0.1 -m "Hotfix v1.0.1"
git push origin master v1.0.1
```

На сервере — повторить **Шаг 6** с тегом `v1.0.1`.

---

## Шаг 10. CI/CD (опционально, GitHub Actions)

На пуш тега `v*` — тесты и деплой по SSH. `.github/workflows/deploy.yml`:

```yaml
name: Deploy
on:
  push:
    tags: ['v*']
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_SSH_KEY }}
          script: |
            cd /opt/tasksbase
            git fetch --tags --prune
            git checkout ${{ github.ref_name }}
            docker compose -f docker-compose.prod.yml up -d --build
            docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
            docker compose -f docker-compose.prod.yml exec app php artisan config:cache
```

Секреты `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY` — в Settings → Secrets and variables → Actions.

---

## Шаг 11. Эксплуатация

### 11.1 Перезапуск сервисов

```bash
docker compose -f docker-compose.prod.yml restart app worker reverb nginx
```

### 11.2 Логи

```bash
docker compose -f docker-compose.prod.yml logs -f --tail=200 app
docker compose -f docker-compose.prod.yml logs -f --tail=200 nginx
```

### 11.3 Бэкап БД

```bash
docker compose -f docker-compose.prod.yml exec -T postgres \
  pg_dump -U postgres tasksbase_knowledgebase > backup_$(date +%F).sql
```

### 11.4 Бэкап файлов пользователей

Загрузки — named-volume `storage_data` (в контейнере `/var/www/html/storage`):

```bash
docker run --rm -v tasksbase-knowledgebase_storage_data:/data -v $PWD:/backup alpine \
  tar czf /backup/storage_$(date +%F).tgz -C /data .
```

---

## Шаг 12. Добавить HTTPS позже

1. Получить домен, A-запись → `Внешний IP` (или сертификат на IP через acme/certbot).
2. Смонтировать сертификаты в nginx, добавить `listen 443 ssl;` + `ssl_certificate`.
3. В `backend/.env.production`: `APP_URL=https://домен`, `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS=домен`, `CORS_ALLOWED_ORIGINS=https://домен`.
4. Открыть 443, пересобрать nginx (Шаг 6). WebSocket станет `wss` автоматически (клиент выбирает схему по `window.location.protocol`).

---

## Шаг 13. Безопасность — чек-лист

- [ ] Сменён пароль `admin@example.com`
- [ ] Заданы сильные `POSTGRES_PASSWORD`/`DB_PASSWORD`, `REVERB_APP_SECRET`, `MEILI_MASTER_KEY`
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Наружу открыты только 22/80/8081 (443 позже); БД, Redis, Meilisearch, Reverb **не** опубликованы
- [ ] `.env` / `.env.production` не в git (только `*.example`), доступ к репо — deploy key (read-only)
- [ ] SSH ограничен по IP (по возможности)
- [ ] Настроен регулярный бэкап БД и `storage_data`
- [ ] Сменён пароль ntfy (`NTFY_PASSWORD`) и он совпадает в compose, `backend/.env.production` и клиентах
- [ ] Порт 8081 открыт только для ntfy; auth включён (`deny-all`)
- [ ] 1С-интеграция: заданы `ONE_C_TOKEN`/`ONE_C_HMAC_SECRET`, при возможности `ONE_C_ALLOWED_IPS`
- [ ] Релизы: при включённой проверке подписи настроены `UPDATES_VERIFY_*`, каталог `./updates` доступен на запись `app`

---

## Шаг 14. Клиенты: Android и Windows

Оба приложения поддерживают **несколько аккаунтов**, каждый — со своим сервером и своим ntfy.

### 14.1 Android (APK)

Сборка через Android Studio или Gradle 8.7 + JDK 17:

```bash
gradle -p android :app:assembleDebug
```

APK: `android/app/build/outputs/apk/debug/app-debug.apk`.

### 14.2 Windows (exe + MSI)

```bash
gradle -p android :desktop:createDistributable
gradle -p android :desktop:packageMsi
```

- EXE: `android/desktop/build/compose/binaries/main/app/TasksbaseChat/TasksbaseChat.exe`
- MSI: `android/desktop/build/compose/binaries/main/msi/TasksbaseChat-1.0.0.msi`

> MSI требует WiX — Gradle скачивает его автоматически.

### 14.3 Добавление аккаунта

При входе указываются:
- **Сервер**: `IP:80` (прод) или ваш адрес;
- **ntfy**: `IP:8081`, **пользователь** и **пароль** ntfy (по умолчанию `tasksbase` / `tasksbase`);
- email/пароль пользователя этого сервера.

Каждый аккаунт хранит свой сервер, WebSocket и ntfy — переключение в меню сверху (Android — тап по заголовку, Windows — меню аккаунта).

### 14.4 Windows: трей и автозапуск

Меню аккаунта → **Настройки…**:
- **Автозапуск при входе в Windows** — запись в `HKCU\Software\Microsoft\Windows\CurrentVersion\Run` (без админа; доступно в установленной/упакованной версии);
- **Сворачивать в трей при запуске** — приложение стартует в трее;
- закрытие окна (X) сворачивает в трей; «Выход» в трее — полное завершение.

Уведомления приходят через WebSocket (Reverb) и ntfy, пока приложение запущено.

---

## Шаг 15. Сервер обновлений (самообновление приложений)

Обновления отдаёт nginx из каталога `./updates`. Каталог монтируется в `nginx` (только чтение) и в `app` (read-write, для загрузки через админку) как `/var/www/updates`. Публичный порт 80.

Структура:
```
updates/
  android/latest.json  +  <файл>.apk
  windows/latest.json  +  <файл>.msi
```

Манифест `latest.json`:
```json
{ "version": "1.0.1", "versionCode": 2, "path": "/updates/android/app-release.apk", "notes": "Что нового", "sha256": "..." }
```

Эндпоинты (через nginx):
- `GET /updates/android/latest.json`, `GET /updates/android/<file>.apk`
- `GET /updates/windows/latest.json`, `GET /updates/windows/<file>.msi`

### 15.1 Загрузка через веб-админку (рекомендуется)

В SPA-админке: **Панель администратора → Релизы приложений**. Форма позволяет выбрать платформу (Android/Windows), версию, `versionCode`, описание и файл. Сервер:
- проверяет расширение по whitelist (`apk/aab` для Android, `msi/exe/zip` для Windows);
- (опционально) проверяет подпись бинарника перед публикацией;
- сохраняет файл в `updates/<platform>/`, считает SHA-256 и атомарно перезаписывает `latest.json`.

API-слой для SPA не используется — раздел живёт в Laravel Blade-админке: `http://<server>/admin/releases` (вход администратора через сессию). Ссылка также есть в меню админки («🚀 Релизы»).

Проверка подписи (опционально, fail-closed):
```bash
# backend/.env.production
UPDATES_VERIFY_SIGNATURE=true
UPDATES_VERIFY_ANDROID="apksigner verify --print-certs %s"
UPDATES_VERIFY_WINDOWS="osslsigncode verify -in %s"
```
Инструменты должны быть установлены в контейнере `app`; `%s` заменяется на путь к файлу.

### 15.2 Публикация из CLI

```powershell
# Android
.\scripts\publish_update.ps1 -Platform android -File .\app-release.apk -Version 1.0.1 -VersionCode 2 -Notes "Fixes"

# Windows
.\scripts\publish_update.ps1 -Platform windows -File .\TasksbaseChat-1.0.1.msi -Version 1.0.1 -Notes "Fixes"
```

Скрипт копирует файл в `updates/<platform>/`, считает SHA-256 и обновляет `latest.json`. Пересборка nginx не нужна — файлы видны сразу (bind-mount).

### 15.3 Как работают приложения

- При запуске и по пункту меню **«Проверить обновления»** приложение опрашивает `/updates/<platform>/latest.json` на своём сервере.
- Если версия новее — предлагает обновление:
  - **Android**: скачивает APK и запускает системный установщик (нужно разрешение «Установка из неизвестных источников» для приложения).
  - **Windows**: скачивает MSI и запускает `msiexec /i` (приложение закрывается, затем установщик обновляет файлы).

> Где менять версию:
> - Android: `versionCode` / `versionName` в `android/app/build.gradle.kts`;
> - Windows: `APP_VERSION` в `Main.kt` и `packageVersion` в `android/desktop/build.gradle.kts`.
>
> URL обновлений берётся с того же сервера, что и активный аккаунт (`http://<server>/updates/...`), поэтому для разных серверов — свои обновления.

---

## Шаг 16. Интеграция с 1С (опционально)

Входящие запросы 1С идут на `/api/v1/integration/1c/*` (без пользовательской сессии) и защищены токеном и/или HMAC-подписью.

```bash
# backend/.env.production
ONE_C_ENABLED=true
ONE_C_BASE_URL=http://1c-host/base/hs/tasksbase   # для исходящих вызовов Laravel → 1С
ONE_C_TOKEN=<bearer-токен>
ONE_C_HMAC_SECRET=<shared-secret>
ONE_C_ALLOWED_IPS=10.0.0.5,10.0.0.6               # опционально, через запятую
ONE_C_TIMESTAMP_TOLERANCE=300
```

- Подпись 1С: `X-Signature = hex(hmac_sha256("{X-Timestamp}.{raw_body}", ONE_C_HMAC_SECRET))` + заголовок `X-Timestamp`.
- Если интеграция включена, но токен и секрет пусты — эндпоинты отвечают `503` (fail-closed).
- Rate limit: 120 запросов/мин; опциональный IP-allowlist.

Планировщик подтягивает изменения задач из 1С ежечасно (`routes/console.php`), либо вручную:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan one-c:pull-issues --since="2026-01-01T00:00:00Z"
```

Не забудьте запускать Laravel scheduler в контейнере `worker` (`php artisan schedule:work`) или через cron.

---

## Шаг 17. Обслуживание чатов

Команда для починки исторически «битых» личных чатов (dm не с ровно двумя участниками):

```bash
docker compose -f docker-compose.prod.yml exec app php artisan chat:repair-dm --strategy=group --dry-run
# затем без --dry-run для применения; --strategy=prune оставит по 2 участника
```

---

## Быстрая шпаргалка

**Релиз (локально):**```bash
git checkout master && git pull --ff-only
(cd backend && php artisan test) && (cd frontend && npm run build)
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push origin master vX.Y.Z
```

**Деплой (сервер):**
```bash
cd /opt/tasksbase && git fetch --tags --prune && git checkout vX.Y.Z
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
curl -fsS http://localhost/up && echo OK
```
