import { defineComponent, nextTick } from 'vue'
import { mount, flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { useRoutePathPending } from '@/composables/useRoutePathPending'

describe('useRoutePathPending', () => {
  afterEach(() => {
    vi.useRealTimers()
  })

  it('becomes true when path changes and false after navigation', async () => {
    vi.useFakeTimers()

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/', component: { template: '<div />' } },
        { path: '/next', component: { template: '<div />' } },
      ],
    })

    const Host = defineComponent({
      setup() {
        const pending = useRoutePathPending()
        return { pending }
      },
      template: '<div>{{ pending }}</div>',
    })

    await router.push('/')
    await router.isReady()

    const wrapper = mount(Host, {
      global: {
        plugins: [router],
      },
    })

    expect(wrapper.text()).toBe('false')

    const navigation = router.push('/next')
    await flushPromises()
    expect(wrapper.text()).toBe('true')

    await navigation
    await flushPromises()
    vi.advanceTimersByTime(50)
    await nextTick()

    expect(wrapper.text()).toBe('false')
  })
})
