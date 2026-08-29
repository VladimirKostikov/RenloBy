import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useToastStore } from '@/stores/toast'

describe('useToastStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('shows message and hides after duration', () => {
    const toast = useToastStore()

    toast.show('Добавлено в избранное', 1000)

    expect(toast.visible).toBe(true)
    expect(toast.message).toBe('Добавлено в избранное')

    vi.advanceTimersByTime(1000)

    expect(toast.visible).toBe(false)
  })

  it('ignores empty messages', () => {
    const toast = useToastStore()

    toast.show('   ')

    expect(toast.visible).toBe(false)
    expect(toast.message).toBe('')
  })
})
