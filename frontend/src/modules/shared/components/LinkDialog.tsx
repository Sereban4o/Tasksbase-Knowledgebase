import { useState } from 'react'
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Button,
  Box,
  Typography,
} from '@mui/material'

interface LinkDialogProps {
  open: boolean
  onClose: () => void
  url: string
  onUrlChange: (url: string) => void
  text: string
  onTextChange: (text: string) => void
  image?: string
  onImageChange?: (image: string) => void
  onInsert: () => void
  onInsertImage?: () => void
}

export default function LinkDialog({
  open,
  onClose,
  url,
  onUrlChange,
  text,
  onTextChange,
  image = '',
  onImageChange,
  onInsert,
  onInsertImage,
}: LinkDialogProps) {
  const [activeTab, setActiveTab] = useState<'link' | 'image'>('link')

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>
        {activeTab === 'link' ? 'Insert Link' : 'Insert Image'}
      </DialogTitle>
      <DialogContent sx={{ pt: 2 }}>
        <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 2 }}>
          <Button
            size="small"
            onClick={() => setActiveTab('link')}
            sx={{
              borderRadius: 0,
              borderBottom: activeTab === 'link' ? 2 : 0,
              borderColor: 'primary.main',
              minHeight: 36,
            }}
          >
            Link
          </Button>
          <Button
            size="small"
            onClick={() => setActiveTab('image')}
            sx={{
              borderRadius: 0,
              borderBottom: activeTab === 'image' ? 2 : 0,
              borderColor: 'primary.main',
              minHeight: 36,
            }}
          >
            Image
          </Button>
        </Box>

        {activeTab === 'link' ? (
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <TextField
              fullWidth
              label="URL"
              placeholder="https://example.com"
              value={url}
              onChange={(e) => onUrlChange(e.target.value)}
              type="url"
            />
            <TextField
              fullWidth
              label="Display Text (optional)"
              placeholder="Click here"
              value={text}
              onChange={(e) => onTextChange(e.target.value)}
            />
          </Box>
        ) : (
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <TextField
              fullWidth
              label="Image URL"
              placeholder="https://example.com/image.jpg"
              value={image}
              onChange={(e) => onImageChange?.(e.target.value)}
              type="url"
            />
            <Typography variant="caption" color="text.secondary">
              Note: For images, the URL will be inserted directly into the editor.
            </Typography>
          </Box>
        )}
      </DialogContent>
      <DialogActions sx={{ p: 2 }}>
        <Button onClick={onClose}>Cancel</Button>
        {activeTab === 'image' ? (
          <Button
            variant="contained"
            onClick={onInsertImage}
            disabled={!url}
          >
            Insert Image
          </Button>
        ) : (
          <Button
            variant="contained"
            onClick={onInsert}
            disabled={!url}
          >
            Insert Link
          </Button>
        )}
      </DialogActions>
    </Dialog>
  )
}
