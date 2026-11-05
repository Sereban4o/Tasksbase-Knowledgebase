import React, { useState } from 'react';
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
  List,
  ListItem,
  ListItemText,
  ListItemAvatar,
  Avatar,
  Divider,
} from '@mui/material';
import {
  Delete as DeleteIcon,
  Add as AddIcon,
  Refresh as RefreshIcon,
  PersonAdd as PersonAddIcon,
  PersonRemove as PersonRemoveIcon,
} from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiClient from '@/modules/shared/api/apiClient';

interface Group {
  id: number;
  name: string;
  description: string | null;
  members_count: number;
  created_at: string;
}

interface GroupMember {
  id: number;
  email: string;
  first_name: string;
  last_name: string;
  avatar: string | null;
}

export default function AdminGroupManagement() {
  const queryClient = useQueryClient();
  const [openDialog, setOpenDialog] = useState(false);
  const [openMembersDialog, setOpenMembersDialog] = useState(false);
  const [selectedGroup, setSelectedGroup] = useState<Group | null>(null);
  const [newGroup, setNewGroup] = useState({ name: '', description: '' });
  const [addUserId, setAddUserId] = useState('');
  const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const { data: groupsResponse, isLoading } = useQuery({
    queryKey: ['admin-groups'],
    queryFn: async () => {
      const res = await apiClient.get('/v1/chat');
      return res.data.data?.filter((c: any) => c.type === 'group') || [];
    },
  });

  const { data: membersData } = useQuery({
    queryKey: ['group-members', selectedGroup?.id],
    queryFn: async () => {
      if (!selectedGroup) return [];
      const res = await apiClient.get(`/v1/chat/${selectedGroup.id}/members`);
      return res.data.members || [];
    },
    enabled: !!selectedGroup && openMembersDialog,
  });

  const members = Array.isArray(membersData) ? membersData : [];

  const deleteMutation = useMutation({
    mutationFn: (chatId: number) => apiClient.delete(`/v1/chat/${chatId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-groups'] });
      setAlert({ type: 'success', message: 'Группа удалена' });
      setTimeout(() => setAlert(null), 3000);
    },
    onError: () => {
      setAlert({ type: 'error', message: 'Ошибка при удалении группы' });
      setTimeout(() => setAlert(null), 3000);
    },
  });

  const createMutation = useMutation({
    mutationFn: (data: { name: string; description?: string; type: string }) =>
      apiClient.post('/v1/chat', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-groups'] });
      setOpenDialog(false);
      setNewGroup({ name: '', description: '' });
      setAlert({ type: 'success', message: 'Группа создана' });
      setTimeout(() => setAlert(null), 3000);
    },
    onError: () => {
      setAlert({ type: 'error', message: 'Ошибка при создании группы' });
      setTimeout(() => setAlert(null), 3000);
    },
  });

  const addMemberMutation = useMutation({
    mutationFn: ({ groupId, userId }: { groupId: number; userId: number }) =>
      apiClient.post(`/v1/chat/${groupId}/members`, { user_ids: [userId] }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['group-members', selectedGroup?.id] });
      queryClient.invalidateQueries({ queryKey: ['admin-groups'] });
      setAlert({ type: 'success', message: 'Участник добавлен' });
      setTimeout(() => setAlert(null), 3000);
    },
    onError: () => {
      setAlert({ type: 'error', message: 'Ошибка при добавлении участника' });
      setTimeout(() => setAlert(null), 3000);
    },
  });

  const removeMemberMutation = useMutation({
    mutationFn: ({ groupId, userId }: { groupId: number; userId: number }) =>
      apiClient.post(`/v1/chat/${groupId}/members/remove`, { user_id: userId }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['group-members', selectedGroup?.id] });
      queryClient.invalidateQueries({ queryKey: ['admin-groups'] });
      setAlert({ type: 'success', message: 'Участник удалён' });
      setTimeout(() => setAlert(null), 3000);
    },
    onError: () => {
      setAlert({ type: 'error', message: 'Ошибка при удалении участника' });
      setTimeout(() => setAlert(null), 3000);
    },
  });

  const getInitials = (user: any) => {
    return (user.first_name?.[0] || '') + (user.last_name?.[0] || '');
  };

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5" sx={{ fontWeight: 600 }}>
          Управление группами
        </Typography>
        <Box sx={{ display: 'flex', gap: 1 }}>
          <IconButton onClick={() => queryClient.invalidateQueries({ queryKey: ['admin-groups'] })}>
            <RefreshIcon />
          </IconButton>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => setOpenDialog(true)}
          >
            Создать группу
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
                <TableCell>Описание</TableCell>
                <TableCell>Участников</TableCell>
                <TableCell>Создана</TableCell>
                <TableCell align="right">Действия</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {(groupsResponse || []).map((group: Group) => (
                <TableRow key={group.id} hover>
                  <TableCell>{group.id}</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>{group.name}</TableCell>
                  <TableCell>{group.description || '-'}</TableCell>
                  <TableCell>
                    <Chip label={group.members_count || 0} size="small" />
                  </TableCell>
                  <TableCell>{new Date(group.created_at).toLocaleDateString('ru-RU')}</TableCell>
                  <TableCell align="right">
                    <IconButton
                      size="small"
                      onClick={() => {
                        setSelectedGroup(group);
                        setOpenMembersDialog(true);
                      }}
                      sx={{ color: 'primary.main' }}
                    >
                      <PersonAddIcon fontSize="small" />
                    </IconButton>
                    <IconButton
                      size="small"
                      color="error"
                      onClick={() => deleteMutation.mutate(group.id)}
                      disabled={deleteMutation.isPending}
                    >
                      <DeleteIcon fontSize="small" />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
              {(groupsResponse || []).length === 0 && (
                <TableRow>
                  <TableCell colSpan={6} align="center">
                    <Typography variant="body2" color="text.secondary">
                      Групп пока нет
                    </Typography>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      )}

      {/* Create Group Dialog */}
      <Dialog open={openDialog} onClose={() => setOpenDialog(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Создать группу</DialogTitle>
        <DialogContent sx={{ pt: 3 }}>
          <TextField
            fullWidth
            label="Название группы"
            value={newGroup.name}
            onChange={(e) => setNewGroup({ ...newGroup, name: e.target.value })}
            sx={{ mb: 2 }}
          />
          <TextField
            fullWidth
            label="Описание"
            value={newGroup.description}
            onChange={(e) => setNewGroup({ ...newGroup, description: e.target.value })}
            multiline
            rows={3}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenDialog(false)}>Отмена</Button>
          <Button
            variant="contained"
            onClick={() =>
              createMutation.mutate({
                ...newGroup,
                type: 'group',
              })
            }
            disabled={!newGroup.name.trim() || createMutation.isPending}
          >
            {createMutation.isPending ? 'Создание...' : 'Создать группу'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Members Dialog */}
      <Dialog
        open={openMembersDialog}
        onClose={() => setOpenMembersDialog(false)}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>
          Участники группы: {selectedGroup?.name}
        </DialogTitle>
        <DialogContent sx={{ pt: 3 }}>
          <Box sx={{ mb: 2, display: 'flex', gap: 1 }}>
            <TextField
              size="small"
              label="ID пользователя"
              value={addUserId}
              onChange={(e) => setAddUserId(e.target.value)}
              sx={{ minWidth: 150 }}
            />
            <Button
              variant="contained"
              startIcon={<PersonAddIcon />}
              onClick={() => {
                if (addUserId && selectedGroup) {
                  addMemberMutation.mutate({
                    groupId: selectedGroup.id,
                    userId: parseInt(addUserId),
                  });
                  setAddUserId('');
                }
              }}
              disabled={!addUserId || addMemberMutation.isPending}
            >
              Добавить
            </Button>
          </Box>

          <Divider sx={{ my: 2 }} />

          <List>
            {members.map((member: GroupMember) => (
              <React.Fragment key={member.id}>
                <ListItem
                  secondaryAction={
                    <IconButton
                      edge="end"
                      size="small"
                      color="error"
                      onClick={() => {
                        if (selectedGroup) {
                          removeMemberMutation.mutate({
                            groupId: selectedGroup.id,
                            userId: member.id,
                          });
                        }
                      }}
                    >
                      <PersonRemoveIcon fontSize="small" />
                    </IconButton>
                  }
                >
                  <ListItemAvatar>
                    <Avatar sx={{ bgcolor: 'primary.main' }}>
                      {getInitials(member)}
                    </Avatar>
                  </ListItemAvatar>
                  <ListItemText
                    primary={member.first_name + ' ' + member.last_name}
                    secondary={member.email}
                  />
                </ListItem>
                <Divider component="li" />
              </React.Fragment>
            ))}
            {members.length === 0 && (
              <Typography variant="body2" color="text.secondary" align="center" sx={{ py: 2 }}>
                Нет участников
              </Typography>
            )}
          </List>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenMembersDialog(false)}>Закрыть</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
