import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import {
  Box,
  Typography,
  Button,
  Card,
  CardContent,
  Grid,
  Tabs,
  Tab,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  IconButton,
  Tooltip,
  TextField,
  InputAdornment,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
} from '@mui/material'
import {
  Add as AddIcon,
  Search as SearchIcon,
  Edit as EditIcon,
} from '@mui/icons-material'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/modules/shared/api/apiClient'
import type { Project, Issue } from '@/types'

interface TabPanelProps {
  children?: React.ReactNode
  index: number
  value: number
}

function TabPanel(props: TabPanelProps) {
  const { children, value, index, ...other } = props
  return (
    <div role="tabpanel" hidden={value !== index} {...other}>
      {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
    </div>
  )
}

export default function ProjectPage() {
  const { projectKey } = useParams<{ projectKey?: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [tabValue, setTabValue] = useState(0)
  const [searchQuery, setSearchQuery] = useState('')
  const [createProjectOpen, setCreateProjectOpen] = useState(false)
  const [newProject, setNewProject] = useState({ name: '', key: '', type: 'scrum' as const })

  // Fetch projects
  const { data: projectsData } = useQuery({
    queryKey: ['projects'],
    queryFn: async () => apiClient.get('/v1/tasksbase/projects').then((r) => r.data),
  })

  // Fetch current project issues
  const { data: issuesData, isLoading: issuesLoading } = useQuery({
    queryKey: ['issues', projectKey],
    queryFn: async () =>
      apiClient.get(`/v1/tasksbase/issues`, { params: { project_key: projectKey } }).then((r) => r.data),
    enabled: !!projectKey,
  })

  // Create project mutation
  const createProjectMutation = useMutation({
    mutationFn: (data: typeof newProject) => apiClient.post('/v1/tasksbase/projects', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] })
      setCreateProjectOpen(false)
      setNewProject({ name: '', key: '', type: 'scrum' })
    },
  })

  const handleCreateProject = () => {
    createProjectMutation.mutate(newProject)
  }

  const projects = projectsData?.data || []
  const issues = issuesData?.data || []

  // If no project selected and we have projects, use the first one
  const selectedProject = projectKey
    ? projects.find((p: Project) => p.key === projectKey)
    : projects[0]

  return (
    <Box>
      {/* Header */}
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h4" component="h1">
          {selectedProject?.name || 'Projects'}
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => setCreateProjectOpen(true)}
        >
          New Project
        </Button>
      </Box>

      {/* Project Selector */}
      {!projectKey && (
        <Box sx={{ mb: 3 }}>
          <Grid container spacing={2}>
            {projects.map((project: Project) => (
              <Grid item xs={12} sm={6} md={4} lg={3} key={project.id}>
                <Card
                  sx={{
                    cursor: 'pointer',
                    '&:hover': { boxShadow: 3 },
                  }}
                  onClick={() => navigate(`/tasksbase/${project.key}`)}
                >
                  <CardContent>
                    <Typography variant="h6">{project.name}</Typography>
                    <Typography variant="body2" color="text.secondary">
                      {project.key}
                    </Typography>
                    <Chip
                      label={project.type}
                      size="small"
                      sx={{ mt: 1, textTransform: 'capitalize' }}
                    />
                  </CardContent>
                </Card>
              </Grid>
            ))}
          </Grid>
        </Box>
      )}

      {selectedProject && (
        <>
          {/* Tabs */}
          <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 2 }}>
            <Tabs value={tabValue} onChange={(_, v) => setTabValue(v)}>
              <Tab label="Issues" />
              <Tab label="Board" />
              <Tab label="Sprints" />
              <Tab label="Reports" />
              <Tab label="Settings" />
            </Tabs>
          </Box>

          {/* Issues Tab */}
          <TabPanel value={tabValue} index={0}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
              <TextField
                placeholder="Search issues..."
                size="small"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                InputProps={{
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchIcon />
                    </InputAdornment>
                  ),
                }}
                sx={{ width: 300 }}
              />
              <Button
                variant="contained"
                startIcon={<AddIcon />}
                onClick={() => navigate(`/tasksbase/${projectKey}/new-issue`)}
              >
                New Issue
              </Button>
            </Box>

            <TableContainer component={Paper}>
              <Table>
                <TableHead>
                  <TableRow>
                    <TableCell>Key</TableCell>
                    <TableCell>Summary</TableCell>
                    <TableCell>Type</TableCell>
                    <TableCell>Status</TableCell>
                    <TableCell>Priority</TableCell>
                    <TableCell>Assignee</TableCell>
                    <TableCell>Due Date</TableCell>
                    <TableCell>Actions</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {issuesLoading ? (
                    <TableRow>
                      <TableCell colSpan={8} align="center">
                        Loading...
                      </TableCell>
                    </TableRow>
                  ) : issues.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={8} align="center">
                        No issues found. Create your first issue!
                      </TableCell>
                    </TableRow>
                  ) : (
                    issues.map((issue: Issue) => (
                      <TableRow
                        key={issue.id}
                        hover
                        onClick={() => navigate(`/tasksbase/${projectKey}/issues/${issue.id}`)}
                        sx={{ cursor: 'pointer' }}
                      >
                        <TableCell>
                          <Typography variant="body2" color="primary">
                            {issue.key}
                          </Typography>
                        </TableCell>
                        <TableCell>{issue.summary}</TableCell>
                        <TableCell>
                          <Chip
                            label={issue.type?.name || 'Task'}
                            size="small"
                            sx={{
                              bgcolor: issue.type?.color || '#6b7280',
                              color: 'white',
                            }}
                          />
                        </TableCell>
                        <TableCell>
                          <Chip
                            label={issue.status?.name || 'To Do'}
                            size="small"
                            sx={{
                              bgcolor: issue.status?.color || '#6b7280',
                              color: 'white',
                            }}
                          />
                        </TableCell>
                        <TableCell>{issue.priority}</TableCell>
                        <TableCell>
                          {issue.assignee ? `${issue.assignee.first_name} ${issue.assignee.last_name}` : '-'}
                        </TableCell>
                        <TableCell>{issue.due_date || '-'}</TableCell>
                        <TableCell>
                          <Tooltip title="Edit">
                            <IconButton size="small">
                              <EditIcon fontSize="small" />
                            </IconButton>
                          </Tooltip>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          </TabPanel>

          {/* Other Tabs */}
          <TabPanel value={tabValue} index={1}>
            <Typography variant="body1" color="text.secondary">
              Board view coming soon. Create a board to get started.
            </Typography>
          </TabPanel>
          <TabPanel value={tabValue} index={2}>
            <Typography variant="body1" color="text.secondary">
              Sprints view coming soon.
            </Typography>
          </TabPanel>
          <TabPanel value={tabValue} index={3}>
            <Typography variant="body1" color="text.secondary">
              Reports coming soon.
            </Typography>
          </TabPanel>
          <TabPanel value={tabValue} index={4}>
            <Typography variant="body1" color="text.secondary">
              Project settings coming soon.
            </Typography>
          </TabPanel>
        </>
      )}

      {/* Create Project Dialog */}
      <Dialog open={createProjectOpen} onClose={() => setCreateProjectOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Create New Project</DialogTitle>
        <DialogContent sx={{ pt: 3 }}>
          <TextField
            autoFocus
            margin="dense"
            label="Project Name"
            fullWidth
            value={newProject.name}
            onChange={(e) => setNewProject({ ...newProject, name: e.target.value })}
            sx={{ mb: 2 }}
          />
          <TextField
            margin="dense"
            label="Project Key (e.g., PROJ)"
            fullWidth
            value={newProject.key}
            onChange={(e) => setNewProject({ ...newProject, key: e.target.value.toUpperCase() })}
            sx={{ mb: 2 }}
          />
          <FormControl fullWidth>
            <InputLabel>Type</InputLabel>
            <Select
              value={newProject.type}
              label="Type"
              onChange={(e) => setNewProject({ ...newProject, type: e.target.value as any })}
            >
              <MenuItem value="scrum">Scrum</MenuItem>
              <MenuItem value="kanban">Kanban</MenuItem>
              <MenuItem value="basic">Basic</MenuItem>
            </Select>
          </FormControl>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setCreateProjectOpen(false)}>Cancel</Button>
          <Button
            variant="contained"
            onClick={handleCreateProject}
            disabled={!newProject.name || !newProject.key}
          >
            Create
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  )
}
