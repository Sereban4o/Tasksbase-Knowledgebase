export const AVATAR_COLORS = [
  '#FF6B6B',
  '#4ECDC4',
  '#45B7D1',
  '#96CEB4',
  '#FFEAA7',
  '#DDA0DD',
  '#98D8C8',
  '#F7DC6F',
  '#BB8FCE',
  '#85C1E9',
]

export const GROUP_CHAT_COLOR = '#9C27B0'

/** Deterministic per-user avatar colour. */
export function getUserAvatarColor(userId?: number | null): string {
  if (!userId) return AVATAR_COLORS[0]
  return AVATAR_COLORS[userId % AVATAR_COLORS.length]
}

/** First letters of first/last name, e.g. "Admin User" -> "AU". */
export function getInitials(firstName?: string | null, lastName?: string | null): string {
  const first = (firstName || '').trim()[0] || ''
  const last = (lastName || '').trim()[0] || ''
  return (first + last).toUpperCase()
}

/** Build a human readable full name, safely handling missing parts. */
export function getFullName(
  firstName?: string | null,
  lastName?: string | null,
): string {
  return [firstName, lastName].filter(Boolean).join(' ').trim()
}
