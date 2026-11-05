import { useCallback, useState, useEffect, useRef } from 'react'
import { useEditor, EditorContent } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import TextAlign from '@tiptap/extension-text-align'
import TiptapImage from '@tiptap/extension-image'
import { Box, Toolbar, IconButton, Tooltip, MenuItem, FormControl, Select, Dialog, DialogContent, Typography } from '@mui/material'
import {
  FormatBold,
  FormatItalic,
  FormatListBulleted,
  FormatListNumbered,
  FormatQuote,
  Code,
  FormatAlignLeft,
  FormatAlignCenter,
  FormatAlignRight,
  Link as LinkIcon,
  Image as ImageIcon,
  HorizontalRule,
  Close as CloseIcon,
} from '@mui/icons-material'
import LinkDialog from './LinkDialog'

interface RichTextEditorProps {
  content: string
  onChange: (html: string) => void
  placeholder?: string
  minHeight?: number
  disabled?: boolean
  toolbar?: boolean
  style?: React.CSSProperties
}

export default function RichTextEditor({
  content,
  onChange,
  placeholder = 'Начните писать...',
  minHeight = 200,
  disabled = false,
  toolbar = true,
  style,
}: RichTextEditorProps) {
  const [showLinkDialog, setShowLinkDialog] = useState(false)
  const [linkUrl, setLinkUrl] = useState('')
  const [linkText, setLinkText] = useState('')
  const [imageUrl, setImageUrl] = useState('')
  const [imageDialogOpen, setImageDialogOpen] = useState(false)
  const [imageDialogSrc, setImageDialogSrc] = useState('')

  const editorInstance = useEditor({
    extensions: [
      StarterKit.configure({
        heading: {
          levels: [1, 2, 3],
        },
        // Disable typing rules that interfere with normal typing
        bulletList: false,
        orderedList: false,
        blockquote: false,
        horizontalRule: false,
        codeBlock: false,
        dropcursor: false,
        gapcursor: false,
      }),
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          class: 'link',
        },
      }),
      Placeholder.configure({
        placeholder,
      }),
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      TiptapImage,
    ],
    content: '',
    onUpdate: ({ editor }) => {
      const html = editor.getHTML()
      onChange(html)
    },
    editorProps: {
      attributes: {
        class: 'tiptap-editor',
        style: 'outline: none; overflow-y: auto;'
      },
      handleClick: (_view, _pos, event) => {
        const target = event.target as HTMLElement | null
        if (target && target.tagName === 'IMG') {
          const src = target.getAttribute('src')
          if (src) {
            setImageDialogSrc(src)
            setImageDialogOpen(true)
          }
        }
        return false
      },
    },
    editable: !disabled,
  })

  const previousContentRef = useRef<string | undefined>(undefined)

  // Update editor content when content prop changes
  useEffect(() => {
    if (!editorInstance) return
    if (previousContentRef.current === content) return
    editorInstance.commands.setContent(content || '')
    previousContentRef.current = content
  }, [editorInstance, content])

  const toggleBold = useCallback(() => {
    editorInstance?.chain().focus().toggleBold().run()
  }, [editorInstance])

  const toggleItalic = useCallback(() => {
    editorInstance?.chain().focus().toggleItalic().run()
  }, [editorInstance])

  const toggleBulletList = useCallback(() => {
    editorInstance?.chain().focus().toggleBulletList().run()
  }, [editorInstance])

  const toggleOrderedList = useCallback(() => {
    editorInstance?.chain().focus().toggleOrderedList().run()
  }, [editorInstance])

  const toggleBlockquote = useCallback(() => {
    editorInstance?.chain().focus().toggleBlockquote().run()
  }, [editorInstance])

  const toggleCodeBlock = useCallback(() => {
    editorInstance?.chain().focus().toggleCodeBlock().run()
  }, [editorInstance])

  const setAlign = useCallback(
    (align: 'left' | 'center' | 'right') => {
      editorInstance?.chain().focus().setTextAlign(align).run()
    },
    [editorInstance]
  )

  const insertLink = useCallback(() => {
    if (linkUrl) {
      editorInstance
        ?.chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: linkUrl })
        .run()
    }
    setShowLinkDialog(false)
    setLinkUrl('')
    setLinkText('')
  }, [editorInstance, linkUrl])

  const insertImage = useCallback(() => {
    if (imageUrl) {
      editorInstance?.chain().focus().setImage({ src: imageUrl }).run()
      setImageUrl('')
    }
  }, [editorInstance, imageUrl])

  const insertHorizontalRule = useCallback(() => {
    editorInstance?.chain().focus().setHorizontalRule().run()
  }, [editorInstance])

  const currentHeading = editorInstance?.getAttributes('heading').level || 1

  if (!editorInstance) {
    return <Box sx={{ minHeight, p: 2, bgcolor: '#f9f9f9', borderRadius: 1 }}>Загрузка редактора...</Box>
  }

  return (
    <Box sx={{ border: '1px solid #dadce0', borderRadius: 1, overflow: 'hidden' }} style={style}>
      {toolbar && (
        <Box
          sx={{
            bgcolor: '#f9f9f9',
            borderBottom: '1px solid #dadce0',
            p: 0.5,
          }}
        >
          <Toolbar disableGutters sx={{ gap: 0.25, flexWrap: 'wrap' }}>
            {/* Headings */}
            <FormControl size="small" sx={{ minWidth: 120, mr: 0.5 }}>
              <Select
                value={currentHeading}
                onChange={(e) => {
                  if (e.target.value === '0') {
                    editorInstance.chain().focus().setParagraph().run()
                  } else {
                    editorInstance.chain().focus().toggleHeading({ level: Number(e.target.value) as 1 | 2 | 3 }).run()
                  }
                }}
                sx={{ fontSize: '0.875rem', minHeight: 32 }}
              >
                <MenuItem value="0" sx={{ fontSize: '0.875rem' }}>Paragraph</MenuItem>
                <MenuItem value="1" sx={{ fontSize: '1.25rem', fontWeight: 700 }}>Heading 1</MenuItem>
                <MenuItem value="2" sx={{ fontSize: '1.1rem', fontWeight: 600 }}>Heading 2</MenuItem>
                <MenuItem value="3" sx={{ fontSize: '1rem', fontWeight: 500 }}>Heading 3</MenuItem>
              </Select>
            </FormControl>

            <Box sx={{ borderRight: '1px solid #dadce0', mx: 0.5 }} />

            {/* Basic formatting */}
            <Tooltip title="Bold">
              <IconButton size="small" onClick={toggleBold} sx={{ p: 0.5 }}>
                <FormatBold fontSize="small" />
              </IconButton>
            </Tooltip>
            <Tooltip title="Italic">
              <IconButton size="small" onClick={toggleItalic} sx={{ p: 0.5 }}>
                <FormatItalic fontSize="small" />
              </IconButton>
            </Tooltip>

            <Box sx={{ borderRight: '1px solid #dadce0', mx: 0.5 }} />

            {/* Lists */}
            <Tooltip title="Bullet List">
              <IconButton size="small" onClick={toggleBulletList} sx={{ p: 0.5 }}>
                <FormatListBulleted fontSize="small" />
              </IconButton>
            </Tooltip>
            <Tooltip title="Ordered List">
              <IconButton size="small" onClick={toggleOrderedList} sx={{ p: 0.5 }}>
                <FormatListNumbered fontSize="small" />
              </IconButton>
            </Tooltip>

            <Box sx={{ borderRight: '1px solid #dadce0', mx: 0.5 }} />

            {/* Block elements */}
            <Tooltip title="Blockquote">
              <IconButton size="small" onClick={toggleBlockquote} sx={{ p: 0.5 }}>
                <FormatQuote fontSize="small" />
              </IconButton>
            </Tooltip>
            <Tooltip title="Code Block">
              <IconButton size="small" onClick={toggleCodeBlock} sx={{ p: 0.5 }}>
                <Code fontSize="small" />
              </IconButton>
            </Tooltip>

            <Box sx={{ borderRight: '1px solid #dadce0', mx: 0.5 }} />

            {/* Alignment */}
            <Tooltip title="Align Left">
              <IconButton size="small" onClick={() => setAlign('left')} sx={{ p: 0.5 }}>
                <FormatAlignLeft fontSize="small" />
              </IconButton>
            </Tooltip>
            <Tooltip title="Align Center">
              <IconButton size="small" onClick={() => setAlign('center')} sx={{ p: 0.5 }}>
                <FormatAlignCenter fontSize="small" />
              </IconButton>
            </Tooltip>
            <Tooltip title="Align Right">
              <IconButton size="small" onClick={() => setAlign('right')} sx={{ p: 0.5 }}>
                <FormatAlignRight fontSize="small" />
              </IconButton>
            </Tooltip>

            <Box sx={{ borderRight: '1px solid #dadce0', mx: 0.5 }} />

            {/* Insert */}
            <Tooltip title="Link">
              <IconButton size="small" onClick={() => { setShowLinkDialog(true); setImageUrl('') }} sx={{ p: 0.5 }}>
                <LinkIcon fontSize="small" />
              </IconButton>
            </Tooltip>
            <Tooltip title="Image">
              <IconButton size="small" onClick={() => { setShowLinkDialog(true); setLinkText('') }} sx={{ p: 0.5 }}>
                <ImageIcon fontSize="small" />
              </IconButton>
            </Tooltip>
            <Tooltip title="Horizontal Rule">
              <IconButton size="small" onClick={insertHorizontalRule} sx={{ p: 0.5 }}>
                <HorizontalRule fontSize="small" />
              </IconButton>
            </Tooltip>
          </Toolbar>

          {/* Link Dialog */}
          <LinkDialog
            open={showLinkDialog}
            onClose={() => {
              setShowLinkDialog(false)
              setLinkUrl('')
              setLinkText('')
              setImageUrl('')
            }}
            url={linkUrl}
            onUrlChange={setLinkUrl}
            text={linkText}
            onTextChange={setLinkText}
            image={imageUrl}
            onImageChange={setImageUrl}
            onInsert={insertLink}
            onInsertImage={insertImage}
          />
        </Box>
      )}

      <Box
        sx={{
          minHeight: minHeight,
          flexGrow: 1,
          p: 2,
          '& .tiptap': {
            outline: 'none',
            '& p.is-editor-empty:first-of-type::before': {
              content: 'attr(data-placeholder)',
              color: '#999',
              pointerEvents: 'none',
            },
            '& h1': { fontSize: '1.75rem', fontWeight: 700, margin: '0.5rem 0' },
            '& h2': { fontSize: '1.4rem', fontWeight: 600, margin: '0.5rem 0' },
            '& h3': { fontSize: '1.15rem', fontWeight: 500, margin: '0.5rem 0' },
            '& p': { margin: '0.5rem 0' },
            '& ul, & ol': { paddingLeft: 1.5, margin: '0.5rem 0' },
            '& blockquote': {
              borderLeft: '4px solid #dadce0',
              paddingLeft: 1.5,
              margin: '0.5rem 0',
              color: '#666',
            },
            '& pre': {
              bgcolor: '#f5f5f5',
              padding: 1.5,
              borderRadius: 1,
              fontFamily: 'monospace',
              margin: '0.5rem 0',
            },
            '& hr': {
              border: 'none',
              borderTop: '2px solid #dadce0',
              margin: '1rem 0',
            },
            '& img': {
              maxWidth: '100%',
              height: 'auto',
              borderRadius: 1,
              margin: '0.5rem 0',
              cursor: 'pointer',
              '&:hover': {
                opacity: 0.85,
              },
            },
            '& a.link': {
              color: '#1a73e8',
              textDecoration: 'underline',
            },
          },
        }}
      >
        <EditorContent editor={editorInstance} />
      </Box>

      {/* Image Preview Dialog */}
      <Dialog
        open={imageDialogOpen}
        onClose={() => setImageDialogOpen(false)}
        maxWidth="lg"
        fullWidth
      >
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', px: 2, py: 1 }}>
          <Typography variant="h6">Просмотр изображения</Typography>
          <IconButton
            onClick={() => setImageDialogOpen(false)}
            sx={{ color: 'text.secondary' }}
          >
            <CloseIcon />
          </IconButton>
        </Box>
        <DialogContent sx={{ display: 'flex', justifyContent: 'center', p: 2 }}>
          <img
            src={imageDialogSrc}
            alt="Preview"
            style={{
              maxWidth: '100%',
              maxHeight: '70vh',
              objectFit: 'contain',
              borderRadius: 8,
            }}
          />
        </DialogContent>
      </Dialog>
    </Box>
  )
}
