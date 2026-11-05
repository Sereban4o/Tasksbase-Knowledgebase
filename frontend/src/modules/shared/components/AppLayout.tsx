import { useState, useEffect } from 'react'
import { Outlet, useNavigate, useLocation } from 'react-router-dom'
import {
  Box,
  Drawer,
  AppBar,
  Toolbar,
  Typography,
  IconButton,
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Divider,
  Avatar,
  Menu,
  MenuItem,
  Tooltip,
  useTheme,
  useMediaQuery,
  CircularProgress,
} from '@mui/material'
import {
  Menu as MenuIcon,
  Close as CloseIcon,
  Dashboard as DashboardIcon,
  Description as DescriptionIcon,
  ExitToApp as LogoutIcon,
  Person as PersonIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
} from '@mui/icons-material'
import { useAuthContext } from '@/modules/auth/hooks/useAuth'
import { useAuth } from '@/modules/auth/store/authStore'
import ChatWidget from '@/modules/chat/components/ChatWidget'

const drawerWidth = 260
const collapsedDrawerWidth = 56
const chatPanelWidth = 400

interface MenuItem {
  text: string
  icon: React.ReactElement
  path: string
}

const tasksbaseMenuItems: MenuItem[] = [
  { text: 'Канбан-доска', icon: <DashboardIcon />, path: '/' },
]

const knowledgebaseMenuItems: MenuItem[] = [
  { text: 'Пространства', icon: <DescriptionIcon />, path: '/knowledgebase' },
]

function MenuSection({
  items,
  location,
  navigate,
  isMobile,
  sidebarOpen,
}: {
  items: MenuItem[]
  location: ReturnType<typeof useLocation>
  navigate: ReturnType<typeof useNavigate>
  isMobile: boolean
  sidebarOpen: boolean
}) {
  if (!items.length) return null

  return (
    <List sx={{ px: 1 }}>
      {items.map((item) => {
        const isActive = item.path === '/'
          ? location.pathname === item.path
          : location.pathname.startsWith(item.path)

        return (
          <Tooltip
            key={item.text}
            title={item.text}
            placement="right"
            disableHoverListener={sidebarOpen || isMobile}
            arrow
          >
            <ListItem disablePadding sx={{ display: 'block', mb: 0.5 }}>
              <ListItemButton
                selected={isActive}
                onClick={() => {
                  navigate(item.path)
                  if (isMobile) setMobileOpen(false)
                }}
                sx={{
                  borderRadius: 10,
                  justifyContent: sidebarOpen && !isMobile ? 'initial' : 'center',
                  px: sidebarOpen && !isMobile ? 2.5 : 1,
                  minHeight: 44,
                  color: '#ffffff',
                }}
              >
                <ListItemIcon sx={{
                  minWidth: 40,
                  color: isActive ? '#ffffff' : 'rgba(255, 255, 255, 0.7)',
                  justifyContent: sidebarOpen && !isMobile ? 'initial' : 'center',
                  marginRight: 0,
                }}>
                  {item.icon}
                </ListItemIcon>
                <ListItemText
                  primary={item.text}
                  sx={{
                    color: '#ffffff',
                    ...(sidebarOpen && !isMobile ? {} : { display: 'none' }),
                    '& .MuiListItemText-primary': {
                      fontWeight: 500,
                    },
                  }}
                />
              </ListItemButton>
            </ListItem>
          </Tooltip>
        )
      })}
    </List>
  )
}

let setMobileOpen: (open: boolean) => void

export default function AppLayout() {
  const theme = useTheme()
  const navigate = useNavigate()
  const location = useLocation()
  const { user, logout } = useAuthContext()
  const { loading } = useAuth()
  const [mobileOpen, setMobileOpenState] = useState(false)
  const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null)
  const [sidebarOpen, setSidebarOpen] = useState(() => {
    const saved = localStorage.getItem('sidebarOpen')
    return saved === null ? true : JSON.parse(saved)
  })
  const isMobile = useMediaQuery(theme.breakpoints.down('md'))

  // Store setter for use in MenuSection
  setMobileOpen = setMobileOpenState

  // Save sidebar state to localStorage
  useEffect(() => {
    localStorage.setItem('sidebarOpen', JSON.stringify(sidebarOpen))
  }, [sidebarOpen])

  const [chatOpen, setChatOpen] = useState(() => {
    const saved = localStorage.getItem('chatWidgetOpen')
    return saved === null ? true : saved === 'true'
  })

  // Persist chat panel state across navigation / reloads
  useEffect(() => {
    localStorage.setItem('chatWidgetOpen', String(chatOpen))
  }, [chatOpen])

  const [chatMinimized, setChatMinimized] = useState(() => {
    return localStorage.getItem('chatWidgetMinimized') === 'true'
  })

  useEffect(() => {
    localStorage.setItem('chatWidgetMinimized', String(chatMinimized))
  }, [chatMinimized])

  const handleDrawerToggle = () => {
    setMobileOpenState(!mobileOpen)
  }

  const handleSidebarToggle = () => {
    setSidebarOpen(!sidebarOpen)
  }

  const handleProfileClick = (event: React.MouseEvent<HTMLElement>) => {
    setAnchorEl(event.currentTarget)
  }

  const handleProfileClose = () => {
    setAnchorEl(null)
  }

  const handleLogout = async () => {
    handleProfileClose()
    await logout()
    navigate('/login')
  }

  const drawer = (
    <Box>
      <Toolbar
        sx={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          px: [1],
          py: 2,
          borderBottom: 1,
          borderColor: 'rgba(255, 255, 255, 0.1)',
          minHeight: '64px !important',
        }}
      >
        {(isMobile || sidebarOpen) && (
          <Typography 
            variant="h6" 
            component="div" 
            sx={{ 
              fontWeight: 700, 
              color: '#ffffff', 
              flexGrow: 1,
              fontSize: '1.1rem',
              letterSpacing: '-0.01em',
            }}
          >

          </Typography>
        )}
        {isMobile && (
          <IconButton onClick={handleDrawerToggle} sx={{ ml: 'auto', color: '#ffffff' }}>
            <CloseIcon />
          </IconButton>
        )}
        {!isMobile && sidebarOpen && (
          <Tooltip title="Свернуть меню" arrow placement="right">
            <IconButton onClick={handleSidebarToggle} sx={{ ml: 'auto', color: 'rgba(255, 255, 255, 0.7)' }}>
              <ChevronLeftIcon />
            </IconButton>
          </Tooltip>
        )}
        {!isMobile && !sidebarOpen && (
          <Tooltip title="Развернуть меню" arrow placement="right">
            <IconButton onClick={handleSidebarToggle} sx={{ ml: 'auto', color: 'rgba(255, 255, 255, 0.7)' }}>
              <ChevronRightIcon />
            </IconButton>
          </Tooltip>
        )}
      </Toolbar>
      {/* Tasksbase Section */}
      <MenuSection
        items={tasksbaseMenuItems}
        location={location}
        navigate={navigate}
        isMobile={isMobile}
        sidebarOpen={sidebarOpen}
      />

      {/* Knowledgebase Section */}
      <MenuSection
        items={knowledgebaseMenuItems}
        location={location}
        navigate={navigate}
        isMobile={isMobile}
        sidebarOpen={sidebarOpen}
      />

      <Box sx={{ flexGrow: 1 }} />

      <Divider sx={{ borderColor: 'rgba(255, 255, 255, 0.1)' }} />
      <List sx={{ px: 1, pb: 2 }}>
        <Tooltip title="Выйти" placement="right" disableHoverListener={sidebarOpen || isMobile} arrow>
          <ListItem disablePadding>
            <ListItemButton 
              onClick={handleLogout} 
              sx={{ 
                mx: 'auto',
                borderRadius: 10,
                justifyContent: 'center',
                px: sidebarOpen && !isMobile ? 2.5 : 1,
                minHeight: 44,
                color: 'rgba(255, 255, 255, 0.7)',
                width: sidebarOpen && !isMobile ? 'auto' : 40,
                '&:hover': {
                  backgroundColor: 'rgba(255, 86, 48, 0.15)',
                  color: '#FF5630',
                },
              }}
            >
              <ListItemIcon sx={{
                minWidth: 40,
                justifyContent: 'center',
                marginRight: 0,
                color: 'inherit',
              }}>
                <LogoutIcon />
              </ListItemIcon>
              <ListItemText 
                primary="Выход" 
                sx={{ 
                  display: sidebarOpen && !isMobile ? 'block' : 'none',
                  '& .MuiListItemText-primary': {
                    color: 'inherit',
                    fontWeight: 500,
                  },
                }} 
              />
            </ListItemButton>
          </ListItem>
        </Tooltip>
      </List>
    </Box>
  )

  return (
    <Box sx={{ display: 'flex' }}>
      {loading && (
        <Box sx={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999, bgcolor: 'background.default' }}>
          <CircularProgress />
        </Box>
      )}
      {/* AppBar */}
      <AppBar
        position="fixed"
        sx={{
          width: {
            md: sidebarOpen ? `calc(100% - ${drawerWidth}px)` : `calc(100% - ${collapsedDrawerWidth}px)`
          },
          ml: {
            md: sidebarOpen ? `${drawerWidth}px` : `${collapsedDrawerWidth}px`
          },
          bgcolor: 'background.paper',
          color: 'text.primary',
          boxShadow: 1,
          transition: theme.transitions.create(['width', 'margin'], {
            easing: theme.transitions.easing.easeOut,
            duration: theme.transitions.duration.enteringScreen,
          }),
        }}
      >
        <Toolbar>
          <IconButton
            color="inherit"
            edge="start"
            onClick={handleDrawerToggle}
            sx={{ mr: 2, display: { md: 'none' } }}
          >
            <MenuIcon />
          </IconButton>
          <Typography variant="h6" noWrap component="div" sx={{ flexGrow: 1 }}>
            {tasksbaseMenuItems.find((i) => location.pathname === i.path)?.text ||
            'Канбан-доска'}
          </Typography>

          {/* User Menu */}
          <Tooltip title={user?.full_name || user?.email}>
            <IconButton onClick={handleProfileClick} sx={{ p: 0 }}>
              <Avatar
                sx={{
                  width: 32,
                  height: 32,
                  bgcolor: 'primary.main',
                  color: 'white',
                  fontSize: '0.875rem',
                }}
              >
                {user?.initials || <PersonIcon />}
              </Avatar>
            </IconButton>
          </Tooltip>
          <Menu
            anchorEl={anchorEl}
            open={Boolean(anchorEl)}
            onClose={handleProfileClose}
            anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            transformOrigin={{ vertical: 'top', horizontal: 'right' }}
          >
            <MenuItem onClick={() => { handleProfileClose(); navigate('/profile') }}>
              <ListItemIcon><PersonIcon fontSize="small" /></ListItemIcon>
              Профиль
            </MenuItem>
            <MenuItem onClick={handleLogout}>
              <ListItemIcon><LogoutIcon fontSize="small" /></ListItemIcon>
              Выход
            </MenuItem>
          </Menu>
        </Toolbar>
      </AppBar>

      {/* Drawer */}
      <Drawer
        variant="permanent"
        sx={{
          display: { xs: 'none', md: 'block' },
          width: sidebarOpen ? drawerWidth : collapsedDrawerWidth,
          flexShrink: 0,
          whiteSpace: 'nowrap',
          boxSizing: 'border-box',
          '& .MuiDrawer-paper': {
            width: sidebarOpen ? drawerWidth : collapsedDrawerWidth,
            boxSizing: 'border-box',
            border: 'none',
            background: theme.palette.background.sidebar,
            color: '#ffffff',
            transition: theme.transitions.create('width', {
              easing: theme.transitions.easing.easeOut,
              duration: theme.transitions.duration.enteringScreen,
            }),
          },
        }}
        open
      >
        {drawer}
      </Drawer>

      {/* Main content */}
      <Box
        component="main"
        sx={{
          flexGrow: 1,
          boxSizing: 'border-box',
          pt: 3,
          pb: 3,
          pl: 3,
          pr: { xs: 3, md: chatOpen && !chatMinimized ? `${chatPanelWidth}px` : 3 },
          width: {
            md: sidebarOpen ? `calc(100% - ${drawerWidth}px)` : `calc(100% - ${collapsedDrawerWidth}px)`
          },
          mt: 8,
          bgcolor: theme.palette.background.default,
          minHeight: 'calc(100vh - 64px)',
          transition: theme.transitions.create('width', {
            easing: theme.transitions.easing.easeOut,
            duration: theme.transitions.duration.enteringScreen,
          }),
        }}
      >
        <Outlet />
        <ChatWidget
          open={chatOpen}
          minimized={chatMinimized}
          onOpenChange={setChatOpen}
          onMinimizedChange={setChatMinimized}
        />
      </Box>
    </Box>
  )
}
