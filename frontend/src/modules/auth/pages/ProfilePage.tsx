import { useState, useEffect } from 'react'
import {
  Box,
  Paper,
  Typography,
  TextField,
  Button,
  Stack,
  Alert,
  Divider,
  Avatar,
  Grid,
} from '@mui/material'
import { Person as PersonIcon, Save as SaveIcon, Cancel as CancelIcon } from '@mui/icons-material'
import { useAuthContext } from '@/modules/auth/hooks/useAuth'
import apiClient from '@/modules/shared/api/apiClient'

export default function ProfilePage() {
  const { user } = useAuthContext()
  const [editing, setEditing] = useState(false)
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    old_password: '',
    password: '',
    password_confirmation: '',
  })
  const [success, setSuccess] = useState('')
  const [error, setError] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    if (user) {
      setFormData({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        old_password: '',
        password: '',
        password_confirmation: '',
      })
    }
  }, [user])

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }))
  }

  const handleSave = async () => {
    setError('')
    setSuccess('')
    setIsSubmitting(true)

    try {
      const data: any = {
        first_name: formData.first_name,
        last_name: formData.last_name,
        email: formData.email,
      }

      // If password is being changed, require old password
      if (formData.password) {
        if (!formData.old_password) {
          setError('Введите текущий пароль')
          setIsSubmitting(false)
          return
        }
        if (formData.password.length < 8) {
          setError('Пароль должен быть не менее 8 символов')
          setIsSubmitting(false)
          return
        }
        if (formData.password !== formData.password_confirmation) {
          setError('Пароли не совпадают')
          setIsSubmitting(false)
          return
        }
        data.old_password = formData.old_password
        data.password = formData.password
      }

      await apiClient.patch('/v1/auth/profile', data)
      setSuccess('Профиль успешно обновлён')
      setEditing(false)
      
      // Clear password fields
      setFormData(prev => ({
        ...prev,
        old_password: '',
        password: '',
        password_confirmation: '',
      }))

      setTimeout(() => setSuccess(''), 3000)
    } catch (err: any) {
      setError(err.response?.data?.message || 'Ошибка при обновлении профиля')
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleCancel = () => {
    if (user) {
      setFormData({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        old_password: '',
        password: '',
        password_confirmation: '',
      })
    }
    setEditing(false)
    setError('')
  }

  const getInitials = (first?: string, last?: string) => {
    return ((first?.[0] || '') + (last?.[0] || '')).toUpperCase()
  }

  return (
    <Box>
      <Typography variant="h4" sx={{ mb: 3, fontWeight: 600 }}>
        Мой профиль
      </Typography>

      <Paper sx={{ p: 4, maxWidth: 700 }}>
        {/* Profile Header */}
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 4 }}>
          <Avatar
            sx={{
              width: 80,
              height: 80,
              bgcolor: 'primary.main',
              fontSize: '2rem',
              fontWeight: 600,
            }}
          >
            {getInitials(user?.first_name, user?.last_name) || <PersonIcon />}
          </Avatar>
          <Box sx={{ ml: 3 }}>
            <Typography variant="h5" fontWeight={600}>
              {user?.first_name} {user?.last_name}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {user?.role?.name === 'admin' ? 'Администратор' : 'Пользователь'}
            </Typography>
          </Box>
        </Box>

        <Divider sx={{ mb: 4 }} />

        {/* Success/Error Messages */}
        {success && (
          <Alert severity="success" sx={{ mb: 3 }} onClose={() => setSuccess('')}>
            {success}
          </Alert>
        )}
        {error && (
          <Alert severity="error" sx={{ mb: 3 }} onClose={() => setError('')}>
            {error}
          </Alert>
        )}

        {/* Profile Form */}
        <Stack spacing={3}>
          <Grid container spacing={3}>
            <Grid item xs={12} sm={6}>
              <TextField
                fullWidth
                label="Имя"
                value={formData.first_name}
                onChange={(e) => handleInputChange('first_name', e.target.value)}
                disabled={!editing}
                InputProps={{
                  sx: { fontSize: '1rem' },
                }}
              />
            </Grid>
            <Grid item xs={12} sm={6}>
              <TextField
                fullWidth
                label="Фамилия"
                value={formData.last_name}
                onChange={(e) => handleInputChange('last_name', e.target.value)}
                disabled={!editing}
                InputProps={{
                  sx: { fontSize: '1rem' },
                }}
              />
            </Grid>
          </Grid>

          <TextField
            fullWidth
            label="Email"
            type="email"
            value={formData.email}
            onChange={(e) => handleInputChange('email', e.target.value)}
            disabled={!editing}
            InputProps={{
              sx: { fontSize: '1rem' },
            }}
          />

          <Divider sx={{ my: 1 }} />

          {/* Password Section */}
          <Typography variant="subtitle2" color="text.secondary" sx={{ mt: 1 }}>
            Смена пароля (необязательно)
          </Typography>

          {editing && (
            <>
              <TextField
                fullWidth
                label="Текущий пароль"
                type="password"
                value={formData.old_password}
                onChange={(e) => handleInputChange('old_password', e.target.value)}
                autoComplete="current-password"
              />
              <TextField
                fullWidth
                label="Новый пароль"
                type="password"
                value={formData.password}
                onChange={(e) => handleInputChange('password', e.target.value)}
                autoComplete="new-password"
                helperText={formData.password ? `Длина: ${formData.password.length} символов` : 'Минимум 8 символов'}
              />
              <TextField
                fullWidth
                label="Подтвердите новый пароль"
                type="password"
                value={formData.password_confirmation}
                onChange={(e) => handleInputChange('password_confirmation', e.target.value)}
                autoComplete="new-password"
                error={!!(formData.password_confirmation && formData.password !== formData.password_confirmation)}
                helperText={
                  formData.password_confirmation && formData.password !== formData.password_confirmation
                    ? 'Пароли не совпадают'
                    : ''
                }
              />
            </>
          )}

          {/* Action Buttons */}
          <Box sx={{ display: 'flex', gap: 2, mt: 2 }}>
            {!editing ? (
              <Button
                variant="contained"
                startIcon={<PersonIcon />}
                onClick={() => {
                  setEditing(true)
                  setError('')
                }}
              >
                Редактировать
              </Button>
            ) : (
              <>
                <Button
                  variant="contained"
                  startIcon={<SaveIcon />}
                  onClick={handleSave}
                  disabled={isSubmitting}
                >
                  {isSubmitting ? 'Сохранение...' : 'Сохранить'}
                </Button>
                <Button
                  variant="outlined"
                  startIcon={<CancelIcon />}
                  onClick={handleCancel}
                  disabled={isSubmitting}
                >
                  Отмена
                </Button>
              </>
            )}
          </Box>
        </Stack>
      </Paper>
    </Box>
  )
}
