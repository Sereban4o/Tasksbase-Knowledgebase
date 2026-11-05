import { useParams, useNavigate } from 'react-router-dom'
import { useRef } from 'react'
import {
  Box,
  Typography,
  Button,
  Chip,
  Stack,
  Divider,
  IconButton,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Tooltip,
  LinearProgress,
  Alert,
} from '@mui/material'
import {
  ArrowBack as ArrowBackIcon,
  Edit as EditIcon,
  AttachFile as AttachFileIcon,
  Delete as DeleteIcon,
  Download as DownloadIcon,
  InsertDriveFile as FileIcon,
} from '@mui/icons-material'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/modules/shared/api/apiClient'
import LoadingSpinner from '@/modules/shared/components/LoadingSpinner'
import type { PageAttachment } from '@/types'

function formatSize(bytes?: number): string {
  if (!bytes || bytes <= 0) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1)
  const value = bytes / Math.pow(1024, i)
  return `${i === 0 ? value : value.toFixed(1)} ${units[i]}`
}

export default function PageView() {
  const { spaceKey, pageId } = useParams<{ spaceKey?: string; pageId?: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)

  const { data: page, isLoading } = useQuery({
    queryKey: ['page', pageId],
    queryFn: async () =>
      apiClient.get(`/v1/knowledgebase/pages/${pageId}`).then((r) => r.data),
    enabled: !!pageId,
  })

  const { data: attachments } = useQuery<PageAttachment[]>({
    queryKey: ['page-attachments', pageId],
    queryFn: async () =>
      apiClient.get(`/v1/knowledgebase/pages/${pageId}/attachments`).then((r) => r.data),
    enabled: !!pageId,
  })

  const uploadMutation = useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData()
      formData.append('file', file)
      const uploaded = await apiClient.post('/v1/files/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return apiClient.post(`/v1/knowledgebase/pages/${pageId}/attachments`, {
        file_id: uploaded.data.id,
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['page-attachments', pageId] })
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (attachmentId: number) =>
      apiClient.delete(`/v1/knowledgebase/pages/${pageId}/attachments/${attachmentId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['page-attachments', pageId] })
    },
  })

  const handlePickFile = () => fileInputRef.current?.click()

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) uploadMutation.mutate(file)
    e.target.value = ''
  }

  if (isLoading) return <LoadingSpinner message="Загрузка статьи..." />
  if (!page) return <Typography>Статья не найдена</Typography>

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
        <IconButton onClick={() => navigate(`/knowledgebase/${spaceKey}`)} sx={{ mr: 2 }}>
          <ArrowBackIcon />
        </IconButton>
        <Box sx={{ flexGrow: 1 }}>
          <Typography variant="h4" component="h1">{page.title}</Typography>
          <Stack direction="row" spacing={1} sx={{ mt: 1 }}>
            <Chip label={`v${page.version}`} size="small" />
            <Chip label={page.status} size="small" color="default" />
            {page.labels?.map((label: string) => (
              <Chip key={label} label={label} size="small" variant="outlined" />
            ))}
          </Stack>
        </Box>
        <Button
          variant="outlined"
          startIcon={<EditIcon />}
          onClick={() => navigate(`/knowledgebase/${spaceKey}/pages/${pageId}/edit`)}
        >
          Edit
        </Button>
      </Box>

      <Divider sx={{ mb: 3 }} />

      {/* Page Content */}
      <Box
        sx={{
          maxWidth: 900,
          '& .tiptap': { maxHeight: '70vh', overflowY: 'auto' },
          '& h1': { fontSize: '2rem', fontWeight: 700, margin: '1rem 0' },
          '& h2': { fontSize: '1.5rem', fontWeight: 600, margin: '0.8rem 0' },
          '& h3': { fontSize: '1.25rem', fontWeight: 500, margin: '0.6rem 0' },
          '& p': { margin: '0.5rem 0', lineHeight: 1.8 },
          '& ul, & ol': { paddingLeft: 2, margin: '0.5rem 0' },
          '& blockquote': {
            borderLeft: '4px solid #dadce0',
            paddingLeft: 2,
            margin: '0.5rem 0',
            color: 'text.secondary',
          },
          '& pre': {
            bgcolor: '#f5f5f5',
            padding: 2,
            borderRadius: 1,
            fontFamily: 'monospace',
            margin: '0.5rem 0',
          },
          '& a': {
            color: 'primary.main',
            textDecoration: 'none',
            '&:hover': {
              textDecoration: 'underline',
            },
          },
        }}
      >
        <Box
          dangerouslySetInnerHTML={{ __html: page.body || 'No content yet.' }}
        />
      </Box>

      {/* Attachments */}
      <Box sx={{ maxWidth: 900, mt: 4 }}>
        <Stack direction="row" alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
          <Typography variant="h6">
            Вложения{attachments && attachments.length > 0 ? ` (${attachments.length})` : ''}
          </Typography>
          <Button
            variant="outlined"
            size="small"
            startIcon={<AttachFileIcon />}
            onClick={handlePickFile}
            disabled={uploadMutation.isPending}
          >
            Прикрепить файл
          </Button>
          <input
            ref={fileInputRef}
            type="file"
            hidden
            onChange={handleFileChange}
          />
        </Stack>

        {uploadMutation.isPending && <LinearProgress sx={{ mb: 1 }} />}
        {uploadMutation.isError && (
          <Alert severity="error" sx={{ mb: 1 }}>
            Не удалось загрузить файл
          </Alert>
        )}

        {!attachments || attachments.length === 0 ? (
          <Typography variant="body2" color="text.secondary">
            Пока нет вложений. Прикрепите файл к статье.
          </Typography>
        ) : (
          <List disablePadding>
            {attachments.map((attachment) => (
              <ListItem
                key={attachment.id}
                divider
                secondaryAction={
                  <Stack direction="row" spacing={0.5}>
                    <Tooltip title="Скачать">
                      <IconButton
                        component="a"
                        href={`/api/v1/files/${attachment.file_id}/download`}
                        size="small"
                      >
                        <DownloadIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                    <Tooltip title="Удалить">
                      <IconButton
                        size="small"
                        color="error"
                        onClick={() => deleteMutation.mutate(attachment.id)}
                        disabled={deleteMutation.isPending}
                      >
                        <DeleteIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                  </Stack>
                }
              >
                <ListItemIcon sx={{ minWidth: 36 }}>
                  <FileIcon fontSize="small" />
                </ListItemIcon>
                <ListItemText
                  primary={attachment.file?.original_name || 'Файл'}
                  secondary={[
                    formatSize(attachment.file?.size),
                    attachment.uploader?.full_name,
                  ]
                    .filter(Boolean)
                    .join(' · ')}
                />
              </ListItem>
            ))}
          </List>
        )}
      </Box>

      <Divider sx={{ mt: 4, mb: 2 }} />

      {/* Page Info */}
      <Box sx={{ maxWidth: 900 }}>
        <Typography variant="body2" color="text.secondary">
          Created by {page.author?.full_name || 'Unknown'} on {new Date(page.created_at).toLocaleDateString()}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Last edited by {page.lastEditor?.full_name || 'Unknown'} on {new Date(page.updated_at).toLocaleDateString()}
        </Typography>
      </Box>
    </Box>
  )
}
