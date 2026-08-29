import { onMounted, ref, watch } from 'vue'
import type { AdminCrudApi } from '@/api/admin'
import { useAdminTestModeStore } from '@/stores/adminTestMode'
import type { PaginatedResponse } from '@/types'

export function useAdminCrud<T extends { id: number }>(
  api: AdminCrudApi<T>,
  listParams?: () => Record<string, unknown>,
) {
  const items = ref<T[]>([])
  const loading = ref(false)
  const error = ref('')
  const testMode = useAdminTestModeStore()

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const result = await api.list({
        isTest: testMode.isTest,
        limit: 100,
        ...(listParams?.() ?? {}),
      })
      items.value = Array.isArray(result) ? result : (result as PaginatedResponse<T>).items
    } catch {
      error.value = 'load_failed'
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function create(payload: Partial<T>) {
    await api.create(payload)
    await load()
  }

  async function update(id: number, payload: Partial<T>) {
    await api.update(id, payload)
    await load()
  }

  async function remove(id: number) {
    await api.remove(id)
    await load()
  }

  onMounted(load)
  watch(
    () => testMode.isTest,
    () => {
      void load()
    },
  )

  return { items, loading, error, load, create, update, remove }
}
