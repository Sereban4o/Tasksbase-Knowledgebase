import { useCallback, useEffect, useMemo, useRef, useState, type ChangeEvent } from 'react'
import {
  Alert,
  Avatar,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  ListItemAvatar,
  ListItemButton,
  ListItemText,
  Menu,
  MenuItem,
  Popover,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material'
import {
  ArrowBack as BackIcon,
  AttachFile as AttachFileIcon,
  Chat as ChatIcon,
  Close as CloseIcon,
  Edit as ComposeIcon,
  KeyboardArrowLeft as CollapseIcon,
  KeyboardArrowRight as ExpandIcon,
  MoreVert as MoreVertIcon,
  Search as SearchIcon,
  Send as SendIcon,
  SentimentSatisfiedAlt as EmojiIcon,
  Tag as TagIcon,
  VolumeOff as VolumeOffIcon,
  VolumeUp as VolumeUpIcon,
} from '@mui/icons-material'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/modules/shared/api/apiClient'
import { unwrapData, unwrapList } from '@/modules/shared/utils/apiResponse'
import { useAuth } from '@/modules/auth/store/authStore'
import ChatMessages from './ChatMessages'
import CreateChatDialog from './CreateChatDialog'
import ChatAvatar from './ChatAvatar'
import ChatInfoPanel from './ChatInfoPanel'
import UserInfoPanel from './UserInfoPanel'
import GroupInfoPanel from './GroupInfoPanel'
import { getFullName, getInitials, getUserAvatarColor, GROUP_CHAT_COLOR } from '../utils'
import { findChatMember, findDmPartner, isChatAdmin, resolveUserProfile, type InfoTarget } from '../info'
import { soundSettings, useChatRealtime, useUserRealtime } from '../realtime'
import { tg } from '../theme'
import type { Chat, User } from '../types'

const DRAWER_WIDTH = 420
const QUICK_EMOJIS = ['😀', '😂', '🥰', '😎', '🤔', '😢', '👍', '👎', '🔥', '❤️', '🎉', '🙏', '👏', '😉', '😮', '🤝', '💯', '✅', '🌟', '☕']

interface ChatAvatar {
  src?: string
  initials: string
  color: string
}

function resolveChatAvatar(chat: Chat, currentUserId?: number): ChatAvatar {
  if (chat.type === 'dm') {
    const other = chat.members?.find((m) => m.id !== currentUserId)
    if (other) {
      return {
        src: other.avatar || undefined,
        initials: getInitials(other.first_name, other.last_name) || '?',
        color: getUserAvatarColor(other.id),
      }
    }
  }
  if (chat.type === 'channel' && !chat.avatar) {
    return { initials: '#', color: GROUP_CHAT_COLOR }
  }
  if (chat.name) {
    return { src: chat.avatar || undefined, initials: chat.name[0].toUpperCase(), color: GROUP_CHAT_COLOR }
  }
  return { initials: '@', color: GROUP_CHAT_COLOR }
}

function resolveChatName(chat: Chat, currentUserId?: number): string {
  if (chat.name) return chat.name
  if (chat.type === 'dm') {
    const other = chat.members?.find((m) => m.id !== currentUserId)
    if (other) {
      const fullName = getFullName(other.first_name, other.last_name)
      return fullName || 'Личный чат'
    }
    return 'Личный чат'
  }
  return 'Чат'
}

function chatSubtitle(chat: Chat): string {
  if (chat.type === 'group') return `${chat.members?.length ?? 0} участников`
  if (chat.type === 'channel') return 'канал'
  return 'личный чат'
}

function ChatRow({
  chat,
  currentUserId,
  isActive,
  onClick,
  onAvatarClick,
}: {
  chat: Chat
  currentUserId?: number
  isActive: boolean
  onClick: () => void
  onAvatarClick?: (chat: Chat) => void
}) {
  const avatar = resolveChatAvatar(chat, currentUserId)
  const name = resolveChatName(chat, currentUserId)

  const preview = chat.last_message?.body
    ? chat.last_message.body.slice(0, 44) + ((chat.last_message.body.length ?? 0) > 44 ? '…' : '')
    : 'Нет сообщений'

  const time = chat.last_message?.created_at
    ? new Date(chat.last_message.created_at).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
      })
    : ''

  return (
    <ListItemButton
      onClick={onClick}
      aria-label={`Открыть чат ${name}`}
      aria-current={isActive ? 'true' : undefined}
      sx={{
        borderRadius: 2,
        px: 1,
        py: 0.75,
        alignItems: 'center',
        bgcolor: isActive ? tg.selected : 'transparent',
        '&:hover': { bgcolor: isActive ? tg.selected : tg.hover },
      }}
    >
      <ListItemAvatar sx={{ minWidth: 62 }}>
        <ChatAvatar
          src={avatar.src}
          initials={avatar.initials}
          color={avatar.color}
          size={50}
          label={`Профиль ${name}`}
          onClick={() => onAvatarClick?.(chat)}
        />
      </ListItemAvatar>
      <ListItemText
        disableTypography
        primary={
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', gap: 1 }}>
            <Typography noWrap sx={{ fontSize: '0.95rem', fontWeight: 600, color: tg.text }}>
              {name}
            </Typography>
            <Typography sx={{ fontSize: '0.72rem', color: tg.secondaryText, flexShrink: 0 }}>
              {time}
            </Typography>
          </Box>
        }
        secondary={
          <Typography noWrap sx={{ fontSize: '0.83rem', color: tg.secondaryText, mt: 0.25 }}>
            {preview}
          </Typography>
        }
      />
    </ListItemButton>
  )
}

function UserRow({ user, onChat }: { user: User; onChat: (userId: number) => void }) {
  return (
    <ListItemButton
      onClick={() => onChat(user.id)}
      aria-label={`Написать ${getFullName(user.first_name, user.last_name)}`}
      sx={{ borderRadius: 2, px: 1, py: 0.75, '&:hover': { bgcolor: tg.hover } }}
    >
      <ListItemAvatar sx={{ minWidth: 62 }}>
        <Avatar
          src={user.avatar || undefined}
          sx={{ bgcolor: getUserAvatarColor(user.id), width: 50, height: 50 }}
        >
          {getInitials(user.first_name, user.last_name)}
        </Avatar>
      </ListItemAvatar>
      <ListItemText
        primary={<Typography sx={{ fontSize: '0.92rem', fontWeight: 600 }}>{getFullName(user.first_name, user.last_name) || user.email}</Typography>}
        secondary={<Typography sx={{ fontSize: '0.78rem', color: tg.secondaryText }}>{user.email}</Typography>}
      />
    </ListItemButton>
  )
}

export default function ChatWidget({
  open,
  minimized,
  onOpenChange,
  onMinimizedChange,
}: {
  open: boolean
  minimized: boolean
  onOpenChange: (open: boolean) => void
  onMinimizedChange: (minimized: boolean) => void
}) {
  const queryClient = useQueryClient()
  const { user } = useAuth()
  const currentUserId = user?.id

  const isMinimized = minimized
  const [showPeople, setShowPeople] = useState(false)
  const [createOpen, setCreateOpen] = useState(false)
  const [selectedChatId, setSelectedChatId] = useState<number | null>(null)
  const [infoTarget, setInfoTarget] = useState<InfoTarget | null>(null)
  const [messageText, setMessageText] = useState('')
  const [searchQuery, setSearchQuery] = useState('')
  const [soundOn, setSoundOn] = useState<boolean>(() => soundSettings.enabled)
  const [emojiAnchor, setEmojiAnchor] = useState<HTMLElement | null>(null)
  const [chatMenuAnchor, setChatMenuAnchor] = useState<HTMLElement | null>(null)
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const handleChatDeleted = useCallback(() => {
    setInfoTarget(null)
    setSelectedChatId(null)
  }, [])

  useChatRealtime(selectedChatId, handleChatDeleted)
  useUserRealtime()

  const { data: users = [], isLoading: usersLoading, isError: usersError } = useQuery<User[]>({
    queryKey: ['chat-users'],
    queryFn: async () => unwrapList<User>(await apiClient.get('/v1/users/all')),
    staleTime: 60_000,
  })

  const { data: chats = [], isLoading: chatsLoading, isError: chatsError } = useQuery<Chat[]>({
    queryKey: ['chat-my'],
    queryFn: async () => unwrapList<Chat>(await apiClient.get('/v1/chat/my', { params: { per_page: 50 } })),
    staleTime: 5_000,
    refetchInterval: 60_000,
    refetchOnWindowFocus: true,
  })

  const selectedChat = useMemo(
    () => chats.find((c) => c.id === selectedChatId) ?? null,
    [chats, selectedChatId],
  )

  const infoChat = useMemo(
    () => (infoTarget?.kind === 'chat' ? chats.find((c) => c.id === infoTarget.chatId) ?? null : null),
    [chats, infoTarget],
  )

  const infoUser = useMemo(
    () =>
      infoTarget?.kind === 'user'
        ? resolveUserProfile({ userId: infoTarget.userId, users, chats, seed: infoTarget.seed })
        : null,
    [chats, infoTarget, users],
  )

  useEffect(() => {
    if (!open || minimized) setInfoTarget(null)
  }, [open, minimized])

  const filteredUsers = useMemo(() => {
    const q = searchQuery.trim().toLowerCase()
    if (!q) return users
    return users.filter((u) => {
      const name = getFullName(u.first_name, u.last_name).toLowerCase()
      return name.includes(q) || u.email.toLowerCase().includes(q)
    })
  }, [users, searchQuery])

  const refreshMessages = useCallback(
    async (chatId: number) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['chat-messages', chatId] }),
        queryClient.invalidateQueries({ queryKey: ['chat-my'] }),
      ])
    },
    [queryClient],
  )

  const handleSelectChat = useCallback((chatId: number) => {
    setSelectedChatId(chatId)
    setShowPeople(false)
    setInfoTarget(null)
    apiClient.post(`/v1/chat/${chatId}/read`).catch(() => {})
  }, [])

  const handleStartChat = useCallback(
    async (userId: number) => {
      try {
        const res = await apiClient.post('/v1/chat', { type: 'dm', user_ids: [userId] })
        const chat = unwrapData<Chat>(res)
        if (chat?.id) {
          handleSelectChat(chat.id)
          await queryClient.invalidateQueries({ queryKey: ['chat-my'] })
        }
      } catch (error) {
        console.error('Failed to create chat:', error)
      }
    },
    [handleSelectChat, queryClient],
  )

  const handleCreatedChannel = useCallback(
    async (chat: Chat) => {
      setCreateOpen(false)
      handleSelectChat(chat.id)
      await queryClient.invalidateQueries({ queryKey: ['chat-my'] })
    },
    [handleSelectChat, queryClient],
  )

  const handleOpenChatInfo = useCallback(
    (chat: Chat) => {
      setEmojiAnchor(null)
      if (chat.type === 'dm') {
        const partner = findDmPartner(chat, currentUserId)
        if (partner) {
          setInfoTarget({ kind: 'user', userId: partner.id, seed: partner })
          return
        }
      }
      setInfoTarget({ kind: 'chat', chatId: chat.id })
    },
    [currentUserId],
  )

  const handleMessageAvatarClick = useCallback(
    (sender: { id: number; first_name?: string; last_name?: string; avatar?: string | null }) => {
      setInfoTarget({ kind: 'user', userId: sender.id, seed: sender })
    },
    [],
  )

  const handleRenameChat = useCallback(
    async (name: string) => {
      if (!selectedChatId) return
      await apiClient.patch(`/v1/chat/${selectedChatId}`, { name })
      await queryClient.invalidateQueries({ queryKey: ['chat-my'] })
    },
    [selectedChatId, queryClient],
  )

  const handleLeaveChat = useCallback(async () => {
    if (!selectedChatId) return
    await apiClient.post(`/v1/chat/${selectedChatId}/leave`)
    setInfoTarget(null)
    setSelectedChatId(null)
    await queryClient.invalidateQueries({ queryKey: ['chat-my'] })
  }, [selectedChatId, queryClient])

  const handleAddMembers = useCallback(
    async (userIds: number[]) => {
      if (!selectedChatId) return
      await apiClient.post(`/v1/chat/${selectedChatId}/members`, { user_ids: userIds })
      await queryClient.invalidateQueries({ queryKey: ['chat-my'] })
    },
    [selectedChatId, queryClient],
  )

  const handleDeleteChat = useCallback(async () => {
    if (!selectedChatId) return
    await apiClient.delete(`/v1/chat/${selectedChatId}`)
    setInfoTarget(null)
    setSelectedChatId(null)
    await queryClient.invalidateQueries({ queryKey: ['chat-my'] })
  }, [selectedChatId, queryClient])

  const handleSendMessage = useCallback(async () => {
    const text = messageText.trim()
    if (!text || !selectedChatId) return
    setMessageText('')
    try {
      await apiClient.post(`/v1/chat/${selectedChatId}/messages`, { body: text, type: 'text' })
      await refreshMessages(selectedChatId)
    } catch (error) {
      console.error('Failed to send message', error)
      setMessageText(text)
    }
  }, [messageText, selectedChatId, refreshMessages])

  const handleFileChange = useCallback(
    async (event: ChangeEvent<HTMLInputElement>) => {
      const file = event.target.files?.[0]
      event.target.value = ''
      if (!file || !selectedChatId) return

      try {
        const form = new FormData()
        form.append('file', file)
        const upload = await apiClient.post('/v1/files/upload', form)
        const uploaded = upload.data
        const type = file.type.startsWith('image/') ? 'image' : 'file'
        await apiClient.post(`/v1/chat/${selectedChatId}/messages`, {
          type,
          body: '',
          file_ids: [uploaded.id],
        })
        await refreshMessages(selectedChatId)
      } catch (error) {
        console.error('Failed to upload file', error)
      }
    },
    [selectedChatId, refreshMessages],
  )

  const handleToggleSound = useCallback(() => {
    setSoundOn((prev) => {
      const next = !prev
      soundSettings.enabled = next
      return next
    })
  }, [])

  const insertEmoji = useCallback((emoji: string) => {
    setMessageText((prev) => prev + emoji)
    setEmojiAnchor(null)
  }, [])

  const selectedAvatar = selectedChat ? resolveChatAvatar(selectedChat, currentUserId) : null
  const selectedName = selectedChat ? resolveChatName(selectedChat, currentUserId) : ''

  if (!open) {
    return (
      <IconButton
        onClick={() => onOpenChange(true)}
        aria-label="Открыть чат"
        sx={{
          position: 'fixed',
          bottom: 24,
          right: 24,
          zIndex: 1200,
          bgcolor: tg.accent,
          color: '#fff',
          boxShadow: 3,
          width: 56,
          height: 56,
          '&:hover': { bgcolor: tg.accentHover },
        }}
      >
        <ChatIcon />
      </IconButton>
    )
  }

  if (minimized) {
    return (
      <Box
        sx={{
          position: 'fixed',
          bottom: 24,
          right: 24,
          zIndex: 1200,
          display: 'flex',
          alignItems: 'center',
          gap: 0.5,
          pl: 1.5,
          pr: 0.5,
          py: 0.5,
          bgcolor: tg.surface,
          border: `1px solid ${tg.divider}`,
          borderRadius: 2,
          boxShadow: 2,
        }}
      >
        <ChatIcon sx={{ color: tg.accent, fontSize: 20 }} />
        <Typography sx={{ fontSize: '0.9rem', fontWeight: 600, color: tg.text, mr: 0.5 }}>
          Чаты
        </Typography>
        <IconButton size="small" onClick={() => onMinimizedChange(false)} aria-label="Развернуть">
          <ExpandIcon fontSize="small" />
        </IconButton>
        <IconButton size="small" onClick={() => onOpenChange(false)} aria-label="Закрыть чат">
          <CloseIcon fontSize="small" />
        </IconButton>
      </Box>
    )
  }

  return (
    <>
    <Box
      sx={{
        position: 'fixed',
        top: { xs: 56, sm: 64 },
        right: 0,
        bottom: 0,
        width: DRAWER_WIDTH,
        maxWidth: '100vw',
        zIndex: (theme) => theme.zIndex.appBar - 1,
        bgcolor: tg.surface,
        borderLeft: `1px solid ${tg.divider}`,
        display: 'flex',
        flexDirection: 'column',
      }}
    >
      {/* Header */}
      <Box
        sx={{
          px: 1.5,
          py: 1,
          display: 'flex',
          alignItems: 'center',
          gap: 1,
          borderBottom: `1px solid ${tg.divider}`,
          bgcolor: tg.surface,
          minHeight: 56,
        }}
      >
        {selectedChatId ? (
          <>
            <IconButton size="small" onClick={() => setSelectedChatId(null)} aria-label="Назад к списку чатов">
              <BackIcon />
            </IconButton>
            {selectedAvatar && (
              <ChatAvatar
                src={selectedAvatar.src}
                initials={selectedAvatar.initials}
                color={selectedAvatar.color}
                size={34}
                label={`Профиль ${selectedName}`}
                onClick={() => selectedChat && handleOpenChatInfo(selectedChat)}
              />
            )}
            <Box sx={{ flex: 1, minWidth: 0 }}>
              <Typography noWrap sx={{ fontSize: '0.95rem', fontWeight: 600, color: tg.text }}>
                {selectedName}
              </Typography>
              <Typography noWrap sx={{ fontSize: '0.72rem', color: tg.secondaryText }}>
                {selectedChat ? chatSubtitle(selectedChat) : ''}
              </Typography>
            </Box>
            <IconButton
              size="small"
              onClick={(e) => setChatMenuAnchor(e.currentTarget)}
              aria-label="Действия с чатом"
            >
              <MoreVertIcon />
            </IconButton>
          </>
        ) : showPeople ? (
          <>
            <IconButton size="small" onClick={() => setShowPeople(false)} aria-label="Назад">
              <BackIcon />
            </IconButton>
            <Typography sx={{ flex: 1, fontSize: '1.1rem', fontWeight: 700, color: tg.text }}>
              Новый чат
            </Typography>
          </>
        ) : (
          <>
            <Typography sx={{ flex: 1, fontSize: '1.15rem', fontWeight: 700, color: tg.text }}>
              Чаты
            </Typography>
            <Tooltip title="Новый чат">
              <IconButton size="small" onClick={() => setShowPeople(true)} aria-label="Новый чат">
                <ComposeIcon />
              </IconButton>
            </Tooltip>
            <Tooltip title="Создать канал">
              <IconButton size="small" onClick={() => setCreateOpen(true)} aria-label="Создать канал">
                <TagIcon />
              </IconButton>
            </Tooltip>
          </>
        )}

        <Tooltip title={soundOn ? 'Выключить звук' : 'Включить звук'}>
          <IconButton
            size="small"
            onClick={handleToggleSound}
            aria-label={soundOn ? 'Выключить звук' : 'Включить звук'}
            aria-pressed={soundOn}
          >
            {soundOn ? <VolumeUpIcon fontSize="small" /> : <VolumeOffIcon fontSize="small" />}
          </IconButton>
        </Tooltip>
        <IconButton
          size="small"
          onClick={() => onMinimizedChange(!minimized)}
          aria-label={isMinimized ? 'Развернуть' : 'Свернуть'}
        >
          {isMinimized ? <ExpandIcon /> : <CollapseIcon />}
        </IconButton>
        <IconButton size="small" onClick={() => onOpenChange(false)} aria-label="Закрыть чат">
          <CloseIcon />
        </IconButton>
      </Box>

      {isMinimized ? (
        <Box sx={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
          <Typography sx={{ fontSize: '0.85rem', color: tg.secondaryText }}>Чат свёрнут</Typography>
        </Box>
      ) : selectedChatId ? (
        <>
          <ChatMessages chatId={selectedChatId} onAvatarClick={handleMessageAvatarClick} />

          {/* Composer */}
          <Box
            sx={{
              display: 'flex',
              alignItems: 'flex-end',
              gap: 0.5,
              px: 1,
              py: 0.75,
              bgcolor: tg.surface,
              borderTop: `1px solid ${tg.divider}`,
            }}
          >
            <input ref={fileInputRef} type="file" hidden onChange={handleFileChange} />
            <IconButton onClick={() => fileInputRef.current?.click()} aria-label="Прикрепить файл" sx={{ color: tg.secondaryText }}>
              <AttachFileIcon />
            </IconButton>

            <TextField
              fullWidth
              multiline
              maxRows={4}
              size="small"
              placeholder="Написать сообщение..."
              aria-label="Поле ввода сообщения"
              value={messageText}
              onChange={(e) => setMessageText(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                  e.preventDefault()
                  handleSendMessage()
                }
              }}
              sx={{
                '& .MuiOutlinedInput-root': {
                  bgcolor: tg.searchBg,
                  borderRadius: 5,
                  paddingRight: '36px',
                  fontSize: '0.9rem',
                  '& fieldset': { border: 'none' },
                },
              }}
              InputProps={{
                endAdornment: (
                  <IconButton
                    size="small"
                    onClick={(e) => setEmojiAnchor(e.currentTarget)}
                    aria-label="Вставить эмодзи"
                    sx={{ color: tg.secondaryText }}
                  >
                    <EmojiIcon />
                  </IconButton>
                ),
              }}
            />

            <IconButton
              onClick={handleSendMessage}
              disabled={!messageText.trim()}
              aria-label="Отправить сообщение"
              sx={{
                width: 44,
                height: 44,
                bgcolor: messageText.trim() ? tg.accent : tg.searchBg,
                color: messageText.trim() ? '#fff' : tg.secondaryText,
                borderRadius: '50%',
                '&:hover': { bgcolor: messageText.trim() ? tg.accentHover : tg.searchBg },
                '&.Mui-disabled': { bgcolor: tg.searchBg, color: tg.secondaryText },
              }}
            >
              <SendIcon sx={{ fontSize: 20 }} />
            </IconButton>
          </Box>

          <Popover
            open={Boolean(emojiAnchor)}
            anchorEl={emojiAnchor}
            onClose={() => setEmojiAnchor(null)}
            anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
            transformOrigin={{ vertical: 'bottom', horizontal: 'right' }}
          >
            <Box
              sx={{
                display: 'grid',
                gridTemplateColumns: 'repeat(5, 44px)',
                p: 1,
                gap: 0.5,
              }}
            >
              {QUICK_EMOJIS.map((emoji) => (
                <IconButton key={emoji} onClick={() => insertEmoji(emoji)} aria-label={`Эмодзи ${emoji}`}>
                  <span style={{ fontSize: 22 }}>{emoji}</span>
                </IconButton>
              ))}
            </Box>
          </Popover>
        </>
      ) : showPeople ? (
        <>
          <Box sx={{ p: 1 }}>
            <TextField
              fullWidth
              size="small"
              placeholder="Поиск людей..."
              aria-label="Поиск пользователей"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              autoFocus
              sx={{ '& .MuiOutlinedInput-root': { bgcolor: tg.searchBg, borderRadius: 3, '& fieldset': { border: 'none' } } }}
              InputProps={{
                startAdornment: <SearchIcon fontSize="small" sx={{ mr: 1, color: tg.secondaryText }} />,
              }}
            />
          </Box>
          <Box sx={{ flex: 1, overflow: 'auto', px: 0.5 }}>
            {usersLoading && (
              <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
                <CircularProgress size={24} />
              </Box>
            )}
            {usersError && (
              <Alert severity="error" sx={{ m: 1 }}>
                Не удалось загрузить пользователей
              </Alert>
            )}
            {filteredUsers.map((u) => (
              <UserRow key={u.id} user={u} onChat={handleStartChat} />
            ))}
            {!usersLoading && !usersError && filteredUsers.length === 0 && (
              <Typography sx={{ fontSize: '0.85rem', color: tg.secondaryText, textAlign: 'center', mt: 4 }}>
                Пользователи не найдены
              </Typography>
            )}
          </Box>
        </>
      ) : (
        <>
          <Box sx={{ p: 1 }}>
            <TextField
              fullWidth
              size="small"
              placeholder="Поиск чатов..."
              aria-label="Поиск чатов"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              sx={{ '& .MuiOutlinedInput-root': { bgcolor: tg.searchBg, borderRadius: 3, '& fieldset': { border: 'none' } } }}
              InputProps={{
                startAdornment: <SearchIcon fontSize="small" sx={{ mr: 1, color: tg.secondaryText }} />,
              }}
            />
          </Box>
          <Box sx={{ flex: 1, overflow: 'auto', px: 0.5, pb: 1 }}>
            {chatsLoading && (
              <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
                <CircularProgress size={24} />
              </Box>
            )}
            {chatsError && (
              <Alert severity="error" sx={{ m: 1 }}>
                Не удалось загрузить чаты
              </Alert>
            )}
            {!chatsLoading &&
              !chatsError &&
              chats.map((chat) => (
                <ChatRow
                  key={chat.id}
                  chat={chat}
                  currentUserId={currentUserId}
                  isActive={chat.id === selectedChatId}
                  onClick={() => handleSelectChat(chat.id)}
                  onAvatarClick={handleOpenChatInfo}
                />
              ))}
            {!chatsLoading && !chatsError && chats.length === 0 && (
              <Typography sx={{ fontSize: '0.85rem', color: tg.secondaryText, textAlign: 'center', mt: 4 }}>
                Чатов пока нет. Начните переписку кнопкой «Новый чат».
              </Typography>
            )}
          </Box>
        </>
      )}

      <Menu
        anchorEl={chatMenuAnchor}
        open={Boolean(chatMenuAnchor)}
        onClose={() => setChatMenuAnchor(null)}
      >
        <MenuItem
          onClick={() => {
            setChatMenuAnchor(null)
            if (selectedChat) handleOpenChatInfo(selectedChat)
          }}
        >
          Информация
        </MenuItem>
        <MenuItem
          onClick={() => {
            setChatMenuAnchor(null)
            setConfirmDeleteOpen(true)
          }}
          sx={{ color: '#d32f2f' }}
        >
          Удалить чат
        </MenuItem>
      </Menu>

      <Dialog open={confirmDeleteOpen} onClose={() => setConfirmDeleteOpen(false)}>
        <DialogTitle>Удалить чат?</DialogTitle>
        <DialogContent>
          <Typography sx={{ fontSize: '0.9rem' }}>
            Чат будет удалён для всех участников. Это действие нельзя отменить.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setConfirmDeleteOpen(false)} sx={{ textTransform: 'none' }}>
            Отмена
          </Button>
          <Button
            color="error"
            onClick={() => {
              setConfirmDeleteOpen(false)
              handleDeleteChat()
            }}
            sx={{ textTransform: 'none' }}
          >
            Удалить
          </Button>
        </DialogActions>
      </Dialog>

      <ChatInfoPanel
        open={Boolean(infoTarget)}
        title={infoTarget?.kind === 'user' ? 'Профиль' : 'Информация'}
        onClose={() => setInfoTarget(null)}
      >
        {infoTarget?.kind === 'user' && infoUser && (
          <UserInfoPanel
            user={infoUser}
            memberRole={findChatMember(chats, infoUser.id)?.pivot?.role}
            isSelf={infoUser.id === currentUserId}
            canStartChat={
              !(
                selectedChat?.type === 'dm' &&
                findDmPartner(selectedChat, currentUserId)?.id === infoUser.id
              )
            }
            onStartChat={() => {
              setInfoTarget(null)
              handleStartChat(infoUser.id)
            }}
          />
        )}
        {infoTarget?.kind === 'chat' && infoChat && (
          <GroupInfoPanel
            chat={infoChat}
            currentUserId={currentUserId}
            canManage={isChatAdmin(infoChat, currentUserId)}
            users={users.filter((u) => !infoChat.members?.some((m) => m.id === u.id))}
            onRename={handleRenameChat}
            onLeave={handleLeaveChat}
            onAddMembers={handleAddMembers}
            onDelete={handleDeleteChat}
          />
        )}
      </ChatInfoPanel>
    </Box>
    <CreateChatDialog
      open={createOpen}
      users={users}
      defaultType="channel"
      onClose={() => setCreateOpen(false)}
      onCreated={handleCreatedChannel}
    />
    </>
  )
}
