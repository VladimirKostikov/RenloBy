import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import { usePublishedAgo } from '@/composables/usePublishedAgo'
import { __resetNowTickerForTests } from '@/composables/useNowTicker'

describe('usePublishedAgo', () => {
  afterEach(() => {
    vi.useRealTimers()
    __resetNowTickerForTests()
  })

  it('updates relative label when ticker advances', async () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-07-16T12:00:00.000Z'))

    const Host = defineComponent({
      setup() {
        const label = usePublishedAgo('2026-07-16T11:50:00.000Z')
        return { label }
      },
      template: '<span>{{ label }}</span>',
    })

    const wrapper = mount(Host)
    await nextTick()
    expect(wrapper.text()).toBe('10 мин. назад')

    vi.setSystemTime(new Date('2026-07-16T13:00:00.000Z'))
    vi.advanceTimersByTime(30_000)
    await nextTick()

    expect(wrapper.text()).toBe('1 час назад')
  })
})
