import { useComparisonsStore } from '@/stores/comparisons'
import { useFavoritesStore } from '@/stores/favorites'
import { useNotificationsStore } from '@/stores/notifications'

export async function syncUserCollections(force = false) {
  const favorites = useFavoritesStore()
  const comparisons = useComparisonsStore()
  const notifications = useNotificationsStore()

  const tasks: Promise<void>[] = []
  if (force || !favorites.loaded) {
    tasks.push(favorites.load())
  }
  if (force || !comparisons.loaded) {
    tasks.push(comparisons.load())
  }
  if (force || !notifications.loaded) {
    tasks.push(notifications.loadUnreadCount())
  }

  await Promise.all(tasks)
}

export function resetUserCollections() {
  useFavoritesStore().reset()
  useComparisonsStore().reset()
  useNotificationsStore().reset()
}
