import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useSiteSettingsStore } from '@/stores/siteSettings'

vi.mock('@/api/siteSettings', () => ({
  fetchSiteSettings: vi.fn().mockResolvedValue({
    id: 1,
    aboutText: 'О сайте тест',
    phoneDisplay: '+375 29 111-11-11',
    phoneRaw: '+375291111111',
    email: 'test@renlo.by',
    supportHours: '9-18',
    ownerName: 'Renlo Test',
    address: 'Minsk',
    offersText: 'Offers',
    offersEmail: 'ads@renlo.by',
    telegramUrl: 'https://t.me/renlo_test',
    whatsappUrl: 'https://wa.me/375291111111',
    vkUrl: 'https://vk.com/renlo_test',
    isTest: false,
  }),
}))

describe('useSiteSettingsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('loads settings and exposes contact helpers', async () => {
    const store = useSiteSettingsStore()
    await store.load()

    expect(store.aboutText).toBe('О сайте тест')
    expect(store.phoneDisplay).toBe('+375 29 111-11-11')
    expect(store.phoneHref).toBe('tel:+375291111111')
    expect(store.emailHref).toBe('mailto:test@renlo.by')
    expect(store.offersEmailHref).toBe('mailto:ads@renlo.by')
    expect(store.telegramUrl).toBe('https://t.me/renlo_test')
    expect(store.whatsappUrl).toBe('https://wa.me/375291111111')
    expect(store.vkUrl).toBe('https://vk.com/renlo_test')
  })
})
