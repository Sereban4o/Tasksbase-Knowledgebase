import { useMemo, useState } from 'react'
import {
  Alert,
  Avatar,
  Box,
  Button,
  Checkbox,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  TextField,
  Typography,
} from '@mui/material'
import { getFullName, getInitials, getUserAvatarColor } from '../utils'
import { tg } from '../theme'
import type { User } from '../types'

interface AddMembersDialogProps {
  open: boolean
  users: User[]
  onClose: () => void
  onSubmit: (userIds: number[]) => Promise<void>
}

/** Диалог добавления участников в группу/канал. */
export default function AddMembersDialog({ open, users, onClose, onSubmit }: AddMembersDialogProps) {
  const [search, setSearch] = useState('')
  const [selected, setSelected] = useState<number[]>([])
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    if (!q) return users
    return users.filter((u) =>
      `${getFullName(u.first_name, u.last_name)} ${u.email}`.toLowerCase().includes(q),
    )
  }, [users, search])

  const toggle = (id: number) =>
    setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]))

  const handleClose = () => {
    if (submitting) return
    setSearch('')
    setSelected([])
    setError(null)
    onClose()
  }

  const handleSubmit = async () => {
    if (selected.length === 0) return
    setSubmitting(true)
    setError(null)
    try {
      await onSubmit(selected)
      setSearch('')
      setSelected([])
      onClose()
    } catch {
      setError('Не удалось добавить участников')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Dialog open={open} onClose={handleClose} fullWidth maxWidth="xs">
      <DialogTitle>Добавить участников</DialogTitle>
      <DialogContent>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5, mt: 0.5 }}>
          <TextField
            fullWidth
            size="small"
            placeholder="Поиск людей..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            autoFocus
          />
          <Box sx={{ maxHeight: 260, overflow: 'auto', border: `1px solid ${tg.divider}`, borderRadius: 1 }}>
            <List dense disablePadding>
              {filtered.map((u) => (
                <ListItemButton key={u.id} onClick={() => toggle(u.id)}>
                  <ListItemIcon sx={{ minWidth: 40 }}>
                    <Checkbox
                      edge="start"
                      checked={selected.includes(u.id)}
                      tabIndex={-1}
                      disableRipple
                      size="small"
                    />
                  </ListItemIcon>
                  <Avatar
                    src={u.avatar || undefined}
                    sx={{ width: 28, height: 28, bgcolor: getUserAvatarColor(u.id), fontSize: '0.75rem', mr: 1 }}
                  >
                    {getInitials(u.first_name, u.last_name)}
                  </Avatar>
                  <ListItemText
                    primary={getFullName(u.first_name, u.last_name) || u.email}
                    primaryTypographyProps={{ fontSize: '0.85rem' }}
                  />
                </ListItemButton>
              ))}
              {filtered.length === 0 && (
                <Typography sx={{ p: 2, fontSize: '0.85rem', color: tg.secondaryText, textAlign: 'center' }}>
                  Пользователи не найдены
                </Typography>
              )}
            </List>
          </Box>
          {error && <Alert severity="error">{error}</Alert>}
        </Box>
      </DialogContent>
      <DialogActions>
        <Button onClick={handleClose} disabled={submitting} sx={{ textTransform: 'none' }}>
          Отмена
        </Button>
        <Button
          onClick={handleSubmit}
          variant="contained"
          disabled={submitting || selected.length === 0}
          sx={{ textTransform: 'none' }}
        >
          {submitting ? <CircularProgress size={18} color="inherit" /> : 'Добавить'}
        </Button>
      </DialogActions>
    </Dialog>
  )
}
