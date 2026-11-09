import { Box, Typography, Button, Grid, Card, CardContent, CardHeader } from '@mui/material'
import {
  ArrowBack as ArrowBackIcon,
  Chat as ChatIcon,
  People as PeopleIcon,
} from '@mui/icons-material'
import { useNavigate } from 'react-router-dom'
import { useState } from 'react'
import AdminChatManagement from '@/modules/admin/components/AdminChatManagement'
import AdminGroupManagement from '@/modules/admin/components/AdminGroupManagement'

type Section = 'overview' | 'chats' | 'groups'

export default function AdminPanelPage() {
  const navigate = useNavigate()
  const [activeSection, setActiveSection] = useState<Section>('overview')
  
  if (activeSection === 'chats') {
    return (
      <Box>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
          <Button
            startIcon={<ArrowBackIcon />}
            onClick={() => setActiveSection('overview')}
            sx={{ mr: 2 }}
          >
            Назад
          </Button>
          <Typography variant="h4" component="h1">
            Управление чатами
          </Typography>
        </Box>
        <AdminChatManagement />
      </Box>
    )
  }

  if (activeSection === 'groups') {
    return (
      <Box>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
          <Button
            startIcon={<ArrowBackIcon />}
            onClick={() => setActiveSection('overview')}
            sx={{ mr: 2 }}
          >
            Назад
          </Button>
          <Typography variant="h4" component="h1">
            Управление группами
          </Typography>
        </Box>
        <AdminGroupManagement />
      </Box>
    )
  }
  
  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
        <Button
          startIcon={<ArrowBackIcon />}
          onClick={() => navigate('/')}
          sx={{ mr: 2 }}
        >
          На главную
        </Button>
        <Typography variant="h4" component="h1">
          Панель администратора
        </Typography>
      </Box>
      
      <Grid container spacing={3} sx={{ mt: 2 }}>
        <Grid item xs={12} md={6}>
          <Card>
            <CardHeader 
              title="Управление чатами" 
              subtitle="Просмотр и управление всеми чатами системы"
              icon={<ChatIcon />}
            />
            <CardContent>
              <Button
                variant="contained"
                fullWidth
                startIcon={<ChatIcon />}
                onClick={() => setActiveSection('chats')}
              >
                Управление чатами
              </Button>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={6}>
          <Card>
            <CardHeader 
              title="Управление группами" 
              subtitle="Создание и управление группами пользователей"
              icon={<PeopleIcon />}
            />
            <CardContent>
              <Button
                variant="contained"
                fullWidth
                startIcon={<PeopleIcon />}
                onClick={() => setActiveSection('groups')}
              >
                Управление группами
              </Button>
            </CardContent>
          </Card>
        </Grid>
      </Grid>
      
      <Typography variant="body1" sx={{ mb: 3, mt: 3 }}>
        Переход к Laravel Admin Panel (требуется вход в веб-админку)...
      </Typography>
      
      <Button
        variant="contained"
        size="large"
        onClick={() => {
          // Same-origin navigation; the Laravel admin uses a session (no token in URL).
          window.location.href = '/admin'
        }}
      >
        Открыть Admin Panel
      </Button>
    </Box>
  )
}
