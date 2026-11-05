import { useState, useEffect, useCallback, type Ref } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  Box,
  Typography,
  Button,
  Chip,
  Divider,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
} from '@mui/material'
import {
  Add as AddIcon,
  Close as CloseIcon,
  Person as PersonIcon,
} from '@mui/icons-material'
import { useDrag, useDrop } from 'react-dnd'
import apiClient from '@/modules/shared/api/apiClient'
import RichTextEditor from '@/modules/shared/components/RichTextEditor'
import LoadingSpinner from '@/modules/shared/components/LoadingSpinner'

interface BoardColumn {
  id: number
  board_id: number
  name: string
  status_id?: number
  order: number
  wip_limit?: number
  created_at: string
  updated_at: string
  status?: {
    id: number
    name: string
    category: string
    color: string
  }
  issues?: KanbanIssue[]
}

interface KanbanIssue {
  id: number
  key: string
  summary: string
  status_id: number
  priority: string
  assignee?: {
    id: number
    first_name: string
    last_name: string
    initials: string
  }
  type?: {
    id: number
    name: string
    color: string
  }
  story_points?: number
}

// Issue Card Component with drag
function IssueCard({ issue, columnId }: { issue: KanbanIssue; columnId: number }) {
  const [{ isDragging }, drag] = useDrag({
    type: 'ISSUE',
    item: { issue, sourceColumn: columnId },
    collect: (monitor) => ({
      isDragging: monitor.isDragging(),
    }),
  })

  const opacity = isDragging ? 0.4 : 1

  return (
    <Box
      ref={drag as unknown as Ref<HTMLDivElement>}
      sx={{
        mb: 1,
        bgcolor: 'background.paper',
        borderRadius: 1,
        boxShadow: isDragging ? 1 : 2,
        border: 1,
        borderColor: 'divider',
        transition: 'box-shadow 0.2s',
        opacity,
        cursor: 'grab',
        '&:hover': {
          boxShadow: 3,
        },
      }}
    >
      {/* Drag Handle */}
      <Box
        sx={{
          display: 'flex',
          alignItems: 'center',
          gap: 0.5,
          p: 0.5,
          px: 1,
          bgcolor: 'action.hover',
          borderRadius: '1px 1px 0 0',
          cursor: 'grab',
        }}
      >
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 0.25 }}>
          {[...Array(3)].map((_, i) => (
            <Box
              key={i}
              sx={{
                width: 12,
                height: 2,
                bgcolor: 'text.secondary',
                borderRadius: 1,
              }}
            />
          ))}
        </Box>
      </Box>
      <Box
        sx={{ p: 1.5, cursor: 'pointer' }}
        onClick={(e) => {
          e.stopPropagation()
          window.location.href = `/issues/${issue.id}`
        }}
      >
        <Typography
          variant="body2"
          sx={{
            fontWeight: 500,
            mb: 1,
            lineHeight: 1.4,
            color: 'primary.main',
            '&:hover': {
              textDecoration: 'underline',
            },
          }}
        >
          {issue.summary}
        </Typography>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Typography variant="caption" color="primary" sx={{ fontWeight: 600 }}>
            {issue.key}
          </Typography>
          {issue.assignee ? (
            <Box
              sx={{
                width: 24,
                height: 24,
                borderRadius: '50%',
                bgcolor: 'primary.main',
                color: 'white',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '0.6rem',
                fontWeight: 600,
              }}
            >
              {issue.assignee.initials}
            </Box>
          ) : (
            <Box
              sx={{
                width: 24,
                height: 24,
                borderRadius: '50%',
                bgcolor: 'grey.300',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '0.6rem',
                color: 'grey.700',
              }}
            >
              ?
            </Box>
          )}
        </Box>
      </Box>
    </Box>
  )
}

// Column Component with drop
function KanbanColumn({ column, issues, moveIssue, index }: { column: BoardColumn; issues: KanbanIssue[]; moveIssue: (issueId: number, toCol: number, index: number) => void; index: number }) {
  const [{ isOver }, drop] = useDrop({
    accept: 'ISSUE',
    drop: (item: { issue: KanbanIssue; sourceColumn: number }) => {
      moveIssue(item.issue.id, column.id, index)
    },
    collect: (monitor) => ({
      isOver: monitor.isOver(),
    }),
  })

  const statusColor = column.status?.color || '#6b7280'

  return (
    <Box
      ref={drop as unknown as Ref<HTMLDivElement>}
      sx={{
        minWidth: 300,
        maxWidth: 300,
        borderRadius: 2,
        display: 'flex',
        flexDirection: 'column',
        maxHeight: 'calc(100vh - 220px)',
        border: 1,
        borderColor: isOver ? statusColor : `${statusColor}40`,
        bgcolor: isOver ? `${statusColor}10` : 'background.paper',
        transition: 'all 0.2s',
        overflow: 'hidden',
      }}
    >
      {/* Column Header */}
      <Box
        sx={{
          p: 2,
          pb: 1.5,
          borderBottom: 2,
          borderColor: statusColor,
          bgcolor: 'background.paper',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          position: 'relative',
          '&::before': {
            content: '""',
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            height: 3,
            bgcolor: statusColor,
          },
        }}
      >
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
          <Box
            sx={{
              width: 10,
              height: 10,
              borderRadius: '50%',
              bgcolor: statusColor,
            }}
          />
          <Typography variant="subtitle2" fontWeight={600}>
            {column.status?.name || column.name}
          </Typography>
        </Box>
        <Chip
          label={issues.length}
          size="small"
          sx={{
            bgcolor: 'rgba(0,0,0,0.04)',
            color: 'text.primary',
            fontWeight: 600,
            fontSize: '0.875rem',
            borderRadius: 1,
          }}
        />
      </Box>

      {/* Issues */}
      <Box sx={{ p: 1, flex: 1, overflowY: 'auto' }}>
        {issues.map((issue) => (
          <IssueCard key={issue.id} issue={issue} columnId={column.id} />
        ))}
      </Box>
    </Box>
  )
}

export default function KanbanBoardPage() {
  const queryClient = useQueryClient()
  const [addColumnOpen, setAddColumnOpen] = useState(false)
  const [newColumnName, setNewColumnName] = useState('')
  const [createIssueOpen, setCreateIssueOpen] = useState(false)
  const [activeBoardId, setActiveBoardId] = useState<number | null>(null)
  const [, setActiveBoardName] = useState<string>('')
  const [assigneeFilter, setAssigneeFilter] = useState<string>('')

  // Fetch all users for assignee filter
  const { data: usersData } = useQuery({
    queryKey: ['all-users'],
    queryFn: async () =>
      apiClient.get('/v1/users/all').then((r) => r.data.data || r.data),
    enabled: !!activeBoardId,
  })
  const users = (usersData as any[] | undefined) || []

  // New issue form state
  const [newIssue, setNewIssue] = useState({
    summary: '',
    description: '',
    priority: 'medium',
  })

  // Fetch all boards
  const { data: boards, isLoading: boardsLoading } = useQuery({
    queryKey: ['all-boards'],
    queryFn: async () => {
      const res = await apiClient.get('/v1/tasksbase/boards')
      return res.data.data || res.data
    },
  })

  // Set first board when loaded
  useEffect(() => {
    if (boards && boards.length > 0 && !activeBoardId) {
      setActiveBoardId(boards[0].id)
      setActiveBoardName(boards[0].name)
    }
  }, [boards, activeBoardId])

  // Fetch board with columns and issues
  const { data: boardData, isLoading: boardLoading } = useQuery({
    queryKey: ['board', activeBoardId],
    queryFn: async () => {
      const [boardRes, columnsRes, issuesRes] = await Promise.all([
        apiClient.get(`/v1/tasksbase/boards/${activeBoardId}`),
        apiClient.get(`/v1/tasksbase/boards/${activeBoardId}/columns`),
        apiClient.get('/v1/tasksbase/issues'),
      ])
      return {
        board: boardRes.data,
        columns: columnsRes.data,
        issues: issuesRes.data.data || issuesRes.data,
      }
    },
    enabled: !!activeBoardId,
  })

  // Build columns with issues (no hardcoded Review column)
  const finalColumns = (boardData?.columns || []).map((col: BoardColumn) => {
    const statusId = col.status_id || col.status?.id
    let filteredIssues = (boardData?.issues || []).filter(
      (iss: KanbanIssue) => iss.status_id === statusId
    )

    // Apply assignee filter
    if (assigneeFilter) {
      filteredIssues = filteredIssues.filter(
        (iss: KanbanIssue) => iss.assignee?.id === Number(assigneeFilter)
      )
    }

    return {
      ...col,
      issues: filteredIssues,
    }
  }) || []

  // Create issue mutation
  const createIssueMutation = useMutation({
    mutationFn: async (data: any) => {
      const projectsRes = await apiClient.get('/v1/tasksbase/projects')
      const projects = projectsRes.data.data || projectsRes.data
      const projectKey = projects[0]?.key || 'TS'

      return apiClient.post('/v1/tasksbase/issues', {
        ...data,
        project_key: projectKey,
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['board', activeBoardId] })
      queryClient.invalidateQueries({ queryKey: ['all-boards'] })
      setCreateIssueOpen(false)
      setNewIssue({
        summary: '',
        description: '',
        priority: 'medium',
      })
    },
  })

  // Move issue mutation
  const moveIssueMutation = useMutation({
    mutationFn: ({ issueId, columnId, order, statusId }: { issueId: number; columnId: number; order: number; statusId?: number }) =>
      apiClient.post(`/v1/tasksbase/issues/${issueId}/move`, statusId
        ? { status_id: statusId }
        : { column_id: columnId, order }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['board', activeBoardId] })
    },
  })

  // Handle issue move
  const handleMoveIssue = useCallback((issueId: number, toColumnId: number, toIndex: number) => {
    const targetColumn = finalColumns.find((col: BoardColumn) => col.id === toColumnId)
    const statusId = targetColumn?.status?.id
    
    moveIssueMutation.mutate({
      issueId,
      columnId: toColumnId,
      order: toIndex,
      statusId,
    })
  }, [moveIssueMutation, finalColumns])

  const handleCreateIssue = () => {
    if (!newIssue.summary.trim()) return

    createIssueMutation.mutate({
      summary: newIssue.summary,
      description: newIssue.description,
      priority: newIssue.priority,
    })
  }

  const handleAddColumn = async () => {
    if (!newColumnName.trim() || !activeBoardId) return

    await apiClient.post(`/v1/tasksbase/boards/${activeBoardId}/columns`, {
      name: newColumnName,
    })

    setNewColumnName('')
    setAddColumnOpen(false)
    queryClient.invalidateQueries({ queryKey: ['board', activeBoardId] })
  }

  if (boardsLoading) {
    return <LoadingSpinner message="Загрузка доски..." />
  }

  if (!boards || boards.length === 0) {
    return (
      <Box>
        <Typography variant="h5" sx={{ mb: 2 }}>Досок пока нет</Typography>
        <Typography color="text.secondary" sx={{ mb: 3 }}>
          Создайте доску для визуализации вашего рабочего процесса.
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => {
            apiClient.get('/v1/tasksbase/projects').then((res) => {
              const projects = res.data.data || res.data
              if (projects.length > 0) {
                apiClient.post(`/v1/tasksbase/projects/${projects[0].key}/boards`, {
                  name: 'Доска задач',
                  type: 'kanban',
                }).then((boardRes) => {
                  setActiveBoardId(boardRes.data.id)
                  setActiveBoardName(boardRes.data.name)
                })
              }
            })
          }}
        >
          Создать доску
        </Button>
      </Box>
    )
  }

  if (boardLoading || !boardData) {
    return <LoadingSpinner message="Загрузка задач..." />
  }

  return (
    <Box>
      {/* Header */}
      <Box
        sx={{
          display: 'flex',
          justifyContent: 'flex-end',
          alignItems: 'center',
          mb: 3,
        }}
      >
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
          <FormControl size="small" sx={{ minWidth: 200 }}>
            <InputLabel>Фильтр по ответственному</InputLabel>
            <Select
              value={assigneeFilter}
              label="Фильтр по ответственному"
              onChange={(e) => setAssigneeFilter(e.target.value)}
              sx={{
                '& .MuiSelect-select': {
                  display: 'flex',
                  alignItems: 'center',
                  gap: 1,
                },
              }}
            >
              <MenuItem value="">
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <PersonIcon sx={{ color: 'text.secondary', fontSize: 18 }} />
                  <Typography variant="body2" color="text.secondary">
                    Все задачи
                  </Typography>
                </Box>
              </MenuItem>
              <MenuItem value="">
                <Typography variant="body2" color="text.secondary">
                  Все задачи
                </Typography>
              </MenuItem>
              {users.map((user: any) => (
                <MenuItem key={user.id} value={user.id}>
                  <Box
                    sx={{
                      width: 28,
                      height: 28,
                      borderRadius: '50%',
                      bgcolor: 'primary.main',
                      color: 'white',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontSize: '0.7rem',
                      fontWeight: 600,
                      mr: 1,
                    }}
                  >
                    {user.initials || `${user.first_name?.[0] || ''}${user.last_name?.[0] || ''}`}
                  </Box>
                  {user.first_name} {user.last_name}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => setCreateIssueOpen(true)}
          >
            Добавить задачу
          </Button>
        </Box>
      </Box>

      <Divider sx={{ mb: 2 }} />

      {/* Active filter indicator */}
      {assigneeFilter && (
        <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 1 }}>
          <Chip
            label={`Фильтр: ${users.find((u: any) => u.id === Number(assigneeFilter))?.first_name} ${users.find((u: any) => u.id === Number(assigneeFilter))?.last_name}`}
            size="small"
            sx={{ bgcolor: 'primary.light', color: 'primary.contrastText' }}
          />
          <Button
            size="small"
            startIcon={<CloseIcon />}
            onClick={() => setAssigneeFilter('')}
          >
            Сбросить
          </Button>
        </Box>
      )}

      {/* Kanban Board */}
      <Box
        sx={{
          display: 'flex',
          gap: 2,
          overflowX: 'auto',
          pb: 2,
          minHeight: 'calc(100vh - 200px)',
        }}
      >
        {finalColumns.map((column: BoardColumn, index: number) => (
          <KanbanColumn
            key={column.id}
            column={column}
            issues={column.issues || []}
            moveIssue={handleMoveIssue}
            index={index}
          />
        ))}
      </Box>

      {/* Add Column Dialog */}
      <Dialog open={addColumnOpen} onClose={() => setAddColumnOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Добавить этап</DialogTitle>
        <DialogContent sx={{ pt: 3 }}>
          <TextField
            autoFocus
            fullWidth
            label="Название этапа"
            value={newColumnName}
            onChange={(e) => setNewColumnName(e.target.value)}
            placeholder="Например: Проверка, Тестирование, QA"
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setAddColumnOpen(false)}>Отмена</Button>
          <Button
            variant="contained"
            onClick={handleAddColumn}
            disabled={!newColumnName.trim()}
          >
            Добавить
          </Button>
        </DialogActions>
      </Dialog>

      {/* Create Issue Dialog */}
      <Dialog
        open={createIssueOpen}
        onClose={() => setCreateIssueOpen(false)}
        maxWidth="md"
        fullWidth
        PaperProps={{ style: { height: '90vh', display: 'flex', flexDirection: 'column' } }}
      >
        <DialogTitle style={{ padding: '24px 24px 8px' }}>
          Новая задача
        </DialogTitle>
        <DialogContent
          dividers
          style={{
            flexGrow: 1,
            overflow: 'hidden',
            padding: '16px 24px',
            display: 'flex',
            flexDirection: 'column',
          }}
        >
          <TextField
            fullWidth
            label="Название"
            placeholder="Кратко опишите задачу"
            value={newIssue.summary}
            onChange={(e) => setNewIssue({ ...newIssue, summary: e.target.value })}
            sx={{ mb: 2 }}
          />
          <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>Описание</Typography>
            <RichTextEditor
              content={newIssue.description}
              onChange={(html) => setNewIssue({ ...newIssue, description: html })}
              placeholder="Описание задачи (необязательно)"
              minHeight={0}
              style={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}
              toolbar
            />
          </Box>
          <FormControl fullWidth sx={{ mt: 2 }}>
            <InputLabel>Приоритет</InputLabel>
            <Select
              value={newIssue.priority}
              label="Приоритет"
              onChange={(e) => setNewIssue({ ...newIssue, priority: e.target.value })}
            >
              <MenuItem value="lowest">Самый низкий</MenuItem>
              <MenuItem value="low">Низкий</MenuItem>
              <MenuItem value="medium">Средний</MenuItem>
              <MenuItem value="high">Высокий</MenuItem>
              <MenuItem value="highest">Самый высокий</MenuItem>
            </Select>
          </FormControl>
        </DialogContent>
        <DialogActions style={{ padding: '8px 24px 16px' }}>
          <Button onClick={() => setCreateIssueOpen(false)}>Отмена</Button>
          <Button
            variant="contained"
            onClick={handleCreateIssue}
            disabled={!newIssue.summary.trim() || createIssueMutation.isPending}
          >
            {createIssueMutation.isPending ? 'Создание...' : 'Создать задачу'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  )
}

// hot-reload-trigger