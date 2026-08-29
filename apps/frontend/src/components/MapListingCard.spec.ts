import { createPinia, setActivePinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import MapListingCard from '@/components/MapListingCard.vue'
import type { ListingDto } from '@/types'
import ru from '@/locales/ru.json'

vi.mock('@/stores/aiAssistant', () => ({
  useAiAssistantStore: () => ({
    isRecommended: () => false,
  }),
}))

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: { ru },
})

function listingStub(overrides: Partial<ListingDto> = {}): ListingDto {
  return {
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
    priceNegotiable: false,
    views: 0,
    images: [],
    publishedAt: '2026-01-01',
    userId: 1,
    cityId: 1,
    districtId: 1,
    metroStationId: null,
    ...overrides,
  }
}

describe('MapListingCard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('does not show endless loader when listing has no photos', async () => {
    const wrapper = mount(MapListingCard, {
      props: {
        listing: listingStub({ images: [] }),
        left: 100,
        top: 100,
      },
      global: { plugins: [i18n] },
    })

    await flushPromises()

    expect(wrapper.find('.map-card__media-loader').exists()).toBe(false)
    expect(wrapper.find('.listing-image-slider__image--placeholder').exists()).toBe(true)
  })

  it('shows listing image slider with pagination for multiple photos', async () => {
    const wrapper = mount(MapListingCard, {
      props: {
        listing: listingStub({ images: ['https://example.com/a.jpg', 'https://example.com/b.jpg'] }),
        left: 100,
        top: 100,
      },
      global: { plugins: [i18n] },
    })

    await flushPromises()

    expect(wrapper.find('.listing-image-slider').exists()).toBe(true)
    expect(wrapper.findAll('.listing-image-slider__dot')).toHaveLength(2)
    expect(wrapper.find('.map-card__media-loader').exists()).toBe(false)
  })
})
