import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as comparisonsApi from '@/api/comparisons'
import ComparisonPanel from '@/modules/collections/components/ComparisonPanel.vue'
import { i18n } from '@/modules/locale'
import type { ListingDto } from '@/types'

vi.mock('@/api/comparisons', () => ({
  fetchComparisons: vi.fn(),
  toggleComparison: vi.fn(),
  removeComparison: vi.fn(),
}))

const listing = {
  id: 10,
  dealType: 'sale',
  listingType: 'apartment',
  status: 'published',
  price: 100000,
  pricePerSqm: 2000,
  rooms: 2,
  area: 50,
  floor: 3,
  totalFloors: 9,
  address: 'Test street 1',
  latitude: 0,
  longitude: 0,
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
  publishedAt: '2026-01-01',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: null,
} as ListingDto

describe('ComparisonPanel', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders embedded variant without compact table', async () => {
    vi.mocked(comparisonsApi.fetchComparisons).mockResolvedValue([
      {
        id: 1,
        userId: null,
        listingId: listing.id,
        listing,
      },
    ])

    const wrapper = mount(ComparisonPanel, {
      props: { embedded: true },
      global: {
        plugins: [i18n],
      },
    })

    await flushPromises()

    expect(wrapper.classes()).toContain('comparison-panel--embedded')
    expect(wrapper.find('.comparison-panel__title').text()).toContain('Сравнение')
    expect(wrapper.find('.comparison-table').exists()).toBe(true)
    expect(wrapper.find('.comparison-table--compact').exists()).toBe(false)
  })
})
