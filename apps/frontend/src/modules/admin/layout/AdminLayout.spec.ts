import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { createMemoryHistory, createRouter } from 'vue-router'
import AdminLayout from '@/modules/admin/layout/AdminLayout.vue'

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: vi.fn(),
}))

vi.mock('@/stores/adminTestMode', () => ({
  useAdminTestModeStore: () => ({
    isTest: true,
    enabled: true,
    confirmOpen: false,
    pendingEnabled: null,
    requestToggle: vi.fn(),
    confirmToggle: vi.fn(),
    cancelToggle: vi.fn(),
  }),
}))

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: {
    ru: {
      app: { name: 'Renlo' },
      admin: {
        title: 'Админка',
        backToSite: 'На сайт',
        testMode: 'Тестовый режим',
        testModeOn: 'Вкл',
        testModeOff: 'Выкл',
        testModeConfirm: 'Подтвердить',
        testModeCancel: 'Отмена',
        dashboard: 'Дашборд',
        listings: 'Объявления',
      },
    },
  },
})

describe('AdminLayout', () => {
  it('keeps sidebar fixed to viewport with scrollable nav', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/admin', name: 'admin', component: { template: '<div />' } },
        { path: '/', name: 'home', component: { template: '<div />' } },
      ],
    })
    await router.push('/admin')
    await router.isReady()

    const wrapper = mount(AdminLayout, {
      global: {
        plugins: [i18n, router],
        stubs: {
          RouterView: true,
          AppLogomark: true,
          AdminNavIcon: true,
          AdminTestModeToggle: { template: '<div class="test-mode-stub" />' },
        },
      },
    })

    expect(wrapper.find('.admin-layout__sidebar').exists()).toBe(true)
    expect(wrapper.find('.admin-layout__nav').exists()).toBe(true)
    expect(wrapper.find('.admin-layout__sidebar-footer').exists()).toBe(true)
    expect(wrapper.find('.admin-layout__back').exists()).toBe(true)
  })

  it('scrolls content to top when admin section changes', async () => {
    const windowScrollTo = vi.fn()
    vi.stubGlobal('scrollTo', windowScrollTo)

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/admin', name: 'admin', component: { template: '<div />' } },
        { path: '/admin/listings', name: 'admin-listings', component: { template: '<div />' } },
        { path: '/', name: 'home', component: { template: '<div />' } },
      ],
    })
    await router.push('/admin')
    await router.isReady()

    const wrapper = mount(AdminLayout, {
      attachTo: document.body,
      global: {
        plugins: [i18n, router],
        stubs: {
          RouterView: true,
          AppLogomark: true,
          AdminNavIcon: true,
          AdminTestModeToggle: { template: '<div class="test-mode-stub" />' },
        },
      },
    })

    const content = wrapper.get('.admin-layout__content').element as HTMLElement
    const contentScrollTo = vi.fn()
    content.scrollTo = contentScrollTo as unknown as typeof content.scrollTo
    content.scrollTop = 320

    await router.push('/admin/listings')
    await flushPromises()
    await Promise.resolve()

    expect(contentScrollTo).toHaveBeenCalledWith({ top: 0, left: 0 })
    expect(windowScrollTo).toHaveBeenCalledWith({ top: 0, left: 0 })

    wrapper.unmount()
    vi.unstubAllGlobals()
  })
})
