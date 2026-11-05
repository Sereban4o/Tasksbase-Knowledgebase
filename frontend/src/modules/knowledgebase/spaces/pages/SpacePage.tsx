// CACHE_BUST_20240115_abc123xyz
import { useState, useEffect } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import {
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Button,
  TextField,
  Chip,
  Stack,
  CircularProgress,
  Backdrop,
  List,
  ListItemButton,
  IconButton,
} from '@mui/material'
import { Add as AddIcon, Close as CloseIcon, Edit as EditIcon, Delete as DeleteIcon } from '@mui/icons-material'
import apiClient from '@/modules/shared/api/apiClient'
import type { Space, Page } from '@/types'
import RichTextEditor from '@/modules/shared/components/RichTextEditor'

function getSpaceKey(path: string): string | undefined {
  const parts = path.split('/')
  if (parts.length >= 3 && parts[1] === 'knowledgebase' && parts[2] !== 'pages') {
    return parts[2]
  }
  return undefined
}

export default function SpacePage() {
  const location = useLocation()
  const navigate = useNavigate()
  const spaceKey = getSpaceKey(location.pathname)

  const [createPageOpen, setCreatePageOpen] = useState(false)
  const [editorContent, setEditorContent] = useState('')
  const [labels, setLabels] = useState<string[]>([])
  const [labelInput, setLabelInput] = useState('')
  const [spaces, setSpaces] = useState<Space[]>([])
  const [pages, setPages] = useState<Page[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    setLoading(true)
    let cancelled = false
    setLoading(true)
    Promise.all([
      apiClient.get('/v1/knowledgebase/spaces').then((r) => {
        const responseData = r.data
        if (responseData && typeof responseData === 'object' && 'data' in responseData) {
          return Array.isArray(responseData.data) ? responseData.data : []
        }
        return Array.isArray(responseData) ? responseData : []
      }),
      spaceKey
        ? apiClient.get(`/v1/knowledgebase/spaces/${spaceKey}/pages`).then((r) => {
            const responseData = r.data
            const pages = Array.isArray(responseData) ? responseData : (responseData?.data || [])
            return pages
          })
        : Promise.resolve([]),
    ]).then(([spacesRes, pagesRes]) => {
      if (!cancelled) {
        setSpaces(spacesRes as Space[])
        setPages(pagesRes as Page[])
        setLoading(false)
      }
    }).catch((err) => {
      console.error('[SpacePage] Error loading data:', err)
      if (!cancelled) setLoading(false)
    })
    return () => { cancelled = true }
  }, [spaceKey])

  const selectedSpace = spaceKey ? spaces.find((s: Space) => s.key === spaceKey) : null
  const isSpaceView = !!spaceKey && !!selectedSpace

  const handleCreatePage = async () => {
    if (!editorContent.trim() || !spaceKey) return
    try {
      const temp = document.createElement('div')
      temp.innerHTML = editorContent
      const text = temp.textContent || temp.innerText || ''
      const title = text.split('\n')[0].trim() || 'Без названия'
      const response = await apiClient.post(`/v1/knowledgebase/spaces/${spaceKey}/pages`, {
        title,
        body: editorContent,
        labels,
      })
      // Add new page to list
      setPages([...pages, response.data])
      setCreatePageOpen(false)
      setEditorContent('')
      setLabels([])
      setLabelInput('')
    } catch (e: any) {
      console.error('Create page error:', e)
      if (e.response) {
        console.error('Response data:', e.response.data)
        console.error('Response status:', e.response.status)
      }
    }
  }

  const addLabel = () => {
    const trimmed = labelInput.trim().toLowerCase()
    if (trimmed && !labels.includes(trimmed)) {
      setLabels([...labels, trimmed])
      setLabelInput('')
    }
  }

  const removeLabel = (label: string) => {
    setLabels(labels.filter((l) => l !== label))
  }

  if (loading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
        <CircularProgress />
      </Box>
    )
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h4" component="h1">
          {selectedSpace?.name || 'Пространства'}
        </Typography>
        <Box sx={{ display: 'flex', gap: 1 }}>
          {selectedSpace && (
            <Button
              variant="contained"
              startIcon={<AddIcon />}
              onClick={() => setCreatePageOpen(true)}
            >
              Добавить статью
            </Button>
          )}
        </Box>
      </Box>

      {!isSpaceView && (
        <Grid container spacing={2}>
          {spaces.map((space: Space) => (
            <Grid item xs={12} sm={6} md={4} key={space.id}>
              <Card
                sx={{ cursor: 'pointer', '&:hover': { boxShadow: 3 } }}
                onClick={() => window.location.href = `/knowledgebase/${space.key}`}
              >
                <CardContent>
                  <Typography variant="h6">{space.name}</Typography>
                  <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                    {space.key}
                  </Typography>
                  <Chip label={space.type} size="small" sx={{ textTransform: 'capitalize' }} />
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>
      )}

      {isSpaceView && (
        <Box>
          <Typography variant="h6" sx={{ mb: 2 }}>Статьи в {selectedSpace.name}</Typography>
          {pages.length === 0 ? (
            <Box sx={{ textAlign: 'center', py: 4 }}>
              <Typography variant="body1" color="text.secondary" sx={{ mb: 2 }}>
                Статей пока нет
              </Typography>
              <Button
                variant="contained"
                startIcon={<AddIcon />}
                onClick={() => setCreatePageOpen(true)}
              >
                Добавить первую статью
              </Button>
            </Box>
          ) : (
            <Card>
              <List>
                {pages.map((page: Page) => (
                  <ListItemButton
                    key={page.id}
                    onClick={() => navigate(`/knowledgebase/${spaceKey}/pages/${page.id}`)}
                    sx={{
                      '&:hover': { bgcolor: 'action.hover' },
                      flexDirection: 'column',
                      alignItems: 'flex-start',
                    }}
                  >
                    <Box sx={{ width: '100%' }}>
                      <Typography variant="subtitle1" sx={{ fontWeight: 500, mb: 0.5 }}>
                        {page.title}
                      </Typography>
                      <Stack direction="row" spacing={1} sx={{ mb: 0.5 }}>
                        <Chip label={`v${page.version}`} size="small" variant="outlined" sx={{ height: 20, fontSize: '0.7rem' }} />
                        <Chip label={page.status} size="small" sx={{ height: 20, fontSize: '0.7rem', bgcolor: page.status === 'published' ? 'success.light' : 'warning.light', color: page.status === 'published' ? 'success.contrastText' : 'warning.contrastText' }} />
                      </Stack>
                      <Box sx={{ display: 'flex', gap: 0.5, flexWrap: 'wrap', mb: 0.5 }}>
                        {page.labels?.map((label) => <Chip key={label} label={label} size="small" />)}
                      </Box>
                      <Box sx={{ display: 'flex', gap: 0.5 }}>
                        <IconButton
                          size="small"
                          onClick={(e) => {
                            e.stopPropagation()
                            navigate(`/knowledgebase/${spaceKey}/pages/${page.id}/edit`)
                          }}
                        >
                          <EditIcon fontSize="small" />
                        </IconButton>
                        <IconButton
                          size="small"
                          onClick={(e) => {
                            e.stopPropagation()
                            if (window.confirm('Вы уверены, что хотите удалить эту статью?')) {
                              apiClient.delete(`/v1/knowledgebase/pages/${page.id}`).then(() => {
                                setPages(pages.filter((p) => p.id !== page.id))
                              })
                            }
                          }}
                        >
                          <DeleteIcon fontSize="small" color="error" />
                        </IconButton>
                      </Box>
                    </Box>
                  </ListItemButton>
                ))}
              </List>
            </Card>
          )}
        </Box>
      )}

      {/* Overlay — always mounted, no MUI Dialog */}
      <Backdrop open={createPageOpen} sx={{ zIndex: (theme) => theme.zIndex.drawer + 1 }}>
        <Box
          sx={{
            width: 700,
            maxWidth: '90vw',
            height: '90vh',
            bgcolor: 'background.paper',
            borderRadius: 2,
            boxShadow: 24,
            display: 'flex',
            flexDirection: 'column',
            p: 3,
          }}
        >
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
            <Typography variant="h6">Новая статья</Typography>
            <Button onClick={() => setCreatePageOpen(false)} startIcon={<CloseIcon />}>Закрыть</Button>
          </Box>
          <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', mb: 2, minHeight: 0 }}>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>Описание (первая строка будет названием)</Typography>
            <RichTextEditor
              content={editorContent}
              onChange={setEditorContent}
              placeholder="Начните писать..."
              minHeight={0}
              style={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}
            />
          </Box>
          <Typography variant="subtitle2" sx={{ mb: 1, color: 'text.secondary' }}>
            Метки
          </Typography>
          <Stack direction="row" spacing={1} sx={{ mb: 2, flexWrap: 'wrap', alignItems: 'center' }}>
            {labels.map((label) => (
              <Chip key={label} label={label} size="small" onDelete={() => removeLabel(label)} />
            ))}
            <TextField
              size="small"
              placeholder="Добавить метку"
              value={labelInput}
              onChange={(e) => setLabelInput(e.target.value)}
              onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addLabel() } }}
              sx={{ width: 200 }}
            />
          </Stack>
          <Box sx={{ display: 'flex', gap: 1, justifyContent: 'flex-end' }}>
            <Button onClick={() => setCreatePageOpen(false)}>Отмена</Button>
            <Button variant="contained" onClick={handleCreatePage} disabled={!editorContent.trim()}>
              Создать статью
            </Button>
          </Box>
        </Box>
      </Backdrop>
    </Box>
  )
}
