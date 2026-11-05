import { useEffect, useRef, useCallback } from 'react'
import { io, Socket } from 'socket.io-client'
import { useAuth } from '@/modules/auth/store/authStore'
import { useQueryClient } from '@tanstack/react-query'

interface WebSocketHook {
  connect: () => void
  disconnect: () => void
  isConnected: boolean
  subscribeToProject: (projectId: number) => void
  subscribeToIssue: (issueId: number) => void
  subscribeToBoard: (boardId: number) => void
  subscribeToSpace: (spaceId: number) => void
  subscribeToPage: (pageId: number) => void
  subscribeToUser: () => void
  emit: (event: string, data: any) => void
}

const SOCKET_URL = import.meta.env.VITE_SOCKET_URL || 'http://localhost:8000'

export function useWebSocket(): WebSocketHook {
  const { token } = useAuth()
  const socketRef = useRef<Socket | null>(null)
  const queryClient = useQueryClient()
  const isConnectedRef = useRef(false)

  const connect = useCallback(() => {
    if (!token || socketRef.current?.connected) return

    socketRef.current = io(SOCKET_URL, {
      auth: {
        token: `Bearer ${token}`,
      },
      transports: ['websocket', 'polling'],
      withCredentials: true,
    })

    const socket = socketRef.current

    socket.on('connect', () => {
      isConnectedRef.current = true

      // Subscribe to user notifications channel
      socket.emit('subscribe-user')
    })

    socket.on('disconnect', () => {
      isConnectedRef.current = false
    })

    // ─── Issue Events ───
    socket.on('issue.updated', (data: any) => {
      queryClient.invalidateQueries({ queryKey: ['issues'] })
      queryClient.invalidateQueries({ queryKey: ['issue', data.id] })
    })

    socket.on('issue.status_changed', (data: any) => {
      queryClient.invalidateQueries({ queryKey: ['issues'] })
      queryClient.invalidateQueries({ queryKey: ['issue', data.id] })
    })

    // ─── Board Events ───
    socket.on('board.issue_moved', (data: any) => {
      queryClient.invalidateQueries({ queryKey: ['board', data.board_id] })
      queryClient.invalidateQueries({ queryKey: ['issues'] })
    })

    socket.on('board.updated', (data: any) => {
      queryClient.invalidateQueries({ queryKey: ['board', data.board_id] })
    })

    // ─── Comment Events ───
    socket.on('new_comment', (data: any) => {
      queryClient.invalidateQueries({ queryKey: ['issue-comments', data.issue_id] })
      queryClient.invalidateQueries({ queryKey: ['page-comments', data.page_id] })
    })

    // ─── Page Events ───
    socket.on('page.updated', (data: any) => {
      queryClient.invalidateQueries({ queryKey: ['page', data.id] })
      queryClient.invalidateQueries({ queryKey: ['pages'] })
    })

    // ─── Notification Events ───
    socket.on('notification.created', () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] })
      queryClient.setQueryData(['unread-count'], (old: number) => (old || 0) + 1)
    })

    // ─── Typing Events ───
    socket.on('user-typing', () => {
      // Handle typing indicator
    })

    return socket
  }, [token, queryClient])

  const disconnect = useCallback(() => {
    if (socketRef.current) {
      socketRef.current.disconnect()
      socketRef.current = null
      isConnectedRef.current = false
    }
  }, [])

  const subscribeToProject = useCallback((projectId: number) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('subscribe-project', projectId)
    }
  }, [])

  const subscribeToIssue = useCallback((issueId: number) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('subscribe-issue', issueId)
    }
  }, [])

  const subscribeToBoard = useCallback((boardId: number) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('subscribe-board', boardId)
    }
  }, [])

  const subscribeToSpace = useCallback((spaceId: number) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('subscribe-space', spaceId)
    }
  }, [])

  const subscribeToPage = useCallback((pageId: number) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('subscribe-page', pageId)
    }
  }, [])

  const subscribeToUser = useCallback(() => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('subscribe-user')
    }
  }, [])

  const emit = useCallback((event: string, data: any) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit(event, data)
    }
  }, [])

  // Auto-connect on mount
  useEffect(() => {
    connect()
    return () => disconnect()
  }, [connect, disconnect])

  return {
    connect,
    disconnect,
    get isConnected() { return isConnectedRef.current },
    subscribeToProject,
    subscribeToIssue,
    subscribeToBoard,
    subscribeToSpace,
    subscribeToPage,
    subscribeToUser,
    emit,
  }
}
