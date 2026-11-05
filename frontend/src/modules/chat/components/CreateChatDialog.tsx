import { useEffect, useMemo, useState } from 'react'
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
  ToggleButton,
  ToggleButtonGroup,
  Typography,
} from '@mui/material'
import apiClient from '@/modules/shared/api/apiClient'
import { unwrapData } from '@/modules/shared/utils/apiResponse'
import { getFullName, getInitials, getUserAvatarColor } from '../utils'
import { tg } from '../theme'
import type { Chat, User } from '../types'

interface CreateChatDialogProps {
  open: boolean
  users: User[]
  defaultType?: 'group' | 'channel'
  onClose: () => void
  onCreated: (chat: Chat) => void
}

export default function CreateChatDialog({
  open,
  users,
  defaultType = 'channel',
  onClose,
  onCreated,
}: CreateChatDialogProps) {
  const [type, setType] = useState<'group' | 'channel'>(defaultType)
  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [search, setSearch] = useState('')
  const [selected, setSelected] = useState<number[]>([])
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Reset the form each time the dialog is opened.
  useEffect(() => {
    if (open) {
      setType(defaultType)
      setName('')
      setDescription('')
      setSearch('')
      setSelected([])
      setError(null)
      setSubmitting(false)
    }
  }, [open, defaultType])

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
    onClose()
  }

  const handleSubmit = async () => {
    const trimmed = name.trim()
    if (!trimmed) {
      setError('Введите название')
      return
    }

    setSubmitting(true)
    setError(null)
    try {
      const res = await apiClient.post('/v1/chat', {
        type,
        name: trimmed,
        description: description.trim() || null,
        user_ids: selected,
      })
      const chat = unwrapData<Chat>(res)
      if (chat?.id) {
        onCreated(chat)
      } else {
        setError('Не удалось создать')
      }
    } catch (e: any) {
      setError(e?.response?.data?.message || 'Не удалось создать канал')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Dialog open={open} onClose={handleClose} fullWidth maxWidth="xs">
      <DialogTitle>{type === 'channel' ? 'Новый канал' : 'Новая группа'}</DialogTitle>
      <DialogContent>
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5, mt: 0.5 }}>
          <ToggleButtonGroup
            exclusive
            size="small"
            value={type}
            onChange={(_, value) => value && setType(value)}
            fullWidth
          >
            <ToggleButton value="channel">Канал</ToggleButton>
            <ToggleButton value="group">Группа</ToggleButton>
          </ToggleButtonGroup>

          <TextField
            fullWidth
            size="small"
            label={type === 'channel' ? 'Название канала' : 'Название группы'}
            value={name}
            onChange={(e) => setName(e.target.value)}
            autoFocus
            inputProps={{ maxLength: 255 }}
          />

          <TextField
            fullWidth
            size="small"
            label="Описание (необязательно)"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            multiline
            maxRows={2}
          />

          <TextField
            fullWidth
            size="small"
            placeholder="Добавить участников..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />

          <Box sx={{ maxHeight: 240, overflow: 'auto', border: `1px solid ${tg.divider}`, borderRadius: 1 }}>
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
        <Button onClick={handleClose} disabled={submitting}>
          Отмена
        </Button>
        <Button onClick={handleSubmit} variant="contained" disabled={submitting}>
          {submitting ? <CircularProgress size={18} color="inherit" /> : 'Создать'}
        </Button>
      </DialogActions>
    </Dialog>
  )
}
