# Чат — Android (Kotlin + Jetpack Compose)

Нативное Android-приложение чата, работающее с текущим бэкендом проекта
(Laravel + Sanctum + Reverb). Сообщения, чаты, каналы и группы **синхронизированы** с веб-версией,
т.к. используют одну и ту же базу и API.

## Возможности (MVP)

- Вход по email/паролю (Sanctum personal access token, хранится в DataStore).
- Список чатов (личные / группы / каналы) с превью и временем.
- Экран переписки: отправка и получение сообщений.
- **Real-time** через Reverb (WebSocket, приватный канал `chat.{id}`) + polling-фолбэк.
- Создание личного чата, группы и **канала** (название, описание, участники).
- Отметка «прочитано» при открытии чата.

## Требования

- **Android Studio** (Ladybug/2024.2+ или новее) со встроенным JDK 17.
- Доступный сервер проекта. Адрес и порт задаются прямо в приложении (см. «Подключение к сайту»).
- Для сборки APK нужен Android SDK (Android Studio ставит его автоматически).

## Быстрый старт

1. Откройте в Android Studio папку **`android/`** (File → Open).
2. Дождитесь Gradle Sync (Studio скачает Gradle 8.7 и зависимости).
3. Запустите эмулятор и нажмите Run ▶.
4. Войдите тестовым аккаунтом:
   - `admin@example.com` / `password`

## Подключение к сайту (адрес и порт)

Сервер задаётся **в самом приложении** и сохраняется на телефоне (DataStore) — пересобирать APK не нужно.

1. На экране входа нажмите на строку **«Сервер: …»**.
2. Укажите адрес и порт сайта:
   - **Прод (внешний доступ):** адрес `Внешний IP`, порт `80`
   - **Внутренняя сеть:** адрес `Внутренний IP`, порт `80`
   - **Эмулятор + локальный dev-бэкенд:** адрес `10.0.2.2`, порт `8000`
3. Нажмите **«Сохранить»**, затем войдите по email и паролю.

Примечания:
- Один адрес используется и для REST API (`/api/v1`), и для WebSocket (`/app/...`), и для `/broadcasting/auth` — в проде всё проксируется nginx на одном порту (80).
- Адрес указывается **без** `http://` и без пути (приложение само добавляет схему и пути).
- При смене сервера текущая сессия сбрасывается (токен принадлежит прежнему серверу) — нужно войти заново.
- Настройка сохраняется на устройстве и переживает перезапуск приложения.

> Значения по умолчанию (пока ничего не сохранено) берутся из `API_BASE_URL` в `app/build.gradle.kts` (`10.0.2.2:8000`).

## Используемые API endpoints

| Назначение | Метод и путь |
|---|---|
| Логин | `POST /api/v1/auth/login` |
| Мои чаты | `GET /api/v1/chat/my?per_page=50` |
| Сообщения | `GET /api/v1/chat/{id}/messages?per_page=50` |
| Отправить сообщение | `POST /api/v1/chat/{id}/messages` |
| Отметить прочитанным | `POST /api/v1/chat/{id}/read` |
| Создать чат/группу/канал | `POST /api/v1/chat` |
| Список пользователей | `GET /api/v1/users/all` |
| Авторизация WS-канала | `POST /broadcasting/auth` |

Real-time: WebSocket `ws://HOST:PORT/app/chat-key` (протокол Pusher), где HOST:PORT — адрес из
настроек приложения; приватный канал `private-chat.{chatId}`, события `MessageCreated` / `MessageUpdated` / `MessageDeleted`.

## Структура проекта

```
android/
  build.gradle.kts, settings.gradle.kts, gradle.properties
  app/build.gradle.kts                 # версии, зависимости, API_BASE_URL по умолчанию
  app/src/main/AndroidManifest.xml
  app/src/main/java/com/tasksbase/chat/
    ChatApplication.kt                 # загрузка сессии при старте
    MainActivity.kt
    di/ServiceLocator.kt, Session.kt    # ручной DI, текущая сессия
    data/remote/Dtos.kt, ApiService.kt, AuthInterceptor.kt, ServerAddressInterceptor.kt
    data/local/TokenStore.kt           # DataStore: токен + пользователь
    data/local/ServerConfigStore.kt    # DataStore: адрес/порт сервера
    data/repository/AuthRepository.kt, ChatRepository.kt
    realtime/RealtimeClient.kt         # Pusher-протокол (Reverb) поверх OkHttp
    ui/AppRoot.kt
    ui/auth/LoginScreen.kt
    ui/chat/ChatListScreen.kt, ChatScreen.kt, CreateChatDialog.kt
    ui/components/…, ui/theme/…, ui/util/…
```

## Как это синхронизировано

- Тот же backend и та же БД, что и у веб-приложения: любые сообщения/каналы видны и там, и там.
- Отправка из приложения сразу появляется в вебе (событие `MessageCreated` в Reverb).
- Сообщения из веба приходят в приложение по WebSocket; если WS недоступен — подхватываются polling-ом (каждые 6 c).

## Возможные проблемы

- **«Нет соединения с сервером»** — проверьте адрес/порт в настройках приложения («Сервер: …») и что сервер доступен (в проде — порт 80).
- **Сообщения не приходят мгновенно** — проверьте, что сервис Reverb работает и порт сервера доступен; приложение продолжит работать через polling.
- **Cleartext HTTP/WS** разрешён в манифесте (`android:usesCleartextTraffic="true"`) — для локальной разработки.
