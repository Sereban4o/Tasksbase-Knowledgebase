import type { Chat, ChatMember, User } from './types'

export type InfoTarget =
  | { kind: 'chat'; chatId: number }
  | { kind: 'user'; userId: number; seed?: Partial<User> }

export const MEMBER_ROLE_LABELS: Record<string, string> = {
  admin: 'Администратор',
  member: 'Участник',
  muted: 'Заглушён',
}

export const USER_STATUS_LABELS: Record<string, string> = {
  active: 'Активен',
  suspended: 'Заблокирован',
  pending: 'Ожидает подтверждения',
}

export function getUserStatusLabel(status?: string | null): string {
  if (!status) return ''
  return USER_STATUS_LABELS[status] ?? status
}

export function getMemberRoleLabel(role?: string | null): string {
  if (!role) return ''
  return MEMBER_ROLE_LABELS[role] ?? role
}

/** Найти собеседника в личном чате (DM). */
export function findDmPartner(chat: Chat, currentUserId?: number): ChatMember | undefined {
  if (chat.type !== 'dm') return undefined
  return chat.members?.find((m) => m.id !== currentUserId)
}

/** Найти участника чата по id пользователя. */
export function findChatMember(chats: Chat[], userId: number): ChatMember | undefined {
  for (const chat of chats) {
    const member = chat.members?.find((m) => m.id === userId)
    if (member) return member
  }
  return undefined
}

/**
 * Собрать данные профиля пользователя из доступных источников:
 * список всех пользователей → участники чатов → seed (переданный аватар/имя).
 */
export function resolveUserProfile(args: {
  userId: number
  users: User[]
  chats: Chat[]
  seed?: Partial<User>
}): User | null {
  const { userId, users, chats, seed } = args

  const fromUsers = users.find((u) => u.id === userId)
  if (fromUsers) return { ...fromUsers, ...seed, id: userId }

  const fromMembers = findChatMember(chats, userId)
  if (fromMembers) return { ...fromMembers, ...seed, id: userId }

  if (seed) {
    return {
      id: userId,
      email: seed.email ?? '',
      first_name: seed.first_name ?? '',
      last_name: seed.last_name ?? '',
      avatar: seed.avatar ?? null,
      status: seed.status,
    }
  }

  return null
}

/** Является ли текущий пользователь администратором чата. */
export function isChatAdmin(chat: Chat | null, currentUserId?: number): boolean {
  if (!chat || !currentUserId) return false
  const member = chat.members?.find((m) => m.id === currentUserId)
  if (member?.pivot?.role) return member.pivot.role === 'admin'
  return chat.created_by === currentUserId
}
