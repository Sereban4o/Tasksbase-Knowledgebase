import { useParams, useNavigate } from 'react-router-dom'
import {
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Chip,
  Divider,
  Button,
  IconButton,
  Tooltip,
  Stack,
  MenuItem,
  FormControl,
  Select,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Alert,
  TextField,
  CircularProgress,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  LinearProgress,
} from '@mui/material'
import LoadingSpinner from '@/modules/shared/components/LoadingSpinner'
import {
  Edit as EditIcon,
  Delete as DeleteIcon,
  AttachFile as AttachFileIcon,
  Link as LinkIcon,
  ArrowBack as ArrowBackIcon,
  Close as CloseIcon,
  InsertDriveFile as FileIcon,
  CloudUpload as CloudUploadIcon,
} from '@mui/icons-material'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/modules/shared/api/apiClient'
import type { Issue, IssueComment } from '@/types'
import RichTextEditor from '@/modules/shared/components/RichTextEditor'
import { useState } from 'react'

function withFileTokens(html: string, token: string): string {
  if (!token) return html
  return html.replace(/(\/api\/v1\/files\/\d+)(?!\?)/g, (_match, url) => `${url}?token=${encodeURIComponent(token)}`)
}

type Priority = 'lowest' | 'low' | 'medium' | 'high' | 'highest'

export default function IssueDetailPage() {
  const { issueId } = useParams<{ issueId?: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('auth_token') || '' : ''
  const [newComment, setNewComment] = useState('')
  const [editData, setEditData] = useState({
    summary: '',
    description: '',
  })
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [editOpen, setEditOpen] = useState(false)
  const [linkOpen, setLinkOpen] = useState(false)
  const [linkIssueId, setLinkIssueId] = useState('')
  const [isUploading, setIsUploading] = useState(false)
  const [error, setError] = useState('')
  const [selectedFiles, setSelectedFiles] = useState<File[]>([])
  const [uploadProgress, setUploadProgress] = useState(0)
  const [attachDialogOpen, setAttachDialogOpen] = useState(false)
  const [imagePreviewOpen, setImagePreviewOpen] = useState(false)
  const [previewImageUrl, setPreviewImageUrl] = useState('')
  const [deletingFileId, setDeletingFileId] = useState<string | null>(null)
  const getFileUrl = (fileId: string): string => {
    const url = `/api/v1/files/${fileId}`
    const authToken = localStorage.getItem('auth_token')
    return authToken ? `${url}?token=${authToken}` : ''
  }

  const stripHtmlTags = (html: string): string => {
    if (!html) return ''
    const tmp = document.createElement('div')
    tmp.innerHTML = html
    return tmp.textContent || tmp.innerText || ''
  }

  const { data: issue, isLoading } = useQuery({
    queryKey: ['issue', issueId],
    queryFn: async () =>
      apiClient.get(`/v1/tasksbase/issues/${issueId}`).then((r) => r.data),
    enabled: !!issueId,
  })

  const { data: comments } = useQuery({
    queryKey: ['issue-comments', issueId],
    queryFn: async () =>
      apiClient.get(`/v1/tasksbase/issues/${issueId}/comments`).then((r) => r.data),
    enabled: !!issueId,
  })

  const { data: statuses } = useQuery({
    queryKey: ['statuses'],
    queryFn: () => apiClient.get('/v1/admin/statuses').then((r) => r.data.filter((s: any) => s.is_used)),
  })

  const { data: priorities } = useQuery({
    queryKey: ['priorities'],
    queryFn: () => apiClient.get('/v1/admin/priorities').then((r) => r.data.filter((p: any) => p.is_used)),
  })



  const updateIssue = useMutation({
    mutationFn: (data: Partial<Issue>) =>
      apiClient.patch(`/v1/tasksbase/issues/${issueId}`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['issue', issueId] })
    },
  })

  const addComment = useMutation({
    mutationFn: (body: string) =>
      apiClient.post(`/v1/tasksbase/issues/${issueId}/comments`, { body }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['issue-comments', issueId] })
    },
  })

  const deleteIssue = useMutation({
    mutationFn: () =>
      apiClient.delete(`/v1/tasksbase/issues/${issueId}`),
    onSuccess: () => {
      navigate('/board')
    },
  })

  const attachFile = useMutation({
    mutationFn: (fileId: string) =>
      apiClient.post(`/v1/tasksbase/issues/${issueId}/attachments`, { file_id: fileId }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['issue', issueId] })
    },
  })

  const linkIssue = useMutation({
    mutationFn: (data: { linked_issue_id: string; link_type?: string }) =>
      apiClient.post(`/v1/tasksbase/issues/${issueId}/links`, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['issue', issueId] })
      setLinkOpen(false)
      setLinkIssueId('')
    },
  })

  const handleStatusChange = (statusId: number) => {
    updateIssue.mutate({ status_id: statusId })
  }

  const handlePriorityChange = (priority: string) => {
    updateIssue.mutate({ priority: priority as Priority })
  }

  const handleEditOpen = () => {
    setEditData({
      summary: issue.summary || '',
      description: issue.description || '',
    })
    setEditOpen(true)
  }

  const handleEditSave = () => {
    updateIssue.mutate(
      { summary: editData.summary, description: editData.description },
      {
        onSuccess: () => {
          setEditOpen(false)
        },
      }
    )
  }

  const handleDelete = () => {
    if (window.confirm('Вы уверены, что хотите удалить эту задачу?')) {
      deleteIssue.mutate()
    }
  }

  const handleFileUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const files = event.target.files
    if (!files || files.length === 0) return

    const newFiles = Array.from(files)
    setSelectedFiles(prev => [...prev, ...newFiles])
    event.target.value = ''
    setAttachDialogOpen(true)
  }

  const handleAttachFiles = async () => {
    if (selectedFiles.length === 0) return

    setIsUploading(true)
    setUploadProgress(0)
    setError('')

    try {
      for (let i = 0; i < selectedFiles.length; i++) {
        const file = selectedFiles[i]
        const formData = new FormData()
        formData.append('file', file)

        await apiClient.post('/v1/files/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || file.size))
            setUploadProgress(Math.round(((i + percentCompleted / 100) / selectedFiles.length) * 100))
          },
        }).then(async (response) => {
          await attachFile.mutateAsync(response.data.id)
        })

        setUploadProgress(Math.round(((i + 1) / selectedFiles.length) * 100))
      }

      setSelectedFiles([])
      setUploadProgress(0)
      setAttachDialogOpen(false)
      queryClient.invalidateQueries({ queryKey: ['issue', issueId] })
    } catch (err: any) {
      setError(err.response?.data?.message || 'Ошибка при загрузке файла')
    } finally {
      setIsUploading(false)
    }
  }

  const handleRemoveFile = (index: number) => {
    setSelectedFiles(prev => prev.filter((_, i) => i !== index))
  }

  const isImageFile = (fileName?: string) => {
    if (!fileName) return false
    const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp']
    return imageExtensions.some(ext => fileName.toLowerCase().endsWith(ext))
  }

  const handleImagePreview = (fileId: string, fileName?: string) => {
    if (isImageFile(fileName)) {
      setPreviewImageUrl(getFileUrl(fileId))
      setImagePreviewOpen(true)
    }
  }

  const handleLinkIssue = () => {
    if (!linkIssueId.trim()) return
    linkIssue.mutate({
      linked_issue_id: linkIssueId.trim(),
      link_type: 'relates',
    })
  }

  const { data: usersData } = useQuery({
    queryKey: ['all-users'],
    queryFn: async () =>
      apiClient.get('/v1/users/all').then((r) => r.data.data || r.data),
  })
  const users = (usersData as any[] | undefined) || []

  const updateAssignee = useMutation({
    mutationFn: (assigneeId: number | null) =>
      apiClient.patch(`/v1/tasksbase/issues/${issueId}`, { assignee_id: assigneeId }),
    onSuccess: (response) => {
      const updatedIssue = response.data
      queryClient.setQueryData(['issue', issueId], (old: any) => {
        if (!old) return old
        return {
          ...old,
          assignee: updatedIssue.assignee,
        }
      })
      // Invalidate board queries to refresh the kanban board
      queryClient.invalidateQueries({ predicate: (query) => query.queryKey[0] === 'board' })
    },
  })

  const handleAssigneeChange = (assigneeId: number | null) => {
    updateAssignee.mutate(assigneeId)
  }

  const deleteFile = useMutation({
    mutationFn: (fileId: string) =>
      apiClient.delete(`/v1/files/${fileId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['issue', issueId] })
      setDeletingFileId(null)
    },
  })

  const handleDeleteFile = (fileId: string) => {
    if (window.confirm('Вы уверены, что хотите удалить этот файл?')) {
      setDeletingFileId(fileId)
      deleteFile.mutate(fileId)
    }
  }

  if (isLoading) return <LoadingSpinner message="Загрузка задачи..." />
  if (!issue) return <Typography>Задача не найдена</Typography>

  return (
    <Box>
      {/* Header */}
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
        <IconButton onClick={() => navigate(-1)} sx={{ mr: 2 }}>
          <ArrowBackIcon />
        </IconButton>
        <Typography variant="h4" component="h1">
          {issue.key} - {issue.summary}
        </Typography>
      </Box>

      <Grid container spacing={3}>
        {/* Main Content */}
        <Grid item xs={12} md={8}>
          {/* Description */}
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Typography variant="h6">Описание</Typography>
                <Button
                  size="small"
                  startIcon={<EditIcon />}
                  onClick={handleEditOpen}
                >
                  Редактировать
                </Button>
              </Box>
              {issue.description ? (
                <Box sx={{ whiteSpace: 'pre-wrap' }} dangerouslySetInnerHTML={{ __html: withFileTokens(issue.description || '', token) }} />
              ) : (
                <Typography variant="body2" color="text.secondary">
                  Нет описания
                </Typography>
              )}
            </CardContent>
          </Card>

          {/* Attachments */}
          {(issue.attachments && issue.attachments.length > 0) && (
            <Card sx={{ mb: 3 }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>Вложения</Typography>
                <Stack spacing={1.5}>
                  {issue.attachments.map((att: any) => {
                    const fileUrl = getFileUrl(att.file_id)
                    const isImage = isImageFile(att.file?.original_name)
                    return (
                      <Box
                        key={att.id}
                        sx={{
                          display: 'flex',
                          alignItems: 'center',
                          gap: 1.5,
                          p: 1.5,
                          borderRadius: 1,
                          bgcolor: 'action.hover',
                        }}
                      >
                        {isImage && fileUrl ? (
                          <IconButton
                            onClick={() => handleImagePreview(att.file_id, att.file?.original_name)}
                            sx={{
                              width: 48,
                              height: 48,
                              p: 0,
                              border: '1px solid',
                              borderColor: 'divider',
                              borderRadius: 1,
                              overflow: 'hidden',
                            }}
                          >
                            <img
                              src={fileUrl}
                              alt={att.file?.original_name}
                              style={{
                                width: '100%',
                                height: '100%',
                                objectFit: 'cover',
                              }}
                              onError={(e) => {
                                (e.target as HTMLImageElement).style.display = 'none'
                              }}
                            />
                          </IconButton>
                        ) : (
                          <AttachFileIcon sx={{ color: 'text.secondary' }} />
                        )}
                        <Button
                          variant="outlined"
                          href={fileUrl}
                          target="_blank"
                          sx={{ justifyContent: 'flex-start', flex: 1 }}
                        >
                          {att.file?.original_name || 'Файл'}
                          <Typography variant="caption" sx={{ ml: 1, color: 'text.secondary' }}>
                            ({(att.file?.size / 1024).toFixed(1)} KB)
                          </Typography>
                        </Button>
                        <Tooltip title="Удалить">
                          <IconButton
                            size="small"
                            color="error"
                            onClick={() => handleDeleteFile(att.file_id)}
                            disabled={deletingFileId === att.file_id}
                          >
                            {deletingFileId === att.file_id ? (
                              <CircularProgress size={20} />
                            ) : (
                              <DeleteIcon fontSize="small" />
                            )}
                          </IconButton>
                        </Tooltip>
                      </Box>
                    )
                  })}
                </Stack>
              </CardContent>
            </Card>
          )}



          {/* Comments */}
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Комментарии ({(comments as IssueComment[] | undefined)?.length || 0})
              </Typography>

              {/* Add Comment */}
              <Box sx={{ mb: 3, flexGrow: 1, display: 'flex', flexDirection: 'column' }}>
                <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>Комментарий</Typography>
                <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}>
                  <RichTextEditor
                    content={newComment}
                    onChange={setNewComment}
                    placeholder="Добавить комментарий"
                    minHeight={0}
                    style={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}
                    toolbar={true}
                  />
                </Box>
                <Button
                  variant="contained"
                  onClick={() => {
                    if (newComment.trim()) {
                      addComment.mutate(newComment)
                      setNewComment('')
                    }
                  }}
                  disabled={addComment.isPending || !newComment.trim()}
                  sx={{ mt: 2, alignSelf: 'flex-end' }}
                >
                  Добавить комментарий
                </Button>
              </Box>

              <Divider sx={{ my: 2 }} />

              {/* Comment List */}
              {(comments as IssueComment[] | undefined)?.map((comment) => (
                <Box key={comment.id} sx={{ mb: 2 }}>
                  <Stack direction="row" alignItems="center" sx={{ mb: 1 }}>
                    <Box
                      sx={{
                        width: 32,
                        height: 32,
                        borderRadius: '50%',
                        bgcolor: 'primary.main',
                        color: 'white',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: '0.75rem',
                        fontWeight: 600,
                      }}
                    >
                      {comment.user?.initials || 'U'}
                    </Box>
                    <Typography variant="body2" sx={{ ml: 1, fontWeight: 500 }}>
                      {comment.user?.full_name || 'Unknown'}
                    </Typography>
                    <Typography variant="caption" color="text.secondary" sx={{ ml: 1 }}>
                      Создана: {new Date(comment.created_at).toLocaleDateString()}
                    </Typography>
                  </Stack>
                  <Typography variant="body2" sx={{ pl: 9, whiteSpace: 'pre-wrap' }}>
                    {stripHtmlTags(comment.body)}
                  </Typography>
                  <Divider sx={{ my: 1 }} />
                </Box>
              ))}
            </CardContent>
          </Card>
        </Grid>

        {/* Sidebar */}
        <Grid item xs={12} md={4}>
          <Card>
            <CardContent>
              <Typography variant="h6" gutterBottom>Детали задачи</Typography>

              <Box sx={{ mt: 2 }}>
                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Тип</Typography>
                <Chip
                  label={issue.type?.name || 'Тип'}
                  size="small"
                  sx={{
                    bgcolor: issue.type?.color || '#6b7280',
                    color: 'white',
                    mb: 2,
                  }}
                />

                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Статус</Typography>
                <FormControl fullWidth size="small" sx={{ mb: 2 }}>
                  <Select
                    value={issue.status_id}
                    onChange={(e) => handleStatusChange(Number(e.target.value))}
                  >
                    {statuses?.map((status: any) => (
                      <MenuItem key={status.id} value={status.id}>{status.name}</MenuItem>
                    ))}
                  </Select>
                </FormControl>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Приоритет</Typography>
                <FormControl fullWidth size="small" sx={{ mb: 2 }}>
                  <Select
                    value={issue.priority}
                    onChange={(e) => handlePriorityChange(e.target.value)}
                  >
                    {priorities?.map((priority: any) => (
                      <MenuItem key={priority.key} value={priority.key}>{priority.name}</MenuItem>
                    ))}
                  </Select>
                </FormControl>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Исполнитель</Typography>
                <FormControl fullWidth size="small" sx={{ mb: 2 }}>
                  <Select
                    value={issue.assignee?.id || ''}
                    onChange={(e) => handleAssigneeChange(e.target.value ? Number(e.target.value) : null)}
                  >
                    <MenuItem value="">Не назначен</MenuItem>
                    {users.map((user: any) => (
                      <MenuItem key={user.id} value={user.id}>
                        {user.first_name} {user.last_name}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Автор</Typography>
                <Typography variant="body2" sx={{ mb: 2 }}>
                  {issue.reporter ? `${issue.reporter.first_name} ${issue.reporter.last_name}` : 'Не назначен'}
                </Typography>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Срок</Typography>
                <Typography variant="body2" sx={{ mb: 2 }}>{issue.due_date || 'Не установлен'}</Typography>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Оценочные баллы</Typography>
                <Typography variant="body2" sx={{ mb: 2 }}>{issue.story_points || '-'}</Typography>

                <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>Создана</Typography>
                <Typography variant="body2" sx={{ mb: 2 }}>
                  {new Date(issue.created_at).toLocaleDateString()}
                </Typography>
              </Box>

              <Divider sx={{ my: 2 }} />

              <Stack direction="row" spacing={1}>
                <Tooltip title="Редактировать">
                  <IconButton size="small" onClick={handleEditOpen}>
                    <EditIcon fontSize="small" />
                  </IconButton>
                </Tooltip>
                <Tooltip title="Удалить">
                  <IconButton size="small" color="error" onClick={() => setDeleteOpen(true)}>
                    <DeleteIcon fontSize="small" />
                  </IconButton>
                </Tooltip>
                <Tooltip title="Прикрепить файл">
                  <Button
                    size="small"
                    startIcon={<AttachFileIcon />}
                    component="label"
                    disabled={isUploading}
                  >
                    {isUploading ? 'Загрузка...' : 'Файлы'}
                    <input
                      type="file"
                      hidden
                      multiple
                      onChange={handleFileUpload}
                    />
                  </Button>
                </Tooltip>
                <Tooltip title="Добавить ссылку">
                  <IconButton size="small" onClick={() => setLinkOpen(true)}>
                    <LinkIcon fontSize="small" />
                  </IconButton>
                </Tooltip>
              </Stack>

              {/* Delete Dialog */}
              <Dialog open={deleteOpen} onClose={() => setDeleteOpen(false)} maxWidth="sm" fullWidth>
                <DialogTitle>Удаление задачи</DialogTitle>
                <DialogContent>
                  <Typography sx={{ mt: 1 }}>
                    Вы уверены, что хотите удалить задачу {issue.key}?
                  </Typography>
                  <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                    Это действие нельзя отменить.
                  </Typography>
                </DialogContent>
                <DialogActions>
                  <Button onClick={() => setDeleteOpen(false)}>Отмена</Button>
                  <Button
                    variant="contained"
                    color="error"
                    onClick={handleDelete}
                    disabled={deleteIssue.isPending}
                  >
                    {deleteIssue.isPending ? 'Удаление...' : 'Удалить'}
                  </Button>
                </DialogActions>
              </Dialog>

              {/* Link Issue Dialog */}
              <Dialog open={linkOpen} onClose={() => setLinkOpen(false)} maxWidth="sm" fullWidth>
                <DialogTitle>Связать с задачей</DialogTitle>
                <DialogContent sx={{ pt: 3 }}>
                  <TextField
                    autoFocus
                    fullWidth
                    label="Ключ задачи (например: TASKBASE-1)"
                    value={linkIssueId}
                    onChange={(e) => setLinkIssueId(e.target.value)}
                    placeholder="TASKBASE-1"
                    sx={{ mb: 1 }}
                  />
                  {error && (
                    <Alert severity="error" sx={{ mt: 1 }} onClose={() => setError('')}>
                      {error}
                    </Alert>
                  )}
                </DialogContent>
                <DialogActions>
                  <Button onClick={() => setLinkOpen(false)}>Отмена</Button>
                  <Button
                    variant="contained"
                    onClick={handleLinkIssue}
                    disabled={linkIssue.isPending || !linkIssueId.trim()}
                  >
                    {linkIssue.isPending ? 'Связывание...' : 'Связать'}
                  </Button>
                </DialogActions>
              </Dialog>

              {/* Attach Files Dialog */}
              <Dialog open={attachDialogOpen} onClose={() => !isUploading && setAttachDialogOpen(false)} maxWidth="sm" fullWidth>
                <DialogTitle>
                  Прикрепить файлы ({selectedFiles.length})
                </DialogTitle>
                <DialogContent sx={{ pt: 3 }}>
                  <List>
                    {selectedFiles.map((file, index) => (
                      <ListItem
                        key={index}
                        secondaryAction={
                          !isUploading && (
                            <IconButton edge="end" size="small" onClick={() => handleRemoveFile(index)}>
                              <CloseIcon />
                            </IconButton>
                          )
                        }
                      >
                        <ListItemIcon>
                          <FileIcon />
                        </ListItemIcon>
                        <ListItemText
                          primary={file.name}
                          secondary={`${(file.size / 1024).toFixed(1)} KB`}
                        />
                      </ListItem>
                    ))}
                  </List>
                  {isUploading && (
                    <Box sx={{ mt: 2, mb: 1 }}>
                      <LinearProgress variant="determinate" value={uploadProgress} />
                      <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>
                        {uploadProgress}%
                      </Typography>
                    </Box>
                  )}
                  {error && (
                    <Alert severity="error" sx={{ mt: 1 }} onClose={() => setError('')}>
                      {error}
                    </Alert>
                  )}
                </DialogContent>
                <DialogActions>
                  <Button onClick={() => { setAttachDialogOpen(false); setSelectedFiles([]); setError(''); }} disabled={isUploading}>
                    Отмена
                  </Button>
                  <Button
                    variant="contained"
                    startIcon={<CloudUploadIcon />}
                    onClick={handleAttachFiles}
                    disabled={isUploading || selectedFiles.length === 0}
                  >
                    {isUploading ? `Загрузка... ${uploadProgress}%` : `Прикрепить ${selectedFiles.length} файл(ов)`}
                  </Button>
                </DialogActions>
              </Dialog>

              {/* Image Preview Dialog */}
              <Dialog
                open={imagePreviewOpen}
                onClose={() => setImagePreviewOpen(false)}
                maxWidth="lg"
                fullWidth
              >
                <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  Просмотр изображения
                  <IconButton
                    onClick={() => setImagePreviewOpen(false)}
                    sx={{ color: 'text.secondary' }}
                  >
                    <CloseIcon />
                  </IconButton>
                </DialogTitle>
                <DialogContent sx={{ display: 'flex', justifyContent: 'center', p: 2, bgcolor: 'black' }}>
                  <img
                    src={previewImageUrl}
                    alt="Preview"
                    style={{
                      maxWidth: '100%',
                      maxHeight: '75vh',
                      objectFit: 'contain',
                      borderRadius: 4,
                    }}
                  />
                </DialogContent>
              </Dialog>

              {/* Edit Issue Dialog */}
              <Dialog
                open={editOpen}
                onClose={() => setEditOpen(false)}
                maxWidth="md"
                fullWidth
                PaperProps={{ style: { height: '90vh', display: 'flex', flexDirection: 'column' } }}
              >
                <DialogTitle style={{ padding: '24px 24px 8px' }}>Редактирование задачи</DialogTitle>
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
                    value={editData.summary}
                    onChange={(e) => setEditData({ ...editData, summary: e.target.value })}
                    sx={{ mb: 2 }}
                  />
                  <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}>
                    <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>Описание</Typography>
                    <RichTextEditor
                      content={editData.description}
                      onChange={(html) => setEditData({ ...editData, description: html })}
                      placeholder="Описание задачи"
                      minHeight={0}
                      style={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}
                    />
                  </Box>
                </DialogContent>
                <DialogActions style={{ padding: '8px 24px 16px' }}>
                  <Button onClick={() => setEditOpen(false)}>Отмена</Button>
                  <Button variant="contained" onClick={handleEditSave} disabled={updateIssue.isPending}>
                    Сохранить
                  </Button>
                </DialogActions>
              </Dialog>
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  )
}
