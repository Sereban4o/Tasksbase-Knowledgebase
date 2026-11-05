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

### Обновление кода

```bash
git pull
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
```

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
- [ ] Настроен регулярный бэкап БД и `storage_data`
