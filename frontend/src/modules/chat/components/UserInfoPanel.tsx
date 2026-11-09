import { Box, Button, Typography } from '@mui/material'
import { Email as EmailIcon } from '@mui/icons-material'
import { getFullName, getInitials, getUserAvatarColor } from '../utils'
import { getMemberRoleLabel, getUserStatusLabel } from '../info'
import { tg } from '../theme'
import type { User } from '../types'

interface UserInfoPanelProps {
  user: User
  memberRole?: string
  isSelf: boolean
  canStartChat: boolean
  onStartChat: () => void
}

/** Карточка профиля пользователя (DM). */
export default function UserInfoPanel({
  user,
  memberRole,
  isSelf,
  canStartChat,
  onStartChat,
}: UserInfoPanelProps) {
  const fullName = getFullName(user.first_name, user.last_name) || user.email
  const statusLabel = getUserStatusLabel(user.status)
  const roleLabel = getMemberRoleLabel(memberRole)

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', pt: 2 }}>
      <Box
        sx={{
          width: 96,
          height: 96,
          borderRadius: '50%',
          bgcolor: getUserAvatarColor(user.id),
          color: '#fff',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontSize: '2rem',
          fontWeight: 600,
          overflow: 'hidden',
        }}
      >
        {user.avatar ? (
          <Box component="img" src={user.avatar} alt="" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
        ) : (
          getInitials(user.first_name, user.last_name) || '?'
        )}
      </Box>

      <Typography sx={{ mt: 1.5, fontSize: '1.15rem', fontWeight: 700, color: tg.text, textAlign: 'center' }}>
        {fullName}
        {isSelf ? ' (вы)' : ''}
      </Typography>

      {statusLabel && (
        <Typography sx={{ mt: 0.25, fontSize: '0.85rem', color: tg.accent }}>
          {statusLabel}
        </Typography>
      )}

      <Box sx={{ width: '100%', mt: 2, display: 'flex', flexDirection: 'column', gap: 1 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
          <EmailIcon fontSize="small" sx={{ color: tg.secondaryText }} />
          <Typography sx={{ fontSize: '0.9rem', color: tg.text, wordBreak: 'break-word' }}>
            {user.email}
          </Typography>
        </Box>
        {roleLabel && (
          <Typography sx={{ fontSize: '0.85rem', color: tg.secondaryText }}>
            Роль в чате: {roleLabel}
          </Typography>
        )}
      </Box>

      {!isSelf && canStartChat && (
        <Button
          fullWidth
          variant="contained"
          onClick={onStartChat}
          sx={{
            mt: 3,
            bgcolor: tg.accent,
            textTransform: 'none',
            '&:hover': { bgcolor: tg.accentHover },
          }}
        >
          Написать сообщение
        </Button>
      )}
    </Box>
  )
}
