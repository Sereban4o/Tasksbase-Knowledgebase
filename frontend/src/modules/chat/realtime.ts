import { useEffect, useRef } from 'react'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/modules/auth/store/authStore'

let notificationAudio: HTMLAudioElement | null = null

const SOUND_KEY = 'chat_sound_enabled'
const VOLUME_KEY = 'chat_sound_volume'

export const soundSettings = {
  get enabled(): boolean {
    if (typeof window === 'undefined') return true
    return window.localStorage.getItem(SOUND_KEY) !== 'false'
  },
  set enabled(value: boolean) {
    if (typeof window === 'undefined') return
    window.localStorage.setItem(SOUND_KEY, String(value))
  },
  get volume(): number {
    if (typeof window === 'undefined') return 0.5
    const raw = window.localStorage.getItem(VOLUME_KEY)
    const parsed = raw ? parseFloat(raw) : 0.5
    return Number.isFinite(parsed) ? Math.min(Math.max(parsed, 0), 1) : 0.5
  },
  set volume(value: number) {
    if (typeof window === 'undefined') return
    window.localStorage.setItem(VOLUME_KEY, String(value))
  },
}

/** Lazily create the shared notification sound instance. */
function getAudio(): HTMLAudioElement | null {
  if (typeof window === 'undefined') return null
  if (!notificationAudio) {
    notificationAudio = new Audio('/sounds/notification.wav')
    notificationAudio.preload = 'auto'
  }
  return notificationAudio
}

export function playNotificationSound(volume?: number): void {
  if (!soundSettings.enabled) return
  const audio = getAudio()
  if (!audio) return
  audio.volume = volume ?? soundSettings.volume
  audio.currentTime = 0
  audio.play().catch(() => {
    /* autoplay may be blocked until first user interaction - ignore */
  })
}

function getEnv() {
  return ((import.meta as any).env || {}) as Record<string, string | undefined>
}

/** Create (or reuse) the shared Echo/Pusher websocket client. */
function getEcho(token: string): any {
  const w = window as any
  const env = getEnv()

  if (w.Echo && w.__echoToken === token) return w.Echo

  if (w.Echo) {
    try {
      w.Echo.disconnect()
    } catch {
      /* ignore */
    }
  }

  const scheme =
    env.VITE_REVERB_SCHEME ||
    env.VITE_PUSHER_SCHEME ||
    (typeof window !== 'undefined' && window.location.protocol === 'https:' ? 'https' : 'http')
  const forceTLS = scheme === 'https'
  const locationPort = typeof window !== 'undefined' ? window.location.port : ''
  const defaultPort = forceTLS ? 443 : 80
  const wsPort = Number(
    env.VITE_REVERB_PORT || env.VITE_PUSHER_PORT || locationPort || defaultPort,
  )

  w.Pusher = Pusher
  w.Echo = new Echo({
    broadcaster: 'pusher',
    key: env.VITE_REVERB_KEY || env.VITE_PUSHER_KEY || 'chat-key',
    cluster: env.VITE_REVERB_CLUSTER || env.VITE_PUSHER_CLUSTER || 'mt1',
    wsHost:
      env.VITE_REVERB_HOST ||
      env.VITE_PUSHER_HOST ||
      (typeof window !== 'undefined' ? window.location.hostname : 'localhost'),
    wsPort,
    wssPort: wsPort,
    forceTLS,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  })
  w.__echoToken = token

  return w.Echo
}

/**
 * Subscribes to the private `chat.{chatId}` channel over Laravel Reverb
 * (Pusher protocol) and keeps the React Query cache in sync with realtime events.
 */
export function useChatRealtime(chatId: number | null): void {
  const queryClient = useQueryClient()
  const { token } = useAuth()
  const channelRef = useRef<any>(null)

  useEffect(() => {
    if (!chatId || !token) return

    const echo = getEcho(token)
    const channel = echo.private(`chat.${chatId}`)
    channelRef.current = channel

    const refresh = () => {
      queryClient.invalidateQueries({ queryKey: ['chat-messages', chatId] })
      queryClient.invalidateQueries({ queryKey: ['chat-my'] })
    }

    channel.listen('MessageCreated', refresh)
    channel.listen('MessageUpdated', refresh)
    channel.listen('MessageDeleted', refresh)

    return () => {
      try {
        channel.stopListening('MessageCreated')
        channel.stopListening('MessageUpdated')
        channel.stopListening('MessageDeleted')
        echo.leave(`chat.${chatId}`)
      } catch {
        /* ignore teardown errors */
      }
      channelRef.current = null
    }
  }, [chatId, token, queryClient])
}
