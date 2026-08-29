import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as favoritesApi from '@/api/favorites'
import { useFavoritesStore } from '@/stores/favorites'
import { useToastStore } from '@/stores/toast'
import { i18n } from '@/modules/locale'

vi.mock('@/api/favorites', () => ({
  fetchFavorites: vi.fn(),
  toggleFavorite: vi.fn(),
  removeFavorite: vi.fn(),
}))

describe('useFavoritesStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('loads favorites and tracks listing ids', async () => {
    vi.mocked(favoritesApi.fetchFavorites).mockResolvedValue([
      {
        id: 1,
        userId: null,
        listingId: 10,
        listing: {
          id: 10,
          dealType: 'sale',
          listingType: 'apartment',
          status: 'published',
          price: 100000,
          pricePerSqm: 1000,
          rooms: 2,
          area: 50,
          floor: 3,
          totalFloors: 9,
          address: 'Test',
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
          views: 0,
          images: [],
          publishedAt: '2026-01-01',
          userId: 1,
          cityId: 1,
          districtId: 1,
          metroStationId: null,
        },
      },
    ])

    const store = useFavoritesStore()
    await store.load()

    expect(store.count).toBe(1)
    expect(store.isFavorite(10)).toBe(true)
    expect(store.listings).toHaveLength(1)
  })

  it('toggles favorite state from api response', async () => {
    vi.mocked(favoritesApi.toggleFavorite).mockResolvedValue({
      active: true,
      item: { id: 5, userId: null, listingId: 15 },
    })

    const store = useFavoritesStore()
    await store.toggle(15, {
      id: 15,
      dealType: 'rent',
      listingType: 'apartment',
      status: 'published',
      price: 500,
      pricePerSqm: 10,
      rooms: 1,
      area: 40,
      floor: 2,
      totalFloors: 5,
      address: 'Rent',
      latitude: 0,
      longitude: 0,
      metroMinutes: null,
      verified: false,
      aiGoodPrice: false,
      rentTerm: 'long',
      hasDeposit: false,
      utilitiesIncluded: false,
      noCommission: false,
      fromOwner: false,
      hasRenovation: false,
      views: 0,
      images: [],
      publishedAt: '2026-01-01',
      userId: 1,
      cityId: 1,
      districtId: 1,
      metroStationId: null,
    })

    expect(store.isFavorite(15)).toBe(true)
    expect(store.count).toBe(1)
    expect(store.favoriteListingIds).toEqual([15])

    const toast = useToastStore()
    expect(toast.visible).toBe(true)
    expect(toast.message).toBe(i18n.global.t('collections.favorites.added'))
  })

  it('does not toast when favorite is removed', async () => {
    vi.mocked(favoritesApi.toggleFavorite).mockResolvedValue({
      active: false,
      item: null,
    })

    const store = useFavoritesStore()
    await store.toggle(15)

    const toast = useToastStore()
    expect(toast.visible).toBe(false)
  })
})
