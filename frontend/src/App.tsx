import { lazy, Suspense, useEffect, useMemo, useState } from 'react'
import {
  createBrowserRouter,
  Navigate,
  RouterProvider,
  type RouteObject,
} from 'react-router-dom'
import { Box, CircularProgress } from '@mui/material'

const LoginPage = lazy(() => import('./modules/auth/pages/LoginPage'))
const RegisterPage = lazy(() => import('./modules/auth/pages/RegisterPage'))
const ProfilePage = lazy(() => import('./modules/auth/pages/ProfilePage'))
const KanbanBoardPage = lazy(() => import('./modules/tasksbase/boards/pages/KanbanBoardPage'))
const IssueDetailPage = lazy(() => import('./modules/tasksbase/issues/pages/IssueDetailPage'))
const SpacePage = lazy(() => import('./modules/knowledgebase/spaces/pages/SpacePage'))
const PageEditorPage = lazy(() => import('./modules/knowledgebase/pages/pages/PageEditorPage'))
const PageViewPage = lazy(() => import('./modules/knowledgebase/pages/pages/PageViewPage'))
const AppLayout = lazy(() => import('./modules/shared/components/AppLayout'))

function LoadingFallback() {
  return (
    <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
      <CircularProgress />
    </Box>
  )
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const [isAuthenticated, setIsAuthenticated] = useState(false)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('auth_token')
    setIsAuthenticated(!!token)
    setIsLoading(false)
  }, [])

  if (isLoading) {
    return <LoadingFallback />
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  return <>{children}</>
}

export function createAppRouter(router: RouteObject[] = []) {
  const routes: RouteObject[] = [
    { path: '/login', element: <LoginPage /> },
    { path: '/register', element: <RegisterPage /> },
    {
      path: '/',
      element: (
        <ProtectedRoute>
          <AppLayout />
        </ProtectedRoute>
      ),
      children: [
        { index: true, element: <KanbanBoardPage /> },
        { path: 'board', element: <KanbanBoardPage /> },
        { path: 'issues/:issueId', element: <IssueDetailPage /> },
        { path: 'knowledgebase', element: <SpacePage /> },
        { path: 'knowledgebase/:spaceKey', element: <SpacePage /> },
        { path: 'knowledgebase/:spaceKey/pages/:pageId', element: <PageViewPage /> },
        { path: 'knowledgebase/:spaceKey/pages/new', element: <PageEditorPage /> },
        { path: 'knowledgebase/:spaceKey/pages/:pageId/edit', element: <PageEditorPage /> },
        { path: 'profile', element: <ProfilePage /> },
      ],
    },
    { path: '*', element: <Navigate to="/" replace /> },
    ...router,
  ]

  return routes
}

export default function App() {
  const appRouter = useMemo(
    () => createBrowserRouter(createAppRouter(), { future: { v7_startTransition: true } }),
    [],
  )

  return (
    <Suspense fallback={<LoadingFallback />}>
      <RouterProvider router={appRouter} />
    </Suspense>
  )
}
