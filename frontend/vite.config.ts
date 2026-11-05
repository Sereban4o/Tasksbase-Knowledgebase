import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  optimizeDeps: {
    force: true,
    include: [
      'react',
      'react-dom',
      'react-dom/client',
      'react-router-dom',
      '@tanstack/react-query',
      '@mui/material',
      '@mui/icons-material',
      'axios',
      '@tiptap/react',
      '@tiptap/starter-kit',
      'zustand',
      'react-hook-form',
      '@emotion/react',
      '@emotion/styled',
      'react-dnd',
      'react-dnd-html5-backend',
    ],
  },
  esbuild: {
    jsx: 'automatic',
    jsxImportSource: 'react',
  },
  server: {
    port: 5173,
    host: '0.0.0.0',
    cors: true,
    watch: {
      usePolling: true,
      interval: 300,
    },
    proxy: {
      '/api': {
        target: 'http://nginx',
      },
      '/broadcasting': {
        target: 'http://nginx',
      },
      '/storage': {
        target: 'http://nginx',
      },
      '/admin': {
        target: 'http://nginx',
      },
      '/sanctum': {
        target: 'http://nginx',
      },
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'react-vendor': ['react', 'react-dom', 'react-router-dom'],
          'mui-vendor': ['@mui/material', '@mui/icons-material'],
          'query-vendor': ['@tanstack/react-query'],
        },
      },
    },
  },
})
