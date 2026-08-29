import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as notificationsApi from '@/api/notifications'
import type { UserNotificationDto } from '@/api/notifications'
import { useAuthStore } from '@/stores/auth'

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<UserNotificationDto[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const loaded = ref(false)
  const error = ref('')

  const hasUnread = computed(() => unreadCount.value > 0)

  function reset() {
    items.value = []
    unreadCount.value = 0
    loaded.value = false
    error.value = ''
  }

  async function loadUnreadCount() {
    const auth = useAuthStore()
    if (!auth.isAuthenticated) {
      unreadCount.value = 0
      return
    }

    try {
      unreadCount.value = await notificationsApi.fetchUnreadNotificationCount()
    } catch {
      unreadCount.value = 0
    }
  }

  async function load() {
    const auth = useAuthStore()
    if (!auth.isAuthenticated) {
      reset()
      return
    }

    loading.value = true
    error.value = ''
    try {
      items.value = await notificationsApi.fetchNotifications()
      unreadCount.value = items.value.filter((item) => !item.isRead).length
      loaded.value = true
    } catch {
      items.value = []
      error.value = 'load_failed'
    } finally {
      loading.value = false
    }
  }

  async function markRead(id: number) {
    const current = items.value.find((item) => item.id === id)
    const updated = await notificationsApi.markNotificationRead(id)
    items.value = items.value.map((item) => (item.id === id ? updated : item))
    if (current && !current.isRead && unreadCount.value > 0) {
      unreadCount.value -= 1
    }
  }

  async function markAllRead() {
    await notificationsApi.markAllNotificationsRead()
    items.value = items.value.map((item) => ({ ...item, isRead: true }))
    unreadCount.value = 0
  }

  return {
    items,
    unreadCount,
    loading,
    loaded,
    error,
    hasUnread,
    reset,
    loadUnreadCount,
    load,
    markRead,
    markAllRead,
  }
})
