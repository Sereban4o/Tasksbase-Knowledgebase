import { useParams, useNavigate } from 'react-router-dom'
import {
  Box,
  Typography,
  Button,
  TextField,
  Stack,
  Chip,
  Divider,
  IconButton,
} from '@mui/material'
import { ArrowBack as ArrowBackIcon, Save as SaveIcon } from '@mui/icons-material'
import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/modules/shared/api/apiClient'
import RichTextEditor from '@/modules/shared/components/RichTextEditor'
import LoadingSpinner from '@/modules/shared/components/LoadingSpinner'
export default function PageEditor() {
  const { spaceKey, pageId } = useParams<{ spaceKey?: string; pageId?: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [body, setBody] = useState('')
  const [labels, setLabels] = useState<string[]>([])
  const [labelInput, setLabelInput] = useState('')

  // Generate title from first line of body
  const generateTitle = (html: string): string => {
    // Remove HTML tags and get first line
    const temp = document.createElement('div')
    temp.innerHTML = html
    const text = temp.textContent || temp.innerText || ''
    const firstLine = text.split('\n')[0].trim()
    return firstLine || 'Без названия'
  }

  const { data: existingPage, isLoading } = useQuery({
    queryKey: ['page', pageId],
    queryFn: async () => {
      const response = await apiClient.get('/v1/knowledgebase/pages/' + pageId)
      return response.data
    },
    enabled: !!pageId,
  })

  useEffect(() => {
    if (existingPage) {
      const pageBody = existingPage.body || ''
      setBody(pageBody)
      setLabels(existingPage.labels || [])
    }
  }, [existingPage, pageId])

  const savePage = useMutation({
    mutationFn: (data: { title: string; body: string; labels?: string[] }) => {
      if (pageId) {
        return apiClient.patch('/v1/knowledgebase/pages/' + pageId, data)
      }
      return apiClient.post('/v1/knowledgebase/spaces/' + spaceKey + '/pages', data)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['pages', spaceKey] })
      if (pageId) {
        queryClient.invalidateQueries({ queryKey: ['page', pageId] })
      }
      // Navigate to the page view after save
      navigate('/knowledgebase/' + spaceKey + '/pages/' + pageId)
    },
    onSettled: () => {
      // Reset form after successful save
    },
  })

  const handleSave = () => {
    const autoTitle = generateTitle(body)
    savePage.mutate({ title: autoTitle, body, labels })
  }

  const addLabel = () => {
    if (labelInput.trim() && !labels.includes(labelInput.trim().toLowerCase())) {
      setLabels([...labels, labelInput.trim().toLowerCase()])
      setLabelInput('')
    }
  }

  const removeLabel = (label: string) => {
    setLabels(labels.filter((l) => l !== label))
  }

  if (pageId && isLoading) {
    return <LoadingSpinner message="Загрузка статьи..." />
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
        <IconButton onClick={() => navigate('/knowledgebase/' + spaceKey)} sx={{ mr: 2 }}>
          <ArrowBackIcon />
        </IconButton>
        <Typography variant="h4" component="h1">
          {pageId ? 'Редактирование' : 'Новая статья'}
        </Typography>
      </Box>

      <Box sx={{ maxWidth: 900 }}>
        <Stack direction="row" spacing={1} sx={{ mb: 2, flexWrap: 'wrap', alignItems: 'center' }}>
          {labels.map((label) => (
            <Chip
              key={label}
              label={label}
              size="small"
              onDelete={() => removeLabel(label)}
            />
          ))}
          <TextField
            size="small"
            placeholder="Добавить метку"
            value={labelInput}
            onChange={(e) => setLabelInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                e.preventDefault()
                addLabel()
              }
            }}
            sx={{ width: 150 }}
          />
        </Stack>

        <Divider sx={{ mb: 2 }} />

        <Box sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}>
          <RichTextEditor
            content={body}
            onChange={setBody}
            placeholder="Начните писать..."
            minHeight={0}
            style={{ flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: 0 }}
          />
        </Box>

        <Stack direction="row" spacing={2}>
          <Button
            variant="contained"
            startIcon={<SaveIcon />}
            onClick={handleSave}
            disabled={savePage.isPending}
          >
            {pageId ? 'Сохранить' : 'Создать'}
          </Button>
          <Button onClick={() => navigate('/knowledgebase/' + spaceKey)}>
            Отмена
          </Button>
        </Stack>
      </Box>
    </Box>
  )
}
