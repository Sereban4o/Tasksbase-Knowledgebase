export interface User {
  id: number
  email: string
  first_name: string
  last_name: string
  avatar: string | null
  status?: string
  role?: { id: number; name: string } | null
}

export type ChatMemberRole = 'admin' | 'member' | 'muted'

export interface ChatMember extends User {
  pivot?: {
    chat_id: number
    user_id: number
    role: ChatMemberRole
    last_read_at: string | null
  }
}

export type ChatType = 'dm' | 'group' | 'channel'
export type MessageType = 'text' | 'image' | 'file' | 'system'

export interface MessageFile {
  id: number
  message_id: number
  file_id: number
  mime_type: string
  size: number
  thumbnail_url: string | null
  url?: string
  file_name?: string
}

export interface MessageReaction {
  id: number
  message_id: number
  user_id: number
  emoji: string
  user?: User
}

export interface Message {
  id: number
  chat_id: number
  sender_id: number
  parent_id: number | null
  body: string
  type: MessageType
  metadata: Record<string, unknown> | null
  edit_count: number
  edited_at: string | null
  deleted_at?: string | null
  created_at: string
  updated_at?: string
  sender?: User
  files?: MessageFile[]
  reactions?: MessageReaction[]
  replies?: Message[]
}

export interface Chat {
  id: number
  created_by: number
  name: string | null
  type: ChatType
  avatar: string | null
  description: string | null
  is_active: boolean
  last_message_at: string | null
  created_at: string
  updated_at: string
  messages_count?: number
  last_message?: Message | null
  creator?: User
  members?: ChatMember[]
}
