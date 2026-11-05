# Деплой в прод (Ubuntu + Docker)

**Внешний IP:** `IP`
**Внутренний IP сервера (Ubuntu + Docker):** `IP`
**Доступ:** по IP, без HTTPS (HTTP). WebSocket отдаётся через nginx (при добавлении TLS тот же конфиг заработает как `wss` на 443).

---

## Архитектура

```
Интернет ──► IP:80 ──► nginx (контейнер)
                                   ├── /              → SPA (статика из образа)
                                   ├── /assets/*      → SPA (статика)
                                   ├── /app/*         → Reverb (WebSocket, ws://)
                                   ├── /api/*         → Laravel (PHP-FPM app:9000)
                                   ├── /sanctum/*     → Laravel
                                   ├── /broadcasting/ → Laravel
                                   ├── /storage/*     → Laravel
                                   ├── /admin         → Laravel (Blade-админка)
                                   └── /up, /docs     → Laravel

Внутренняя Docker-сеть (наружу НЕ публикуется):
  app (php-fpm :9000)   worker (queue)   reverb (:8080)
  postgres (:5432)      redis (:6379)    meilisearch (:7700)
```

Публикуется **только порт 80** (nginx). Всё остальное доступно лишь внутри Docker-сети.

---

## Какие порты открыть

### Публичные (firewall + NAT роутера → 192.168.0.5)

| Порт | Протокол | Назначение | Обязательность |
|------|----------|-----------|----------------|
| **22**  | TCP | SSH (администрирование) | обязательно (лучше ограничить по IP) |
| **80**  | TCP | Сайт + API + WebSocket (`ws://`) | обязательно |
| **443** | TCP | HTTPS/`wss` (на будущее) | опционально |

### Внутренние (НЕ открывать в интернет!)

Эти порты используются только внутри Docker-сети и в прод-`docker-compose.prod.yml` **не публикуются**:

| Порт | Сервис |
|------|--------|
| 5432 | PostgreSQL |
| 6379 | Redis |
| 7700 | Meilisearch |
| 8080 | Reverb (WebSocket, доступен только через nginx) |
| 9000 | PHP-FPM |

> Если сервер за NAT-роутером (внешний `IP`), пробросьте на `IP` только **80** (и **22** для SSH). Остальное наружу не пробрасывать.

### UFW (пример)

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
# sudo ufw allow 443/tcp   # когда включите HTTPS
sudo ufw enable
sudo ufw status
```

> Docker управляет iptables напрямую. Так как наружу публикуется только `80`, этого достаточно. Порты из других сервисов не публикуются в прод-конфиге.

---

## Подготовка и запуск

Выполнять на сервере `IP` в каталоге проекта.

### 1. Конфигурация окружения

```bash
cp backend/.env.production.example backend/.env.production
```

Отредактируйте `backend/.env.production`:

- Сгенерируйте `APP_KEY`:
  ```bash
  php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
  ```
  и вставьте: `APP_KEY=base64:...`
- Замените `CHANGE_ME_*`: `POSTGRES_PASSWORD`, `DB_PASSWORD` (должны совпадать), `REVERB_APP_SECRET`, `MEILI_MASTER_KEY`.
- `REVERB_APP_KEY` оставьте `chat-key` (или задайте свой **и** пересоберите nginx с тем же ключом: `REVERB_APP_KEY=ваш_ключ docker compose -f docker-compose.prod.yml build nginx`).
- `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`, `CORS_ALLOWED_ORIGINS` уже указывают на `IP`.

### 2. Сборка и запуск

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

### 3. Инициализация БД

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

> `--seed` создаёт демо-данные (пользователи, доска «Доска задач»). Демо-админ: `admin@example.com` / `password` — **обязательно смените пароль**. Если демо-данные не нужны, выполните seed только нужных классов: `db:seed --class=UserSeeder`, `--class=IssueStatusSeeder` и т.д.

### 4. Кэш конфигурации (опционально, ускоряет прод)

```bash
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

> После изменения `backend/.env.production` пересоздайте app и сбросьте кэш:
> ```bash
> docker compose -f docker-compose.prod.yml exec app php artisan config:clear
> docker compose -f docker-compose.prod.yml up -d --force-recreate app worker reverb
> ```

### 5. Проверка

- Сайт: `http://IP`
- API health: `http://IP/up`
- Логин: `admin@example.com` / `password` (смените!)
- Админка (Blade): `http://IP/admin/login`

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f --tail=100
```

---

## Эксплуатация

### Краткое обновление кода

```bash
git fetch --tags --prune
git checkout master && git pull --ff-only      # или конкретный тег: git checkout vX.Y.Z
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
```

> Полный процесс релиза (ветки, теги, откат, CI) — в разделе **«Релиз через Git»** ниже.

### Перезапуск

```bash
docker compose -f docker-compose.prod.yml restart app worker reverb nginx
```

### Бэкап БД

```bash
docker compose -f docker-compose.prod.yml exec -T postgres \
  pg_dump -U postgres tasksbase_knowledgebase > backup_$(date +%F).sql
```

### Файлы пользователей

Загрузки хранятся в named-volume `storage_data` (в контейнере `/var/www/html/storage`). Бэкап тома:
```bash
docker run --rm -v tasksbase-knowledgebase_storage_data:/data -v $PWD:/backup alpine \
  tar czf /backup/storage_$(date +%F).tgz -C /data .
```

---

## Релиз через Git

### Ветки и версии

- `master` — стабильная ветка, **совпадает с продом**.
- `feature/*`, `fix/*`, `hotfix/*` — рабочие ветки, вливаются в `master` через Pull Request.
- Релиз помечается тегом `vMAJOR.MINOR.PATCH` (например `v1.0.0`, `v1.0.1`, `v1.1.0`).
- Тег — неизменяемый срез кода для прода. Откат = `git checkout` предыдущего тега.

### Первый деплой сервера (клонирование)

```bash
sudo mkdir -p /opt/tasksbase && sudo chown $USER /opt/tasksbase
git clone https://github.com/Sereban4o/Tasksbase-Knowledgebase.git /opt/tasksbase
cd /opt/tasksbase
git checkout master           # или нужный тег: git checkout v1.0.0

cp backend/.env.production.example backend/.env.production
# заполнить секреты (см. «Подготовка и запуск»)

docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force   # опционально
```

> `backend/.env.production` в git **не хранится** (в `.gitignore`). Создаётся один раз на сервере; `git pull` / `git checkout` его **не перезаписывают**.

### Выпуск релиза (локально / CI)

```bash
git checkout master
git pull --ff-only
git merge --no-ff feature/xyz

# прогон тестов и сборки перед тегом
(cd backend && php artisan test)
(cd frontend && npm run build)

git tag -a v1.0.0 -m "Release v1.0.0"
git push origin master
git push origin v1.0.0        # отправить тег (git push origin --tags — все теги)
```

### Деплой тега на сервере

```bash
cd /opt/tasksbase
git fetch --tags --prune
git checkout v1.0.0

# пересборка образов (SPA собирается внутри nginx-образа)
docker compose -f docker-compose.prod.yml build --pull
docker compose -f docker-compose.prod.yml up -d

# миграции + кэш
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache

# проверка
curl -fsS http://localhost/up && echo OK
docker compose -f docker-compose.prod.yml ps
```

`git checkout <тег>` переводит репозиторий в detached HEAD — для прода это нормально. Вернуться на ветку: `git checkout master`.

Узнать развёрнутую версию:

```bash
git describe --tags --always
```

### Откат (rollback)

```bash
cd /opt/tasksbase
git fetch --tags
git checkout v1.0.0           # предыдущий стабильный тег
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
```

> Откат кода **не откатывает миграции**. Если релиз содержал миграции — либо `php artisan migrate:rollback` (только если `down()` реализован), либо восстановление БД из бэкапа (см. «Бэкап БД»). Предпочтительнее «forward-fix» новым тегом.

### Hotfix

```bash
git checkout -b hotfix/critical-bug master
# ... правки + тесты ...
git commit -am "Fix critical bug"
git checkout master && git merge --no-ff hotfix/critical-bug
git tag -a v1.0.1 -m "Hotfix v1.0.1"
git push origin master v1.0.1
```

Затем на сервере — «Деплой тега» с `v1.0.1`.

### Доступ к репозиторию на сервере

- Используйте **deploy key** (SSH, read-only) или токен только на чтение.
- Приватный репо по SSH:

```bash
ssh-keygen -t ed25519 -C "deploy@tasksbase" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub          # добавить в GitHub → Settings → Deploy keys (read-only)
git remote set-url origin git@github.com:Sereban4o/Tasksbase-Knowledgebase.git
```

### CI/CD (опционально, GitHub Actions)

На пуш тега `v*` — прогон тестов и деплой по SSH. Пример `.github/workflows/deploy.yml`:

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

Секреты `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY` задайте в настройках репозитория (Settings → Secrets and variables → Actions).

> SPA собирается внутри `nginx`-образа, поэтому после `git checkout` обязательно пересобирайте `nginx` (`up -d --build`). Ключ `REVERB_APP_KEY` попадает в клиентский бандл на этапе сборки.

---

## Добавление HTTPS позже

1. Получите домен, направьте A-запись на `IP` (или выпустите сертификат на IP через certbot/acme).
2. Смонтируйте сертификаты в nginx и добавьте `listen 443 ssl;` + блок `ssl_certificate`.
3. В `backend/.env.production`: `APP_URL=https://домен`, `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS=домен`, `CORS_ALLOWED_ORIGINS=https://домен`.
4. Откройте 443 и пересоберите nginx. WebSocket автоматически станет `wss` (клиент выбирает схему по `window.location.protocol`).

---

## Безопасность — чек-лист

- [ ] Сменён пароль `admin@example.com`
- [ ] Заданы сильные `POSTGRES_PASSWORD`/`DB_PASSWORD`, `REVERB_APP_SECRET`, `MEILI_MASTER_KEY`
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Наружу открыты только 22/80 (443 позже); БД, Redis, Meilisearch, Reverb **не** опубликованы
- [ ] SSH ограничен по IP (по возможности)
- [ ] `.env` / `.env.production` не в git (только `*.example`), доступ к репо — deploy key (read-only)
- [ ] Настроен регулярный бэкап БД и `storage_data`
