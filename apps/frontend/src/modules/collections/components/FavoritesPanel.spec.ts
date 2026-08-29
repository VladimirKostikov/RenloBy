import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import * as favoritesApi from '@/api/favorites'
import FavoritesPanel from '@/modules/collections/components/FavoritesPanel.vue'
import { i18n } from '@/modules/locale'
import type { ListingDto } from '@/types'

vi.mock('@/api/favorites', () => ({
  fetchFavorites: vi.fn(),
  toggleFavorite: vi.fn(),
  removeFavorite: vi.fn(),
}))

vi.mock('@/api/comparisons', () => ({
  fetchComparisons: vi.fn().mockResolvedValue([]),
  toggleComparison: vi.fn(),
  removeComparison: vi.fn(),
}))

vi.mock('@/modules/collections/syncUserCollections', () => ({
  syncUserCollections: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('@/api/reference', () => ({
  fetchCities: vi.fn().mockResolvedValue([]),
  fetchDistricts: vi.fn().mockResolvedValue([]),
  fetchMetroStations: vi.fn().mockResolvedValue([]),
}))

vi.mock('@/stores/listings', async () => {
  const { defineStore } = await import('pinia')
  const { ref } = await import('vue')

  return {
    useListingsStore: defineStore('listings', () => ({
      cities: ref([]),
      districts: ref([]),
      metroStations: ref([]),
      initialize: vi.fn().mockResolvedValue(undefined),
    })),
  }
})

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

describe('FavoritesPanel', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders embedded variant inside account layout content', async () => {
    vi.mocked(favoritesApi.fetchFavorites).mockResolvedValue([])

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/user/favorites', component: { template: '<div />' } }],
    })
    await router.push('/account/user/favorites')
    await router.isReady()

    const wrapper = mount(FavoritesPanel, {
      props: { embedded: true },
      global: {
        plugins: [i18n, router],
      },
    })

    await flushPromises()

    expect(wrapper.classes()).toContain('favorites-panel--embedded')
    expect(wrapper.find('.favorites-panel__title').text()).toContain('Избранное')
  })

  it('renders catalog grid cards for favorites', async () => {
    vi.mocked(favoritesApi.fetchFavorites).mockResolvedValue([
      {
        id: 1,
        userId: null,
        listingId: listing.id,
        listing,
      },
    ])

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/user/favorites', component: { template: '<div />' } },
        { path: '/', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/user/favorites')
    await router.isReady()

    const wrapper = mount(FavoritesPanel, {
      props: { embedded: true },
      global: {
        plugins: [i18n, router],
      },
    })

    await flushPromises()

    expect(wrapper.find('.favorites-panel__grid').exists()).toBe(true)
    expect(wrapper.findAll('.catalog-card')).toHaveLength(1)
    expect(wrapper.find('.catalog-card--compact').exists()).toBe(false)
    expect(wrapper.find('.favorites-panel__count').text()).toBe('1')
    expect(wrapper.find('.listing-card').exists()).toBe(false)
  })
})
