import { useState } from 'react';
import {
  Box,
  Typography,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  IconButton,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  TextField,
  Chip,
  Alert,
  CircularProgress,
} from '@mui/material';
import {
  Delete as DeleteIcon,
  Add as AddIcon,
  Refresh as RefreshIcon,
} from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiClient from '@/modules/shared/api/apiClient';

interface Chat {
  id: number;
  name: string | null;
  type: 'dm' | 'group' | 'channel';
  created_by: number;
  is_active: boolean;
  messages_count: number;
  members_count: number;
  created_at: string;
}

export default function AdminChatManagement() {
  const queryClient = useQueryClient();
  const [openDialog, setOpenDialog] = useState(false);
  const [newChat, setNewChat] = useState({ name: '', type: 'group' });
  const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const { data: chatsResponse, isLoading } = useQuery({
    queryKey: ['admin-chats'],
    queryFn: async () => {
      const res = await apiClient.get('/v1/chat');
      return res.data.data || res.data;
    },
  });

  const chats = Array.isArray(chatsResponse) ? chatsResponse : (chatsResponse?.data || []);

  const deleteMutation = useMutation({
    mutationFn: (chatId: number) => apiClient.delete(`/v1/chat/${chatId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-chats'] });
      setAlert({ type: 'success', message: 'Чат удалён' });
      setTimeout(() => setAlert(null), 3000);
    },
    onError: () => {
      setAlert({ type: 'error', message: 'Ошибка при удалении чата' });
      setTimeout(() => setAlert(null), 3000);
    },
  });

  const createMutation = useMutation({
    mutationFn: (data: { name: string; type: string }) =>
      apiClient.post('/v1/chat', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-chats'] });
      setOpenDialog(false);
      setNewChat({ name: '', type: 'group' });
      setAlert({ type: 'success', message: 'Чат создан' });
      setTimeout(() => setAlert(null), 3000);
    },
    onError: () => {
      setAlert({ type: 'error', message: 'Ошибка при создании чата' });
      setTimeout(() => setAlert(null), 3000);
    },
  });

  const getTypeLabel = (type: string) => {
    switch (type) {
      case 'dm': return 'Личный';
      case 'group': return 'Группа';
      case 'channel': return 'Канал';
      default: return type;
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'dm': return 'default';
      case 'group': return 'primary';
      case 'channel': return 'secondary';
      default: return 'default';
    }
  };

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5" sx={{ fontWeight: 600 }}>
          Управление чатами
        </Typography>
        <Box sx={{ display: 'flex', gap: 1 }}>
          <IconButton onClick={() => queryClient.invalidateQueries({ queryKey: ['admin-chats'] })}>
            <RefreshIcon />
          </IconButton>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => setOpenDialog(true)}
          >
            Создать чат
          </Button>
        </Box>
      </Box>

      {alert && (
        <Alert severity={alert.type} sx={{ mb: 2 }} onClose={() => setAlert(null)}>
          {alert.message}
        </Alert>
      )}

      {isLoading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}>
          <CircularProgress />
        </Box>
      ) : (
        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>ID</TableCell>
                <TableCell>Название</TableCell>
                <TableCell>Тип</TableCell>
                <TableCell>Сообщений</TableCell>
                <TableCell>Участников</TableCell>
                <TableCell>Создан</TableCell>
                <TableCell align="right">Действия</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {chats.map((chat: Chat) => (
                <TableRow key={chat.id} hover>
                  <TableCell>{chat.id}</TableCell>
                  <TableCell>{chat.name || '(без имени)'}</TableCell>
                  <TableCell>
                    <Chip label={getTypeLabel(chat.type)} color={getTypeColor(chat.type) as any} size="small" />
                  </TableCell>
                  <TableCell>{chat.messages_count || 0}</TableCell>
                  <TableCell>{chat.members_count || 0}</TableCell>
                  <TableCell>{new Date(chat.created_at).toLocaleDateString('ru-RU')}</TableCell>
                  <TableCell align="right">
                    <IconButton
                      size="small"
                      color="error"
                      onClick={() => deleteMutation.mutate(chat.id)}
                      disabled={deleteMutation.isPending}
                    >
                      <DeleteIcon fontSize="small" />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
              {chats.length === 0 && (
                <TableRow>
                  <TableCell colSpan={7} align="center">
                    <Typography variant="body2" color="text.secondary">
                      Чатов пока нет
                    </Typography>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      )}

      {/* Create Chat Dialog */}
      <Dialog open={openDialog} onClose={() => setOpenDialog(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Создать чат</DialogTitle>
        <DialogContent sx={{ pt: 3 }}>
          <TextField
            fullWidth
            label="Название"
            value={newChat.name}
            onChange={(e) => setNewChat({ ...newChat, name: e.target.value })}
            sx={{ mb: 2 }}
          />
          <Box sx={{ mt: 2 }}>
            <Typography variant="body2" sx={{ mb: 1 }}>Тип чата:</Typography>
            <Box sx={{ display: 'flex', gap: 1 }}>
              {['group', 'channel', 'dm'].map((type) => (
                <Chip
                  key={type}
                  label={getTypeLabel(type)}
                  onClick={() => setNewChat({ ...newChat, type })}
                  color={newChat.type === type ? 'primary' : 'default'}
                  variant={newChat.type === type ? 'filled' : 'outlined'}
                />
              ))}
            </Box>
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenDialog(false)}>Отмена</Button>
          <Button
            variant="contained"
            onClick={() => createMutation.mutate(newChat)}
            disabled={!newChat.name.trim() || createMutation.isPending}
          >
            {createMutation.isPending ? 'Создание...' : 'Создать'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
