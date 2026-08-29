import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAdminTestModeStore } from '@/stores/adminTestMode'

describe('useAdminTestModeStore', () => {
  beforeEach(() => {
    sessionStorage.clear()
    setActivePinia(createPinia())
  })

  it('defaults to test mode disabled', () => {
    const store = useAdminTestModeStore()
    expect(store.enabled).toBe(false)
    expect(store.isTest).toBe(false)
  })

  it('requires confirm before toggling', () => {
    const store = useAdminTestModeStore()
    store.requestToggle(true)
    expect(store.confirmOpen).toBe(true)
    expect(store.enabled).toBe(false)

    store.confirmToggle()
    expect(store.enabled).toBe(true)
    expect(store.confirmOpen).toBe(false)
  })

  it('cancels pending toggle', () => {
    const store = useAdminTestModeStore()
    store.requestToggle(true)
    store.cancelToggle()
    expect(store.enabled).toBe(false)
    expect(store.confirmOpen).toBe(false)
  })
})
