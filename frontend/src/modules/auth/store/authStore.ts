import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { User, AuthResponse } from '@/types'
import apiClient from '@/modules/shared/api/apiClient'

interface AuthState {
  user: User | null
  token: string | null
  loading: boolean
  error: string | null
  setUser: (user: User | null) => void
  setToken: (token: string | null) => void
  setLoading: (loading: boolean) => void
  setError: (error: string | null) => void
  login: (email: string, password: string) => Promise<void>
  register: (data: {
    email: string
    password: string
    password_confirmation: string
    first_name: string
    last_name: string
  }) => Promise<void>
  logout: () => Promise<void>
  fetchUser: () => Promise<void>
  clearError: () => void
}

export const useAuth = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: localStorage.getItem('auth_token'),
      loading: false,
      error: null,

      setUser: (user) => set({ user }),
      setToken: (token) => {
        set({ token })
        if (token) {
          localStorage.setItem('auth_token', token)
        } else {
          localStorage.removeItem('auth_token')
        }
      },
      setLoading: (loading) => set({ loading }),
      setError: (error) => set({ error }),
      clearError: () => set({ error: null }),

      login: async (email: string, password: string) => {
        set({ loading: true, error: null })
        try {
          const response = await apiClient.post<AuthResponse>('/v1/auth/login', {
            email,
            password,
          })
          const token = response.data.token
          const user = response.data.user
          set({ user, token, loading: false, error: null })
          localStorage.setItem('auth_token', token)
        } catch (error: any) {
          set({
            loading: false,
            error: error.response?.data?.message || 'Login failed',
          })
          throw error
        }
      },

      register: async (data) => {
        set({ loading: true, error: null })
        try {
          const response = await apiClient.post<AuthResponse>('/v1/auth/register', data)
          const token = response.data.token
          const user = response.data.user
          set({ user, token, loading: false, error: null })
          localStorage.setItem('auth_token', token)
        } catch (error: any) {
          set({
            loading: false,
            error: error.response?.data?.message || 'Registration failed',
          })
          throw error
        }
      },

      logout: async () => {
        try {
          await apiClient.post('/v1/auth/logout')
        } catch {
          // Ignore logout errors
        }
        set({ user: null, token: null, loading: false })
        localStorage.removeItem('auth_token')
      },

      fetchUser: async () => {
        const token = get().token
        if (!token) {
          set({ user: null, loading: false })
          return
        }

        set({ loading: true })
        try {
          const response = await apiClient.get<User>('/v1/auth/me')
          set({ user: response.data, loading: false })
        } catch {
          set({ user: null, token: null, loading: false })
          localStorage.removeItem('auth_token')
        }
      },
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
      }),
      onRehydrateStorage: () => (state) => {
        if (state && state.token) {
          state.fetchUser()
        }
      },
    }
  )
)
