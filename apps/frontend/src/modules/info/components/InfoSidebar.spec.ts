import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import InfoSidebar from '@/modules/info/components/InfoSidebar.vue'
import { i18n } from '@/modules/locale'
import type { InfoPageDto } from '@/types/info'

vi.mock('@/api/siteSettings', () => ({
  fetchSiteSettings: vi.fn().mockResolvedValue({
    id: 1,
    aboutText: 'About',
    phoneDisplay: '+375 29 000-00-00',
    phoneRaw: '+375290000000',
    email: 'support@renlo.by',
    supportHours: 'Ежедневно 9:00-18:00',
    ownerName: 'Renlo',
    address: null,
    offersText: null,
    offersEmail: null,
    telegramUrl: null,
    whatsappUrl: null,
    vkUrl: null,
    isTest: false,
  }),
}))

const pages: InfoPageDto[] = [
  {
    id: 1,
    slug: 'buyers',
    title: 'Покупателям',
    body: '',
    category: 'buyers',
    importantNote: null,
    faqItems: [],
    sortOrder: 1,
    updatedAt: '2026-01-01T00:00:00Z',
    metaTitle: null,
    metaDescription: null,
  },
  {
    id: 2,
    slug: 'sellers',
    title: 'Продавцам',
    body: '',
    category: 'sellers',
    importantNote: null,
    faqItems: [],
    sortOrder: 2,
    updatedAt: '2026-01-01T00:00:00Z',
    metaTitle: null,
    metaDescription: null,
  },
  {
    id: 3,
    slug: 'faq',
    title: 'FAQ',
    body: '',
    category: 'faq',
    importantNote: null,
    faqItems: [],
    sortOrder: 3,
    updatedAt: '2026-01-01T00:00:00Z',
    metaTitle: null,
    metaDescription: null,
  },
]

describe('InfoSidebar', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders nav icons for title and pages', async () => {
    const pinia = createPinia()
    const wrapper = mount(InfoSidebar, {
      props: {
        pages,
        activeSlug: 'buyers',
      },
      global: {
        plugins: [i18n, pinia],
      },
    })

    await flushPromises()

    expect(wrapper.find('.info-sidebar__title-icon').exists()).toBe(true)
    expect(wrapper.findAll('.info-sidebar__link-icon')).toHaveLength(3)
    expect(wrapper.find('.info-sidebar__link--active').text()).toContain('Покупателям')
    expect(wrapper.find('a[href="tel:+375290000000"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('+375 29 000-00-00')
  })
})
