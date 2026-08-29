import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useNotificationsStore } from '@/stores/notifications'

const fetchUnread = vi.fn()
const fetchList = vi.fn()

vi.mock('@/api/notifications', () => ({
  fetchUnreadNotificationCount: (...args: unknown[]) => fetchUnread(...args),
  fetchNotifications: (...args: unknown[]) => fetchList(...args),
  markNotificationRead: vi.fn(),
  markAllNotificationsRead: vi.fn(),
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({ isAuthenticated: true }),
}))

describe('notifications store', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
    fetchUnread.mockResolvedValue(2)
    fetchList.mockResolvedValue([])
  })

  it('loads unread count', async () => {
    const store = useNotificationsStore()
    await store.loadUnreadCount()
    expect(store.unreadCount).toBe(2)
    expect(store.hasUnread).toBe(true)
  })
})
