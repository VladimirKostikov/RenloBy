import { flushPromises, mount } from '@vue/test-utils'
import { defineComponent, nextTick } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { useHeadSnippets } from '@/composables/useHeadSnippets'

const fetchHeadSnippets = vi.fn()
const injectHeadSnippetCodes = vi.fn()
const clearInjectedHeadSnippets = vi.fn()

vi.mock('@/api/headSnippets', () => ({
  fetchHeadSnippets: (...args: unknown[]) => fetchHeadSnippets(...args),
}))

vi.mock('@/lib/injectHeadSnippets', () => ({
  injectHeadSnippetCodes: (...args: unknown[]) => injectHeadSnippetCodes(...args),
  clearInjectedHeadSnippets: (...args: unknown[]) => clearInjectedHeadSnippets(...args),
}))

describe('useHeadSnippets', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    fetchHeadSnippets.mockResolvedValue([{ code: '<meta name="x" content="1">' }])
  })

  it('injects snippets on public routes and clears them in admin', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/', component: { template: '<div />' } },
        { path: '/admin/head-snippets', component: { template: '<div />' } },
      ],
    })
    await router.push('/')
    await router.isReady()

    const Host = defineComponent({
      setup() {
        useHeadSnippets()
        return () => null
      },
    })

    mount(Host, {
      global: { plugins: [router] },
    })
    await flushPromises()

    expect(fetchHeadSnippets).toHaveBeenCalled()
    expect(injectHeadSnippetCodes).toHaveBeenCalledWith(['<meta name="x" content="1">'])

    await router.push('/admin/head-snippets')
    await nextTick()
    await flushPromises()

    expect(clearInjectedHeadSnippets).toHaveBeenCalled()
  })
})
