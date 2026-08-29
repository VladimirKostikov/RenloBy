import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { flushPromises } from '@vue/test-utils'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { useAdminTestModeStore } from '@/stores/adminTestMode'

describe('useAdminCrud', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    sessionStorage.clear()
  })

  it('loads items with test mode and limit 100', async () => {
    const list = vi.fn().mockResolvedValue({
      items: [{ id: 1, address: 'Test' }],
      total: 1,
      page: 1,
      limit: 100,
    })
    const api = {
      list,
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
    }

    const { items, load } = useAdminCrud<{ id: number; address: string }>(api)
    await load()
    await flushPromises()

    expect(list).toHaveBeenCalledWith({ isTest: false, limit: 100 })
    expect(items.value).toEqual([{ id: 1, address: 'Test' }])
  })

  it('reloads when test mode changes', async () => {
    const list = vi.fn().mockResolvedValue([])
    const api = {
      list,
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
    }

    useAdminCrud<{ id: number }>(api)
    await flushPromises()
    list.mockClear()

    const testMode = useAdminTestModeStore()
    testMode.enabled = true
    await flushPromises()

    expect(list).toHaveBeenCalledWith({ isTest: true, limit: 100 })
  })
})
