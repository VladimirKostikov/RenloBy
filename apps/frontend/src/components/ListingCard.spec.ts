import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingCard from '@/components/ListingCard.vue'
import { __resetNowTickerForTests } from '@/composables/useNowTicker'
import ru from '@/locales/ru.json'
import type { ListingDto } from '@/types'

const listing: ListingDto = {
  id: 1,
  dealType: 'sale',
  listingType: 'apartment',
  status: 'published',
  price: 100000,
  pricePerSqm: 2000,
  rooms: 2,
  area: 50,
  floor: 3,
  totalFloors: 9,
  address: 'ул. Тестовая, 1',
  latitude: 53.9,
  longitude: 27.5,
  metroMinutes: null,
  verified: false,
  aiGoodPrice: false,
  rentTerm: null,
  hasDeposit: false,
  utilitiesIncluded: false,
  noCommission: false,
  fromOwner: false,
  hasRenovation: false,
  views: 12,
  images: [],
  publishedAt: '2026-07-16T10:00:00.000Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: null,
}

function mountCard() {
  const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
  setActivePinia(createPinia())

  return mount(ListingCard, {
    props: { listing },
    global: {
      plugins: [i18n],
      stubs: {
        teleport: true,
        ListingShareModal: true,
        ListingReportModal: true,
      },
    },
  })
}

describe('ListingCard published time', () => {
  afterEach(() => {
    vi.useRealTimers()
    __resetNowTickerForTests()
  })

  it('renders dynamic published ago instead of static locale text', async () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-07-16T12:00:00.000Z'))

    const wrapper = mountCard()
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('2 часа назад')
    expect(wrapper.text()).toContain('Продажа · Квартира')
  })

  it('keeps rooms area and floor as separate nowrap params', () => {
    const wrapper = mountCard()
    const params = wrapper.findAll('.listing-card__param')

    expect(wrapper.find('.listing-card__params').exists()).toBe(true)
    expect(params).toHaveLength(3)
    expect(params[0]?.text()).toBe('2-комн.')
    expect(params[1]?.text()).toBe('50 м²')
    expect(params[2]?.text()).toBe('3/9')
  })

  it('places price per sqm under photo params in one row', () => {
    const wrapper = mountCard()
    const media = wrapper.find('.listing-card__media')
    const sqm = media.find('.listing-card__sqm')
    const source = readFileSync(resolve(__dirname, './ListingCard.vue'), 'utf8')

    expect(sqm.exists()).toBe(true)
    expect(sqm.text()).toContain('BYN/м²')
    expect(sqm.text()).toContain('$/м²')
    expect(wrapper.find('.listing-card__price-row .listing-card__sqm').exists()).toBe(false)
    expect(source).toMatch(/\.listing-card__sqm\s*\{[^}]*flex-direction:\s*row/s)
    expect(source).toMatch(/\.listing-card__footer\s*\{[^}]*margin-top:\s*2px/s)
  })

  it('links to the standalone listing page from the media actions', () => {
    const wrapper = mountCard()
    const link = wrapper.find('.listing-card__page-link')

    expect(link.exists()).toBe(true)
    expect(link.attributes('href')).toBe('/listings/1')
    expect(link.attributes('aria-label')).toBe('Открыть страницу объявления')
  })
})

describe('ListingCard more menu', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('opens actions menu on three-dots click', async () => {
    const wrapper = mountCard()
    const menuBtn = wrapper.find('.listing-card__menu')

    expect(menuBtn.exists()).toBe(true)
    expect(wrapper.find('.listing-card__menu-panel').exists()).toBe(false)

    await menuBtn.trigger('click')

    const panel = wrapper.find('.listing-card__menu-panel')
    expect(panel.exists()).toBe(true)
    expect(panel.text()).toContain('Поделиться')
    expect(panel.text()).toContain('В сравнение')
    expect(panel.text()).toContain('Пожаловаться')
  })

  it('opens share modal from menu', async () => {
    const wrapper = mountCard()

    await wrapper.find('.listing-card__menu').trigger('click')
    await wrapper.findAll('.listing-card__menu-item')[0].trigger('click')

    expect(wrapper.find('.listing-card__menu-panel').exists()).toBe(false)
    expect(wrapper.findComponent({ name: 'ListingShareModal' }).props('open')).toBe(true)
  })

  it('opens report modal from menu', async () => {
    const wrapper = mountCard()

    await wrapper.find('.listing-card__menu').trigger('click')
    await wrapper.findAll('.listing-card__menu-item')[2].trigger('click')

    expect(wrapper.findComponent({ name: 'ListingReportModal' }).props('open')).toBe(true)
  })
})
