# Chat System Documentation

## Overview
Full-featured real-time chat system (like RocketChat/Telegram) integrated with Laravel backend and React frontend.

## Backend (Laravel)

### Database Tables
- `chats` - Chat containers (dm, group, channel)
- `chat_members` - User-chat relationships with roles
- `messages` - Messages with text, files, replies
- `message_files` - Attached files (images, documents)
- `message_reactions` - Emoji reactions on messages
- `online_users` - Real-time online status

### API Endpoints

#### Chats
- `GET /api/v1/chat/my` - Get current user's chats
- `GET /api/v1/chat` - Paginated chat list (admin)
- `POST /api/v1/chat` - Create new chat
  ```json
  {
    "type": "dm|group|channel",
    "name": "Chat Name", // required for group/channel
    "description": "Optional description",
    "user_ids": [1, 2, 3] // user IDs to add
  }
  ```
- `GET /api/v1/chat/{chatId}` - Get chat details
- `PATCH /api/v1/chat/{chatId}` - Update chat
- `DELETE /api/v1/chat/{chatId}` - Delete chat
- `POST /api/v1/chat/{chatId}/members` - Add members
  ```json
  { "user_ids": [1, 2, 3] }
  ```
- `POST /api/v1/chat/{chatId}/members/remove` - Remove member
  ```json
  { "user_id": 1 }
  ```
- `POST /api/v1/chat/{chatId}/read` - Mark as read
- `GET /api/v1/chat/search-users?q=email` - Search users by email/name

#### Messages
- `GET /api/v1/chat/{chatId}/messages` - Get messages (paginated)
- `POST /api/v1/chat/{chatId}/messages` - Send message
  ```json
  {
    "body": "Message text",
    "type": "text|image|file|system",
    "parent_id": null, // for replies
    "file_ids": []
  }
  ```
- `GET /api/v1/chat/{chatId}/messages/{messageId}` - Get message
- `PATCH /api/v1/chat/{chatId}/messages/{messageId}` - Edit message
  ```json
  { "body": "Edited message" }
  ```
- `DELETE /api/v1/chat/{chatId}/messages/{messageId}` - Delete message
- `POST /api/v1/chat/{chatId}/messages/{messageId}/react` - Add reaction
  ```json
  { "emoji": "👍" }
  ```
- `DELETE /api/v1/chat/{chatId}/messages/{messageId}/react/{emoji}` - Remove reaction

### Real-time Events (Laravel Echo)

Channel: `chat.{chatId}`

Events:
- `MessageCreated` - New message sent
- `MessageUpdated` - Message edited
- `MessageDeleted` - Message deleted

### Models
- `Chat` - Relationships: members, messages, lastMessage, creator
- `ChatMember` - Relationships: user, chat
- `Message` - Relationships: chat, sender, parent, replies, files, reactions
- `MessageFile` - Relationships: message, file
- `MessageReaction` - Relationships: message, user

## Frontend (React)

### Components
- `ChatPage` - Main chat container
- `ChatList` - Sidebar with list of chats
- `ChatWindow` - Messages display with auto-scroll
- `MessageInput` - Text input with emoji picker & file upload

### State Management (Zustand)
Store location: `frontend/src/modules/chat/store.ts`

State:
```typescript
{
  chats: Chat[],
  activeChatId: number | null,
  messages: Record<number, Message[]>,
  currentUser: User | null,
  userId: number | null,
  // Actions
  loadChats(),
  setActiveChat(chatId),
  loadMessages(chatId),
  sendMessage(chatId, body, parentId?),
  addReaction(chatId, messageId, emoji),
  removeReaction(chatId, messageId, emoji),
  connectToChat(chatId),
  disconnectFromChat(chatId),
  createPrivateChat(),
  setCurrentUser(user)
}
```

### API Client
Location: `frontend/src/modules/chat/api.ts`

Methods:
- `chatApi.getMyChats()`
- `chatApi.createChat(data)`
- `chatApi.getChat(chatId)`
- `chatApi.updateChat(chatId, data)`
- `chatApi.deleteChat(chatId)`
- `chatApi.addMembers(chatId, user_ids)`
- `chatApi.removeMember(chatId, userId)`
- `chatApi.markAsRead(chatId)`
- `chatApi.searchUsers(q)`
- `chatApi.getMessages(chatId, page, before?)`
- `chatApi.sendMessage(chatId, data)`
- `chatApi.getMessage(chatId, messageId)`
- `chatApi.updateMessage(chatId, messageId, body)`
- `chatApi.deleteMessage(chatId, messageId)`
- `chatApi.addReaction(chatId, messageId, emoji)`
- `chatApi.removeReaction(chatId, messageId, emoji)`

### TypeScript Types
Location: `frontend/src/modules/chat/types.ts`

Interfaces:
- `Chat` - Chat entity with metadata
- `ChatMember` - User membership in chat
- `Message` - Message with sender, files, reactions
- `MessageFile` - Attached file with URL
- `MessageReaction` - Emoji reaction with user count
- `User` - User profile info

## Features

### Implemented
✅ Create DM/group/channel chats
✅ Send text messages
✅ Real-time messaging via Laravel Echo + Redis
✅ Message editing & deletion
✅ Emoji reactions
✅ File attachments (images with preview)
✅ Message replies (threading support)
✅ Chat list with last message preview
✅ Online user tracking
✅ Mark chats as read
✅ User search by email

### TODO
⏳ File upload to messages (images, documents)
⏳ Full emoji picker library (e.g., emoji-mart)
⏳ Message search within chat
⏳ Mute/unmute users
⏳ Chat pinning
⏳ Unread message counters
⏳ Typing indicators
⏳ Voice messages
⏳ Video calls

## Testing

### Create Chat via API
```bash
# First login to get token
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Then create chat (use token from login response)
curl -X POST http://localhost/api/v1/chat \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "group",
    "name": "Test Group",
    "user_ids": [1, 2, 3]
  }'
```

### Send Message via API
```bash
curl -X POST http://localhost/api/v1/chat/1/messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "body": "Hello world!",
    "type": "text"
  }'
```

### Get Messages
```bash
curl http://localhost/api/v1/chat/1/messages \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test Script
Python test script available at `test_chat.py`:
```bash
python test_chat.py
```

## Docker Services
- Backend API: `tasksbase-knowledgebase-app:8000`
- Frontend: `tasksbase-knowledgebase-frontend:5173`
- Nginx: `tasksbase-knowledgebase-nginx:80`
- PostgreSQL: `tasksbase-knowledgebase-postgres:5432`
- Redis: `tasksbase-knowledgebase-redis:6379`

## Database
- Database name: `tasksbase_knowledgebase`
- Chat tables: `chats`, `chat_members`, `messages`, `message_files`, `message_reactions`, `online_users`
