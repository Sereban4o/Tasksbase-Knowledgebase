import { useEffect, useRef, type ReactNode } from 'react'
import { Box, Fade, IconButton, Slide, Typography } from '@mui/material'
import { ArrowBack as BackIcon } from '@mui/icons-material'
import { tg } from '../theme'

interface ChatInfoPanelProps {
  open: boolean
  title: string
  onClose: () => void
  children?: ReactNode
}

/** Выдвижная панель с информацией о пользователе/группе (внутри виджета чата). */
export default function ChatInfoPanel({ open, title, onClose, children }: ChatInfoPanelProps) {
  const closeRef = useRef<HTMLButtonElement>(null)

  useEffect(() => {
    if (!open) return
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKeyDown)
    closeRef.current?.focus()
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [open, onClose])

  return (
    <Box
      sx={{
        position: 'absolute',
        inset: 0,
        zIndex: 10,
        pointerEvents: open ? 'auto' : 'none',
      }}
    >
      <Fade in={open} mountOnEnter unmountOnExit>
        <Box
          onClick={onClose}
          sx={{ position: 'absolute', inset: 0, bgcolor: 'rgba(0,0,0,0.35)' }}
        />
      </Fade>
      <Slide direction="right" in={open} mountOnEnter unmountOnExit timeout={250}>
        <Box
          role="dialog"
          aria-modal="true"
          aria-label={title}
          sx={{
            position: 'absolute',
            inset: 0,
            bgcolor: tg.surface,
            display: 'flex',
            flexDirection: 'column',
            boxShadow: 3,
          }}
        >
          <Box
            sx={{
              px: 1.5,
              py: 1,
              display: 'flex',
              alignItems: 'center',
              gap: 1,
              borderBottom: `1px solid ${tg.divider}`,
              minHeight: 56,
            }}
          >
            <IconButton
              size="small"
              onClick={onClose}
              aria-label="Назад"
              ref={closeRef}
            >
              <BackIcon />
            </IconButton>
            <Typography sx={{ flex: 1, fontSize: '1rem', fontWeight: 700, color: tg.text }}>
              {title}
            </Typography>
          </Box>
          <Box sx={{ flex: 1, overflowY: 'auto', px: 1.5, pb: 2 }}>{children}</Box>
        </Box>
      </Slide>
    </Box>
  )
}
