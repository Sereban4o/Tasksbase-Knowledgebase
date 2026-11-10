import { createTheme, type Shadows } from '@mui/material/styles'

// ─── Custom palette augmentation ──────────────────────────────────────────────
declare module '@mui/material/styles' {
  interface Palette {
    border: string
    status: {
      todo: string
      inProgress: string
      inReview: string
      done: string
      closed: string
      bug: string
      task: string
      story: string
    }
  }

  interface PaletteOptions {
    border?: string
    status?: {
      todo: string
      inProgress: string
      inReview: string
      done: string
      closed: string
      bug: string
      task: string
      story: string
    }
  }

  interface TypeBackground {
    sidebar?: string
    sidebarHover?: string
    sidebarActive?: string
  }

  interface PaletteColor {
    gradient?: string
  }

  interface SimplePaletteColorOptions {
    gradient?: string
  }
}

// ─── Color Palette ───────────────────────────────────────────────────────────
// Inspired by Atlassian/Linear design systems
const palette = {
  primary: {
    main: '#0052CC',
    light: '#4C9AFF',
    dark: '#003D99',
    contrastText: '#ffffff',
    gradient: 'linear-gradient(135deg, #0052CC 0%, #4C9AFF 100%)',
  },
  secondary: {
    main: '#7C3AED',
    light: '#A78BFA',
    dark: '#5B21B6',
    contrastText: '#ffffff',
    gradient: 'linear-gradient(135deg, #7C3AED 0%, #A78BFA 100%)',
  },
  success: {
    main: '#00875A',
    light: '#36B37E',
    dark: '#006644',
    contrastText: '#ffffff',
  },
  warning: {
    main: '#FF8B00',
    light: '#FFAB00',
    dark: '#E67E00',
    contrastText: '#ffffff',
  },
  error: {
    main: '#FF5630',
    light: '#FF8F73',
    dark: '#DE350B',
    contrastText: '#ffffff',
  },
  info: {
    main: '#00B8D9',
    light: '#4FC3F7',
    dark: '#0097B2',
    contrastText: '#ffffff',
  },
  background: {
    default: '#F4F5F7',
    paper: '#ffffff',
    sidebar: 'linear-gradient(180deg, #001E3C 0%, #003366 50%, #004080 100%)',
    sidebarHover: 'rgba(255, 255, 255, 0.08)',
    sidebarActive: 'rgba(255, 255, 255, 0.15)',
  },
  text: {
    primary: '#172B4D',
    secondary: '#5E6C84',
    disabled: '#97A0AF',
  },
  divider: '#E4E7EB',
  border: '#D0D5DD',
  // Custom colors for status chips
  status: {
    todo: '#6B7280',
    inProgress: '#0052CC',
    inReview: '#FF9800',
    done: '#00875A',
    closed: '#7C3AED',
    bug: '#FF5630',
    task: '#36B37E',
    story: '#00B8D9',
  },
}

const theme = createTheme({
  palette,
  typography: {
    fontFamily: '"Inter", "Helvetica", "Arial", sans-serif',
    h1: {
      fontSize: '2rem',
      fontWeight: 600,
      lineHeight: 1.2,
      letterSpacing: '-0.015em',
    },
    h2: {
      fontSize: '1.75rem',
      fontWeight: 600,
      lineHeight: 1.3,
      letterSpacing: '-0.008em',
    },
    h3: {
      fontSize: '1.5rem',
      fontWeight: 600,
      lineHeight: 1.3,
    },
    h4: {
      fontSize: '1.25rem',
      fontWeight: 600,
      lineHeight: 1.4,
    },
    h5: {
      fontSize: '1.125rem',
      fontWeight: 600,
      lineHeight: 1.4,
    },
    h6: {
      fontSize: '1rem',
      fontWeight: 600,
      lineHeight: 1.4,
    },
    button: {
      textTransform: 'none',
      fontWeight: 500,
      letterSpacing: '0.01em',
    },
  },
  shape: {
    borderRadius: 12,
  },
  shadows: [
    '0 0 0 1px rgba(0, 0, 0, 0.04)',
    '0 1px 2px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06)',
    '0 4px 8px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.08)',
    '0 8px 16px rgba(0, 0, 0, 0.04), 0 2px 4px rgba(0, 0, 0, 0.08)',
    '0 16px 32px rgba(0, 0, 0, 0.04), 0 4px 8px rgba(0, 0, 0, 0.08)',
    '0 32px 64px rgba(0, 0, 0, 0.04), 0 8px 16px rgba(0, 0, 0, 0.08)',
    ...(Array(19).fill('none') as string[]),
  ] as unknown as Shadows,
  components: {
    // ─── MUI Components ──────────────────────────────────────────────────
    MuiButton: {
      defaultProps: {
        disableElevation: true,
      },
      styleOverrides: {
        root: {
          borderRadius: 8,
          padding: '8px 16px',
          fontWeight: 500,
          transition: 'all 0.2s ease',
        },
        containedPrimary: {
          background: 'linear-gradient(135deg, #0052CC 0%, #0065FF 100%)',
          boxShadow: '0 1px 3px rgba(0, 82, 204, 0.25)',
          '&:hover': {
            background: 'linear-gradient(135deg, #003D99 0%, #0052CC 100%)',
            boxShadow: '0 4px 12px rgba(0, 82, 204, 0.35)',
            transform: 'translateY(-1px)',
          },
        },
        containedSecondary: {
          background: 'linear-gradient(135deg, #7C3AED 0%, #8B5CF6 100%)',
          boxShadow: '0 1px 3px rgba(124, 58, 237, 0.25)',
          '&:hover': {
            background: 'linear-gradient(135deg, #5B21B6 0%, #7C3AED 100%)',
            boxShadow: '0 4px 12px rgba(124, 58, 237, 0.35)',
            transform: 'translateY(-1px)',
          },
        },
        outlined: {
          borderColor: palette.border,
          '&:hover': {
            borderColor: palette.primary.main,
            backgroundColor: 'rgba(0, 82, 204, 0.04)',
          },
        },
      },
    },
    MuiCard: {
      styleOverrides: {
        root: {
          borderRadius: 16,
          border: `1px solid ${palette.border}`,
          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.04)',
          transition: 'all 0.2s ease',
          '&:hover': {
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.08)',
            borderColor: palette.primary.light,
          },
        },
      },
    },
    MuiChip: {
      styleOverrides: {
        root: {
          fontWeight: 500,
          borderRadius: 6,
        },
      },
    },
    MuiAppBar: {
      styleOverrides: {
        root: {
          backgroundColor: palette.background.paper,
          borderBottom: `1px solid ${palette.border}`,
          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.04)',
        },
      },
    },
    MuiDrawer: {
      styleOverrides: {
        paper: {
          borderRight: 'none',
          boxShadow: '2px 0 12px rgba(0, 0, 0, 0.08)',
        },
      },
    },
    MuiTextField: {
      styleOverrides: {
        root: {
          '& .MuiOutlinedInput-root': {
            borderRadius: 8,
            '& fieldset': {
              borderColor: palette.border,
              transition: 'border-color 0.2s ease',
            },
            '&:hover fieldset': {
              borderColor: palette.primary.light,
            },
            '&.Mui-focused fieldset': {
              borderColor: palette.primary.main,
              boxShadow: `0 0 0 3px rgba(0, 82, 204, 0.1)`,
            },
          },
        },
      },
    },
    MuiSelect: {
      styleOverrides: {
        root: {
          borderRadius: 8,
        },
        select: {
          minHeight: 40,
          display: 'flex',
          alignItems: 'center',
        },
      },
    },
    MuiIconButton: {
      styleOverrides: {
        root: {
          borderRadius: 8,
          transition: 'all 0.2s ease',
          '&:hover': {
            backgroundColor: 'rgba(0, 82, 204, 0.08)',
          },
        },
      },
    },
    MuiListItemButton: {
      styleOverrides: {
        root: {
          borderRadius: 10,
          margin: '4px 8px',
          padding: '8px 12px',
          transition: 'all 0.2s ease',
          '&:hover': {
            backgroundColor: 'rgba(0, 82, 204, 0.06)',
          },
          '&.Mui-selected': {
            backgroundColor: 'rgba(0, 82, 204, 0.12)',
            '&:hover': {
              backgroundColor: 'rgba(0, 82, 204, 0.16)',
            },
            '& .MuiListItemIcon-root': {
              color: palette.primary.main,
            },
          },
        },
      },
    },
    MuiDivider: {
      styleOverrides: {
        root: {
          borderColor: palette.border,
        },
      },
    },
    MuiTooltip: {
      styleOverrides: {
        tooltip: {
          borderRadius: 8,
          fontSize: '0.8125rem',
          fontWeight: 500,
          padding: '6px 12px',
          boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
        },
        arrow: {
          '&::before': {
            border: '1px solid #374151',
          },
        },
      },
    },
    MuiDialog: {
      styleOverrides: {
        paper: {
          borderRadius: 20,
          boxShadow: '0 24px 48px rgba(0, 0, 0, 0.12)',
        },
      },
    },
    MuiDialogTitle: {
      styleOverrides: {
        root: {
          fontSize: '1.25rem',
          fontWeight: 600,
          padding: '24px 24px 0',
        },
      },
    },
    MuiDialogContent: {
      styleOverrides: {
        root: {
          padding: '20px 24px',
        },
      },
    },
    MuiDialogActions: {
      styleOverrides: {
        root: {
          padding: '16px 24px 24px',
        },
      },
    },
    MuiAlert: {
      styleOverrides: {
        standardSuccess: {
          backgroundColor: '#E3FCEF',
          color: '#006644',
          '& .MuiAlert-icon': {
            color: '#00875A',
          },
        },
        standardError: {
          backgroundColor: '#FFEBE6',
          color: '#BF2600',
          '& .MuiAlert-icon': {
            color: '#FF5630',
          },
        },
        standardWarning: {
          backgroundColor: '#FFFAE6',
          color: '#614F00',
          '& .MuiAlert-icon': {
            color: '#FFAB00',
          },
        },
      },
    },
    MuiAvatar: {
      styleOverrides: {
        root: {
          fontWeight: 600,
          fontSize: '0.875rem',
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
        },
      },
    },
  },
})

// ─── Custom CSS for global styles ────────────────────────────────────────────
const style = document.createElement('style')
style.textContent = `
  * {
    scrollbar-width: thin;
    scrollbar-color: #D0D5DD transparent;
  }
  
  *::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  
  *::-webkit-scrollbar-track {
    background: transparent;
  }
  
  *::-webkit-scrollbar-thumb {
    background-color: #D0D5DD;
    border-radius: 4px;
    border: 2px solid transparent;
  }
  
  *::-webkit-scrollbar-thumb:hover {
    background-color: #97A0AF;
  }
  
  body {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }
  
  ::selection {
    background-color: rgba(0, 82, 204, 0.16);
    color: #003D99;
  }
`
document.head.appendChild(style)

export default theme
