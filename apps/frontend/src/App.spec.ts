import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it, vi } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
import App from '@/App.vue'
import { i18n } from '@/modules/locale'

vi.mock('@/modules/auth/components/AuthModal.vue', () => ({
  default: { name: 'AuthModal', template: '<div class="auth-modal-stub" />' },
}))

vi.mock('@/modules/auth/components/AuthSuccessModal.vue', () => ({
  default: { name: 'AuthSuccessModal', template: '<div class="auth-success-stub" />' },
}))

vi.mock('@/modules/consent/CookieConsentBanner.vue', () => ({
  default: { name: 'CookieConsentBanner', template: '<div class="cookie-consent-stub" />' },
}))

async function mountApp(path: string) {
  const pinia = createPinia()
  setActivePinia(pinia)

  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div class="page-home" />' } },
      { path: '/articles', name: 'articles', component: { template: '<div class="page-articles" />' } },
      { path: '/search', name: 'search-map', component: { template: '<div class="page-search" />' } },
      { path: '/admin', name: 'admin', component: { template: '<div class="page-admin" />' } },
      {
        path: '/admin/:pathMatch(.*)*',
        name: 'admin-catch',
        component: { template: '<div class="page-admin" />' },
      },
    ],
  })

  await router.push(path)
  await router.isReady()

  return mount(App, {
    global: {
      plugins: [i18n, router, pinia],
      stubs: {
        HeaderTopBar: true,
        HeaderSearchField: true,
        AppLogomark: true,
      },
    },
  })
}

describe('App shell', () => {
  it('shows sticky header and footer on public pages', async () => {
    const wrapper = await mountApp('/articles')
    await flushPromises()

    expect(wrapper.find('.home-header').exists()).toBe(true)
    expect(wrapper.find('.home-header--sticky').exists()).toBe(true)
    expect(wrapper.find('.app-footer').exists()).toBe(true)
    expect(wrapper.find('.app-shell--public').exists()).toBe(true)
    expect(wrapper.find('.app-shell__main').exists()).toBe(true)
    expect(wrapper.find('.page-articles').exists()).toBe(true)
  })

  it('keeps main slot between header and footer while route resolves', async () => {
    const wrapper = await mountApp('/articles')
    await flushPromises()

    const shell = wrapper.find('.app-shell')
    const main = wrapper.find('.app-shell__main')
    const footer = wrapper.find('.app-footer')

    expect(shell.classes()).toContain('app-shell--public')
    expect(main.exists()).toBe(true)
    expect(footer.exists()).toBe(true)

    const children = shell.element.children
    const tags = Array.from(children).map((el) => (el as HTMLElement).className)
    expect(tags.some((c) => c.includes('home-header'))).toBe(true)
    expect(tags.some((c) => c.includes('app-shell__main'))).toBe(true)
    expect(tags.some((c) => c.includes('app-footer'))).toBe(true)

    const mainIndex = Array.from(children).findIndex((el) =>
      (el as HTMLElement).classList.contains('app-shell__main'),
    )
    const footerIndex = Array.from(children).findIndex((el) =>
      (el as HTMLElement).classList.contains('app-footer'),
    )
    expect(mainIndex).toBeLessThan(footerIndex)
  })

  it('hides public header and footer on admin routes', async () => {
    const wrapper = await mountApp('/admin')
    await flushPromises()

    expect(wrapper.find('.home-header').exists()).toBe(false)
    expect(wrapper.find('.app-footer').exists()).toBe(false)
    expect(wrapper.find('.page-admin').exists()).toBe(true)
  })

  it('locks search map page to viewport without footer', async () => {
    const wrapper = await mountApp('/search')
    await flushPromises()

    expect(wrapper.find('.app-shell--viewport').exists()).toBe(true)
    expect(wrapper.find('.home-header').exists()).toBe(true)
    expect(wrapper.find('.app-footer').exists()).toBe(false)
    expect(wrapper.find('.page-search').exists()).toBe(true)
  })

  it('keeps main container ready for route preloader', async () => {
    const wrapper = await mountApp('/')
    await flushPromises()

    const { default: ContainerPreloader } = await import('@/components/ContainerPreloader.vue')
    expect(wrapper.findComponent(ContainerPreloader).exists()).toBe(true)
  })
})
