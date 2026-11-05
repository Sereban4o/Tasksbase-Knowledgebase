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
- Запущенный бэкенд проекта: `docker compose up -d` (nginx на порту `8000`, Reverb на `8080`).
- Для сборки APK нужен Android SDK (Android Studio ставит его автоматически).

## Быстрый старт

1. Откройте в Android Studio папку **`android/`** (File → Open).
2. Дождитесь Gradle Sync (Studio скачает Gradle 8.7 и зависимости).
3. Запустите эмулятор и нажмите Run ▶.
4. Войдите тестовым аккаунтом:
   - `admin@example.com` / `password`

## Настройка адреса сервера

Адреса заданы в `app/build.gradle.kts` (`defaultConfig.buildConfigField`):

```kotlin
buildConfigField("String", "API_BASE_URL", "\"http://10.0.2.2:8000/api/v1/\"")
buildConfigField("String", "WS_HOST", "\"10.0.2.2\"")
buildConfigField("int", "WS_PORT", "8080")
buildConfigField("String", "WS_KEY", "\"chat-key\"")
buildConfigField("String", "BROADCAST_AUTH_URL", "\"http://10.0.2.2:8000/broadcasting/auth\"")
```

- **Эмулятор**: `10.0.2.2` — это хост-машина. Ничего менять не нужно.
- **Реальное устройство** (тот же Wi-Fi): замените `10.0.2.2` на LAN-IP вашего ПК,
  например `192.168.1.10`. Также убедитесь, что порты `8000` и `8080` доступны в локальной сети.
- **Устройство по USB** (альтернатива): выполните
  `adb reverse tcp:8000 tcp:8000` и `adb reverse tcp:8080 tcp:8080`,
  а в конфиге используйте `127.0.0.1` вместо `10.0.2.2`.

После изменения `buildConfigField` — Sync Project и пересоберите.

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

Real-time: WebSocket `ws://HOST:8080/app/chat-key` (протокол Pusher), приватный канал
`private-chat.{chatId}`, события `MessageCreated` / `MessageUpdated` / `MessageDeleted`.

## Структура проекта

```
android/
  build.gradle.kts, settings.gradle.kts, gradle.properties
  app/build.gradle.kts                 # версии, зависимости, buildConfigField (адреса)
  app/src/main/AndroidManifest.xml
  app/src/main/java/com/tasksbase/chat/
    ChatApplication.kt                 # загрузка сессии при старте
    MainActivity.kt
    di/ServiceLocator.kt, Session.kt    # ручной DI, текущая сессия
    data/remote/Dtos.kt, ApiService.kt, AuthInterceptor.kt
    data/local/TokenStore.kt           # DataStore: токен + пользователь
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

- **«Нет соединения с сервером»** — проверьте, что `docker compose up -d` запущен и адрес в `buildConfigField` корректный (эмулятор → `10.0.2.2`).
- **Сообщения не приходят мгновенно** — проверьте, что контейнер `tasksbase-knowledgebase-reverb` запущен и порт `8080` доступен; приложение продолжит работать через polling.
- **Cleartext HTTP/WS** разрешён в манифесте (`android:usesCleartextTraffic="true"`) — для локальной разработки.
