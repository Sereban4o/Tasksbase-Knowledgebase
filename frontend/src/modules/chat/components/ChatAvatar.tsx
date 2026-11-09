import type { MouseEvent } from 'react'
import { Avatar, ButtonBase, type SxProps, type Theme } from '@mui/material'

interface ChatAvatarProps {
  src?: string | null
  initials: string
  color: string
  size?: number
  label?: string
  onClick?: (event: MouseEvent<HTMLElement>) => void
  sx?: SxProps<Theme>
}

/**
 * Аватар чата/пользователя. Если передан onClick — становится кликабельным
 * (ButtonBase) и не всплывает событие, чтобы не срабатывал родительский клик
 * (например, строка чата в списке).
 */
export default function ChatAvatar({
  src,
  initials,
  color,
  size = 40,
  label,
  onClick,
  sx,
}: ChatAvatarProps) {
  const avatar = (
    <Avatar
      src={src || undefined}
      sx={{
        bgcolor: color,
        width: size,
        height: size,
        fontSize: size * 0.4,
        ...sx,
      }}
    >
      {initials}
    </Avatar>
  )

  if (!onClick) return avatar

  return (
    <ButtonBase
      component="span"
      onClick={(event) => {
        event.stopPropagation()
        onClick(event)
      }}
      aria-label={label}
      sx={{
        borderRadius: '50%',
        transition: 'opacity 0.15s',
        '&:hover': { opacity: 0.85 },
      }}
    >
      {avatar}
    </ButtonBase>
  )
}
