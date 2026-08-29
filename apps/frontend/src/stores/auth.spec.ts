import { createPinia, setActivePinia } from 'pinia'
import { AxiosError } from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as authApi from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/auth', () => ({
  login: vi.fn(),
  register: vi.fn(),
  logout: vi.fn(),
  fetchMe: vi.fn(),
}))

vi.mock('@/modules/collections/syncUserCollections', () => ({
  syncUserCollections: vi.fn(),
  resetUserCollections: vi.fn(),
}))

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('logs in and stores user', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      id: 1,
      email: 'user@renlo.local',
      name: 'user',
      roles: ['ROLE_USER'],
    })

    const store = useAuthStore()
    await store.login({ email: 'user@renlo.local', password: 'SecurePass1' })

    expect(store.isAuthenticated).toBe(true)
    expect(store.user?.email).toBe('user@renlo.local')
  })

  it('registers and stores user', async () => {
    vi.mocked(authApi.register).mockResolvedValue({
      id: 2,
      email: 'new@renlo.local',
      name: 'new',
      roles: ['ROLE_USER'],
    })

    const store = useAuthStore()
    await store.register({ email: 'new@renlo.local', password: 'SecurePass1' })

    expect(store.isAuthenticated).toBe(true)
    expect(store.user?.email).toBe('new@renlo.local')
  })

  it('marks initialized on 401 and retries after network errors', async () => {
    vi.mocked(authApi.fetchMe).mockRejectedValueOnce(new AxiosError('Network Error'))

    const store = useAuthStore()
    await store.initialize()
    expect(store.initialized).toBe(false)

    const unauthorized = new AxiosError('Unauthorized')
    unauthorized.response = {
      status: 401,
      statusText: 'Unauthorized',
      headers: {},
      config: unauthorized.config!,
      data: null,
    }
    vi.mocked(authApi.fetchMe).mockRejectedValueOnce(unauthorized)
    await store.initialize()
    expect(store.initialized).toBe(true)
    expect(store.isAuthenticated).toBe(false)
  })
})
