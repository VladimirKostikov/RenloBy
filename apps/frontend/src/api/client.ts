import axios from 'axios'
import { useAdminTestModeStore } from '@/stores/adminTestMode'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

apiClient.interceptors.request.use((config) => {
  const url = config.url ?? ''
  const isAdmin = url.startsWith('/admin') || url.includes('/admin/')
  if (!isAdmin) {
    return config
  }

  try {
    const store = useAdminTestModeStore()
    const isTest = store.isTest ? '1' : '0'
    config.headers = config.headers ?? {}
    config.headers['X-Admin-Test-Mode'] = isTest
    config.params = {
      ...(config.params ?? {}),
      isTest: store.isTest,
    }
  } catch {
    // pinia may be unavailable outside app context
  }

  return config
})

export default apiClient
