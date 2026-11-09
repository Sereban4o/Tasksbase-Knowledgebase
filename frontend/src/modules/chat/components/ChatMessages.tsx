import React, { useEffect, useMemo, useRef } from 'react'
import { format, isToday, isYesterday } from 'date-fns'
import { ru } from 'date-fns/locale'
import { useQuery } from '@tanstack/react-query'
import { Box, CircularProgress, Alert, Typography } from '@mui/material'
import { DoneAll as DoneAllIcon } from '@mui/icons-material'
import apiClient from '@/modules/shared/api/apiClient'
import { unwrapList } from '@/modules/shared/utils/apiResponse'
import { useAuth } from '@/modules/auth/store/authStore'
import { getInitials, getUserAvatarColor } from '../utils'
import ChatAvatar from './ChatAvatar'
import { playNotificationSound } from '../realtime'
import { tg } from '../theme'
import type { Message } from '../types'

interface ChatMessagesProps {
  chatId: number
  onAvatarClick?: (sender: {
    id: number
    first_name?: string
    last_name?: string
    avatar?: string | null
  }) => void
}

interface MessageGroup {
  key: number
  senderId: number
  isOwn: boolean
  sender: Message['sender']
  messages: Message[]
}

type RenderItem =
  | { kind: 'date'; key: string; label: string }
  | { kind: 'group'; group: MessageGroup }

function dayLabel(dateStr: string): string {
  const date = new Date(dateStr)
  if (isToday(date)) return 'Сегодня'
  if (isYesterday(date)) return 'Вчера'
  return format(date, 'd MMMM', { locale: ru })
}

function ChatMessages({ chatId, onAvatarClick }: ChatMessagesProps) {
  const bottomRef = useRef<HTMLDivElement>(null)
  const lastMessageIdRef = useRef<number>(0)
  const { user } = useAuth()
  const currentUserId = user?.id

  const {
    data: messages = [],
    isLoading,
    isError,
  } = useQuery<Message[]>({
    queryKey: ['chat-messages', chatId],
    queryFn: async () => {
      const res = await apiClient.get(`/v1/chat/${chatId}/messages`, {
        params: { per_page: 50 },
      })
      // API returns newest-first; render oldest-first.
      return unwrapList<Message>(res).slice().reverse()
    },
    staleTime: 5_000,
    refetchInterval: 30_000,
    refetchOnWindowFocus: true,
  })

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  // Notification sound for new incoming messages (skips the initial load).
  useEffect(() => {
    if (!messages.length) return
    const maxId = messages.reduce((max, m) => (m.id > max ? m.id : max), 0)
    const previousMax = lastMessageIdRef.current

    if (previousMax === 0) {
      lastMessageIdRef.current = maxId
      return
    }

    if (maxId > previousMax) {
      const hasIncoming = messages.some(
        (m) => m.id > previousMax && m.sender_id !== currentUserId,
      )
      if (hasIncoming) playNotificationSound()
    }

    lastMessageIdRef.current = maxId
  }, [messages, currentUserId])

  // Build a render list: date dividers + consecutive-sender groups.
  const items = useMemo<RenderItem[]>(() => {
    const out: RenderItem[] = []
    let current: MessageGroup | null = null
    let lastDay = ''

    const flush = () => {
      if (current) {
        out.push({ kind: 'group', group: current })
        current = null
      }
    }

    for (const message of messages) {
      const day = format(new Date(message.created_at), 'yyyy-MM-dd')
      if (day !== lastDay) {
        flush()
        out.push({ kind: 'date', key: day, label: dayLabel(message.created_at) })
        lastDay = day
      }

      const isOwn = message.sender_id === currentUserId
      if (current && current.senderId === message.sender_id && current.isOwn === isOwn) {
        current.messages.push(message)
      } else {
        flush()
        current = {
          key: message.id,
          senderId: message.sender_id,
          isOwn,
          sender: message.sender,
          messages: [message],
        }
      }
    }

    flush()
    return out
  }, [messages, currentUserId])

  if (isLoading && messages.length === 0) {
    return (
      <Box sx={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <CircularProgress size={28} />
      </Box>
    )
  }

  if (isError) {
    return (
      <Box sx={{ flex: 1, p: 2 }}>
        <Alert severity="error">Не удалось загрузить сообщения</Alert>
      </Box>
    )
  }

  if (messages.length === 0) {
    return (
      <Box
        sx={{
          flex: 1,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          p: 3,
          bgcolor: tg.chatBg,
        }}
      >
        <Box
          sx={{
            px: 2,
            py: 1,
            borderRadius: 4,
            bgcolor: 'rgba(255,255,255,0.7)',
            color: tg.secondaryText,
            fontSize: '0.85rem',
          }}
        >
          Сообщений пока нет. Напишите первым!
        </Box>
      </Box>
    )
  }

  return (
    <Box
      role="log"
      aria-live="polite"
      aria-label="Сообщения чата"
      sx={{
        flex: 1,
        overflowY: 'auto',
        bgcolor: tg.chatBg,
        px: 1,
        py: 1.5,
        display: 'flex',
        flexDirection: 'column',
        gap: '1px',
      }}
    >
      {items.map((item) => {
        if (item.kind === 'date') {
          return (
            <Box key={`date-${item.key}`} sx={{ display: 'flex', justifyContent: 'center', my: 1 }}>
              <Box
                sx={{
                  px: 1.25,
                  py: 0.25,
                  borderRadius: 1,
                  bgcolor: 'rgba(0,0,0,0.12)',
                  color: '#fff',
                  fontSize: '0.72rem',
                  fontWeight: 500,
                  backdropFilter: 'blur(4px)',
                }}
              >
                {item.label}
              </Box>
            </Box>
          )
        }

        const { group } = item
        const senderId = group.sender?.id ?? group.senderId
        const avatarColor = getUserAvatarColor(senderId)
        const initials = getInitials(group.sender?.first_name, group.sender?.last_name)
        const senderName =
          [group.sender?.first_name, group.sender?.last_name].filter(Boolean).join(' ') ||
          'Пользователь'

        return (
          <Box
            key={`group-${group.key}`}
            sx={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: group.isOwn ? 'flex-end' : 'flex-start',
              mb: 0.5,
            }}
          >
            {group.messages.map((message, index) => {
              const isLast = index === group.messages.length - 1
              const time = message.created_at
                ? format(new Date(message.created_at), 'HH:mm', { locale: ru })
                : ''
              const bg = group.isOwn ? tg.outgoingBg : tg.incomingBg
              const metaColor = group.isOwn ? tg.metaOutgoing : tg.metaIncoming

              return (
                <Box
                  key={message.id}
                  component="article"
                  aria-label={`${senderName}, ${time}`}
                  sx={{
                    display: 'flex',
                    alignItems: 'flex-end',
                    gap: 0.5,
                    maxWidth: '85%',
                    mb: '2px',
                  }}
                >
                  {!group.isOwn && (
                    <Box sx={{ width: 34, flexShrink: 0 }}>
                      {isLast && (
                        <ChatAvatar
                          src={group.sender?.avatar}
                          initials={initials || '?'}
                          color={avatarColor}
                          size={34}
                          label={senderName}
                          onClick={() =>
                            onAvatarClick?.({
                              id: senderId,
                              first_name: group.sender?.first_name,
                              last_name: group.sender?.last_name,
                              avatar: group.sender?.avatar,
                            })
                          }
                        />
                      )}
                    </Box>
                  )}

                  <Box
                    sx={{
                      position: 'relative',
                      px: 1.25,
                      py: 0.75,
                      bgcolor: bg,
                      color: tg.text,
                      borderRadius: `${tg.bubbleRadius}px`,
                      borderBottomRightRadius: group.isOwn && isLast ? 2 : tg.bubbleRadius,
                      borderBottomLeftRadius: !group.isOwn && isLast ? 2 : tg.bubbleRadius,
                      boxShadow: '0 1px 1px rgba(0,0,0,0.08)',
                      '&::after': isLast
                        ? {
                            content: '""',
                            position: 'absolute',
                            bottom: 0,
                            width: 11,
                            height: 11,
                            bgcolor: bg,
                            ...(group.isOwn
                              ? {
                                  right: -8,
                                  clipPath: 'polygon(0 0, 100% 100%, 0 100%)',
                                }
                              : {
                                  left: -8,
                                  clipPath: 'polygon(100% 0, 0 100%, 100% 100%)',
                                }),
                          }
                        : undefined,
                    }}
                  >
                    {message.body && (
                      <Typography
                        variant="body2"
                        sx={{
                          whiteSpace: 'pre-wrap',
                          wordBreak: 'break-word',
                          fontSize: '0.9rem',
                          lineHeight: 1.35,
                        }}
                      >
                        {message.body}
                      </Typography>
                    )}

                    {message.files && message.files.length > 0 && (
                      <Box sx={{ mt: 0.75, display: 'flex', flexDirection: 'column', gap: 0.75 }}>
                        {message.files.map((file) => {
                          const isImage = file.mime_type?.startsWith('image/')
                          const url = file.url || file.thumbnail_url
                          if (isImage && url) {
                            return (
                              <a key={file.id} href={url} target="_blank" rel="noopener noreferrer">
                                <Box
                                  component="img"
                                  src={url}
                                  alt={file.file_name || 'Изображение'}
                                  sx={{
                                    maxWidth: '100%',
                                    maxHeight: 220,
                                    borderRadius: 1,
                                    objectFit: 'cover',
                                    display: 'block',
                                  }}
                                />
                              </a>
                            )
                          }
                          return (
                            <a
                              key={file.id}
                              href={url || '#'}
                              target="_blank"
                              rel="noopener noreferrer"
                              style={{ color: tg.accent, fontSize: '0.85rem' }}
                            >
                              📎 {file.file_name || 'Файл'}
                            </a>
                          )
                        })}
                      </Box>
                    )}

                    <Box
                      sx={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'flex-end',
                        gap: 0.4,
                        mt: 0.25,
                        float: 'right',
                        pl: 1,
                      }}
                    >
                      {message.edited_at && (
                        <Box sx={{ fontSize: '0.65rem', color: metaColor }}>изм.</Box>
                      )}
                      <Box sx={{ fontSize: '0.68rem', color: metaColor }}>{time}</Box>
                      {group.isOwn && (
                        <DoneAllIcon
                          aria-label="Доставлено"
                          sx={{ fontSize: 15, color: tg.metaOutgoing, ml: -0.25 }}
                        />
                      )}
                    </Box>
                  </Box>
                </Box>
              )
            })}
          </Box>
        )
      })}
      <div ref={bottomRef} />
    </Box>
  )
}

export default React.memo(ChatMessages)
