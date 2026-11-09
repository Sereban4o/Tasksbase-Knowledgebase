import { useMemo, useState } from 'react'
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  TextField,
  Typography,
} from '@mui/material'
import {
  Delete as DeleteIcon,
  Edit as EditIcon,
  Logout as LogoutIcon,
  PersonAdd as PersonAddIcon,
} from '@mui/icons-material'
import { getFullName, getInitials, getUserAvatarColor, GROUP_CHAT_COLOR } from '../utils'
import { getMemberRoleLabel } from '../info'
import { tg } from '../theme'
import AddMembersDialog from './AddMembersDialog'
import type { Chat, User } from '../types'

interface GroupInfoPanelProps {
  chat: Chat
  currentUserId?: number
  canManage: boolean
  users: User[]
  onRename: (name: string) => Promise<void>
  onLeave: () => Promise<void>
  onAddMembers: (userIds: number[]) => Promise<void>
  onDelete: () => Promise<void>
}

function resolveChatAvatar(chat: Chat) {
  if (chat.type === 'channel' && !chat.avatar) {
    return { src: undefined, initials: '#', color: GROUP_CHAT_COLOR }
  }
  if (chat.name) {
    return { src: chat.avatar || undefined, initials: chat.name[0].toUpperCase(), color: GROUP_CHAT_COLOR }
  }
  return { src: undefined, initials: '@', color: GROUP_CHAT_COLOR }
}

/** Карточка группы/канала с участниками и действиями. */
export default function GroupInfoPanel({
  chat,
  currentUserId,
  canManage,
  users,
  onRename,
  onLeave,
  onAddMembers,
  onDelete,
}: GroupInfoPanelProps) {
  const [editingName, setEditingName] = useState(false)
  const [nameDraft, setNameDraft] = useState(chat.name ?? '')
  const [addOpen, setAddOpen] = useState(false)
  const [confirmLeaveOpen, setConfirmLeaveOpen] = useState(false)
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const isDm = chat.type === 'dm'
  const isChannel = chat.type === 'channel'
  const avatar = resolveChatAvatar(chat)

  const subtitle = useMemo(() => {
    if (isChannel) return 'канал'
    if (isDm) return 'личный чат'
    return `Группа · ${chat.members?.length ?? 0} участников`
  }, [chat, isChannel, isDm])

  const availableUsers = useMemo(
    () => users.filter((u) => !chat.members?.some((m) => m.id === u.id)),
    [users, chat],
  )

  const handleSaveName = async () => {
    const trimmed = nameDraft.trim()
    if (!trimmed || trimmed === chat.name) {
      setEditingName(false)
      return
    }
    setSubmitting(true)
    setError(null)
    try {
      await onRename(trimmed)
      setEditingName(false)
    } catch {
      setError('Не удалось изменить название')
    } finally {
      setSubmitting(false)
    }
  }

  const handleLeave = async () => {
    setSubmitting(true)
    setError(null)
    try {
      await onLeave()
    } catch {
      setError('Не удалось покинуть чат')
      setSubmitting(false)
      setConfirmLeaveOpen(false)
    }
  }

  const handleDelete = async () => {
    setSubmitting(true)
    setError(null)
    try {
      await onDelete()
    } catch {
      setError('Не удалось удалить чат')
      setSubmitting(false)
      setConfirmDeleteOpen(false)
    }
  }

  const handleAddMembers = async (userIds: number[]) => {
    await onAddMembers(userIds)
  }

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', pt: 2 }}>
      <Box
        sx={{
          width: 96,
          height: 96,
          borderRadius: '50%',
          bgcolor: avatar.color,
          color: '#fff',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontSize: '2rem',
          fontWeight: 600,
          overflow: 'hidden',
        }}
      >
        {avatar.src ? (
          <Box component="img" src={avatar.src} alt="" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
        ) : (
          avatar.initials
        )}
      </Box>

      {editingName ? (
        <Box sx={{ width: '100%', mt: 1.5, display: 'flex', gap: 0.5 }}>
          <TextField
            fullWidth
            size="small"
            value={nameDraft}
            onChange={(e) => setNameDraft(e.target.value)}
            autoFocus
            inputProps={{ maxLength: 255 }}
          />
          <Button size="small" onClick={handleSaveName} disabled={submitting} sx={{ textTransform: 'none' }}>
            {submitting ? <CircularProgress size={16} /> : 'OK'}
          </Button>
          <Button size="small" onClick={() => { setEditingName(false); setNameDraft(chat.name ?? '') }} sx={{ textTransform: 'none' }}>
            Отмена
          </Button>
        </Box>
      ) : (
        <Typography sx={{ mt: 1.5, fontSize: '1.15rem', fontWeight: 700, color: tg.text, textAlign: 'center' }}>
          {chat.name || 'Личный чат'}
        </Typography>
      )}

      <Typography sx={{ mt: 0.25, fontSize: '0.8rem', color: tg.secondaryText }}>{subtitle}</Typography>

      {chat.description && (
        <Typography sx={{ mt: 1.5, fontSize: '0.9rem', color: tg.text, textAlign: 'center' }}>
          {chat.description}
        </Typography>
      )}

      {error && <Alert severity="error" sx={{ mt: 1.5, width: '100%' }}>{error}</Alert>}

      <Box sx={{ width: '100%', mt: 2, borderTop: `1px solid ${tg.divider}` }}>
        {canManage && !isDm && (
          <ListItemButton onClick={() => setEditingName(true)} sx={{ borderRadius: 1 }} aria-label="Изменить название">
            <ListItemIcon sx={{ minWidth: 40 }}>
              <EditIcon fontSize="small" />
            </ListItemIcon>
            <ListItemText primaryTypographyProps={{ fontSize: '0.9rem' }}>Изменить название</ListItemText>
          </ListItemButton>
        )}
        {canManage && !isDm && (
          <ListItemButton onClick={() => setAddOpen(true)} sx={{ borderRadius: 1 }} aria-label="Добавить участников">
            <ListItemIcon sx={{ minWidth: 40 }}>
              <PersonAddIcon fontSize="small" />
            </ListItemIcon>
            <ListItemText primaryTypographyProps={{ fontSize: '0.9rem' }}>Добавить участников</ListItemText>
          </ListItemButton>
        )}
        {!isDm && (
          <ListItemButton
            onClick={() => setConfirmLeaveOpen(true)}
            sx={{ borderRadius: 1, color: '#d32f2f' }}
            aria-label="Покинуть чат"
          >
            <ListItemIcon sx={{ minWidth: 40, color: '#d32f2f' }}>
              <LogoutIcon fontSize="small" />
            </ListItemIcon>
            <ListItemText primaryTypographyProps={{ fontSize: '0.9rem' }}>
              {isChannel ? 'Покинуть канал' : 'Покинуть группу'}
            </ListItemText>
          </ListItemButton>
        )}
        <ListItemButton
          onClick={() => setConfirmDeleteOpen(true)}
          sx={{ borderRadius: 1, color: '#d32f2f' }}
          aria-label="Удалить чат"
        >
          <ListItemIcon sx={{ minWidth: 40, color: '#d32f2f' }}>
            <DeleteIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText primaryTypographyProps={{ fontSize: '0.9rem' }}>Удалить чат</ListItemText>
        </ListItemButton>
      </Box>

      {!isDm && (
        <Box sx={{ width: '100%', mt: 2 }}>
          <Typography sx={{ fontSize: '0.85rem', fontWeight: 600, color: tg.secondaryText, mb: 1 }}>
            Участники · {chat.members?.length ?? 0}
          </Typography>
          {chat.members?.map((member) => {
            const name = getFullName(member.first_name, member.last_name) || member.email
            return (
              <Box key={member.id} sx={{ display: 'flex', alignItems: 'center', gap: 1, py: 0.5 }}>
                <Box
                  sx={{
                    width: 36,
                    height: 36,
                    borderRadius: '50%',
                    bgcolor: getUserAvatarColor(member.id),
                    color: '#fff',
                    fontSize: '0.8rem',
                    fontWeight: 600,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    overflow: 'hidden',
                    flexShrink: 0,
                  }}
                >
                  {member.avatar ? (
                    <Box component="img" src={member.avatar} alt="" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : (
                    getInitials(member.first_name, member.last_name) || '?'
                  )}
                </Box>
                <Box sx={{ minWidth: 0, flex: 1 }}>
                  <Typography noWrap sx={{ fontSize: '0.9rem', color: tg.text }}>
                    {name}
                    {member.id === currentUserId ? ' (вы)' : ''}
                  </Typography>
                  <Typography sx={{ fontSize: '0.75rem', color: tg.secondaryText }}>
                    {getMemberRoleLabel(member.pivot?.role ?? 'member')}
                  </Typography>
                </Box>
              </Box>
            )
          })}
        </Box>
      )}

      <AddMembersDialog
        open={addOpen}
        users={availableUsers}
        onClose={() => setAddOpen(false)}
        onSubmit={handleAddMembers}
      />

      <Dialog open={confirmLeaveOpen} onClose={() => setConfirmLeaveOpen(false)}>
        <DialogTitle>{isChannel ? 'Покинуть канал?' : 'Покинуть группу?'}</DialogTitle>
        <DialogContent>
          <Typography sx={{ fontSize: '0.9rem' }}>
            Вы больше не будете получать сообщения из этого чата.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setConfirmLeaveOpen(false)} disabled={submitting} sx={{ textTransform: 'none' }}>
            Отмена
          </Button>
          <Button onClick={handleLeave} color="error" disabled={submitting} sx={{ textTransform: 'none' }}>
            {submitting ? <CircularProgress size={18} color="inherit" /> : 'Покинуть'}
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={confirmDeleteOpen} onClose={() => setConfirmDeleteOpen(false)}>
        <DialogTitle>Удалить чат?</DialogTitle>
        <DialogContent>
          <Typography sx={{ fontSize: '0.9rem' }}>
            Чат будет удалён для всех участников. Это действие нельзя отменить.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setConfirmDeleteOpen(false)} disabled={submitting} sx={{ textTransform: 'none' }}>
            Отмена
          </Button>
          <Button onClick={handleDelete} color="error" disabled={submitting} sx={{ textTransform: 'none' }}>
            {submitting ? <CircularProgress size={18} color="inherit" /> : 'Удалить'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  )
}
