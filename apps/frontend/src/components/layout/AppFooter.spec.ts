import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
import AppFooter from '@/components/layout/AppFooter.vue'
import { i18n } from '@/modules/locale'

vi.mock('@/api/siteSettings', () => ({
  fetchSiteSettings: vi.fn().mockResolvedValue({
    id: 1,
    aboutText: 'Агрегатор покупки, продажи и аренды квартир в Беларуси.',
    phoneDisplay: '+375 29 000-00-00',
    phoneRaw: '+375290000000',
    email: 'support@renlo.by',
    supportHours: 'Ежедневно 9:00-18:00',
    ownerName: 'Renlo',
    address: 'Минск',
    offersText: null,
    offersEmail: 'partners@renlo.by',
    telegramUrl: 'https://t.me/renlo_bot',
    whatsappUrl: 'https://wa.me/375290000000',
    vkUrl: 'https://vk.com/renlo',
    isTest: false,
  }),
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: { template: '<div />' } }],
})

describe('AppFooter', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders footer sections and key links', async () => {
    await router.push('/')
    await router.isReady()

    const pinia = createPinia()
    const wrapper = mount(AppFooter, {
      global: {
        plugins: [i18n, router, pinia],
      },
    })

    await flushPromises()

    expect(wrapper.find('.app-footer').exists()).toBe(true)
    expect(wrapper.text()).toContain('Renlo')
    expect(wrapper.text()).toContain('Продажа')
    expect(wrapper.text()).toContain('Безопасность сделок')
    expect(wrapper.text()).toContain('Карта объявлений')
    expect(wrapper.text()).toContain('Подать объявление')
    expect(wrapper.text()).toContain('Продвижение объявлений')
    expect(wrapper.find('a[href="tel:+375290000000"]').exists()).toBe(true)
    expect(wrapper.find('a[href="mailto:support@renlo.by"]').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('Предложения:')
    expect(wrapper.find('a[href="mailto:partners@renlo.by"]').exists()).toBe(false)
    expect(wrapper.find('a[href="/sale"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/info/deal-safety"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/info/offer"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/info/privacy"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/info/personal-data"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Договор оферты')
    expect(wrapper.text()).toContain('Политика конфиденциальности')
    expect(wrapper.text()).toContain('Персональные данные')
    expect(wrapper.find('.theme-menu__trigger').exists()).toBe(true)
    expect(wrapper.find('.theme-menu--on-dark').exists()).toBe(true)
    expect(wrapper.find('.app-footer__inner').exists()).toBe(true)
  })
})
