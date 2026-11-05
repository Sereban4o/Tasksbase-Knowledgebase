import { useState } from 'react'
import {
  Badge,
  Box,
  Menu,
  MenuItem,
  List,
  ListItemText,
  Typography,
  Divider,
  Button,
  IconButton,
} from '@mui/material'
import {
  Notifications as NotificationsIcon,
} from '@mui/icons-material'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/modules/shared/api/apiClient'
import type { Notification } from '@/types'

export default function NotificationPanel() {
  const queryClient = useQueryClient()
  const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null)

  const { data } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () =>
      apiClient.get<Notification[]>('/v1/auth/notifications').then((r) => r.data),
  })

  const markAsRead = useMutation({
    mutationFn: (id: number) => apiClient.patch(`/v1/auth/notifications/${id}/read`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] })
    },
  })

  const unreadCount = data?.filter((n) => !n.read).length ?? 0

  return (
    <>
      <IconButton
        onClick={(e) => setAnchorEl(e.currentTarget)}
        size="small"
        sx={{ color: 'inherit' }}
      >
        <Badge badgeContent={unreadCount} color="error">
          <NotificationsIcon />
        </Badge>
      </IconButton>

      <Menu
        anchorEl={anchorEl}
        open={Boolean(anchorEl)}
        onClose={() => setAnchorEl(null)}
        PaperProps={{ sx: { width: 360, maxHeight: 480 } }}
      >
        <Box sx={{ p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Typography variant="h6">Notifications</Typography>
          <Button size="small" onClick={() => setAnchorEl(null)}>
            Mark all read
          </Button>
        </Box>
        <Divider />
        <List sx={{ p: 0 }}>
          {data?.slice(0, 10).map((notification) => (
            <MenuItem
              key={notification.id}
              onClick={() => markAsRead.mutate(notification.id)}
              sx={{
                bgcolor: notification.read ? 'transparent' : 'action.hover',
              }}
            >
              <ListItemText
                primary={notification.type}
                secondary={notification.data?.summary || 'No message'}
                primaryTypographyProps={{ noWrap: true }}
                secondaryTypographyProps={{ noWrap: true }}
              />
            </MenuItem>
          ))}
          {(!data || data.length === 0) && (
            <MenuItem>
              <Typography color="text.secondary">No notifications</Typography>
            </MenuItem>
          )}
        </List>
      </Menu>
    </>
  )
}
