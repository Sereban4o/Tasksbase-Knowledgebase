import { useAuth as useAuthStore } from '@/modules/auth/store/authStore'

export { useAuthStore as useAuth }

export function useAuthContext() {
  const { user, token, loading, error, login, register, logout, fetchUser, clearError } = useAuthStore()

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated: !!token && !!user,
    login,
    register,
    logout,
    fetchUser,
    clearError,
  }
}
