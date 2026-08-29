import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  CATALOG_LIST_LIMIT,
  HOME_LIST_LIMIT,
  HOME_NATIONWIDE_LIST_LIMIT,
  useListingsStore,
} from '@/stores/listings'

vi.mock('@/api/listings', () => ({
  fetchListings: vi.fn().mockResolvedValue({ items: [], total: 0, page: 1, limit: 20 }),
  fetchListing: vi.fn(),
}))

vi.mock('@/api/reference', () => ({
  fetchCities: vi.fn().mockResolvedValue([]),
  fetchDistricts: vi.fn().mockResolvedValue([]),
  fetchMetroStations: vi.fn().mockResolvedValue([]),
}))

describe('useListingsStore home defaults', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('resetFilters on home clears city filter', () => {
    const store = useListingsStore()
    store.cityId = 42

    store.resetFilters()

    expect(store.cityId).toBeUndefined()
  })

  it('defaults rent term to long', () => {
    const store = useListingsStore()

    expect(store.rentTerm).toBe('long')
  })

  it('defaults listing type filter to empty', () => {
    const store = useListingsStore()

    expect(store.listingType).toBeUndefined()
    expect(store.catalogCategory).toBe('all')
  })

  it('resetFilters restores long-term rent', () => {
    const store = useListingsStore()
    store.rentTerm = 'daily'

    store.resetFilters()

    expect(store.rentTerm).toBe('long')
  })

  it('resetFilters in catalog mode keeps nationwide Belarus default', () => {
    const store = useListingsStore()
    store.setCatalogMode(true)
    store.cities = [
      { id: 1, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' },
      { id: 2, name: 'Брест', slug: 'brest-city', regionSlug: 'brest' },
    ]
    store.cityId = 1
    store.regionSlug = 'minsk-city'

    store.resetFilters()

    expect(store.cityId).toBeUndefined()
    expect(store.regionSlug).toBeUndefined()
    expect(store.mapNationwide).toBe(true)
  })

  it('limits home list to 10 items without map zoom', () => {
    const store = useListingsStore()

    expect(store.mapNationwide).toBe(true)
    expect(store.limit).toBe(HOME_NATIONWIDE_LIST_LIMIT)

    store.setMapNationwide(false)
    expect(store.limit).toBe(HOME_LIST_LIMIT)

    store.setMapNationwide(true)
    expect(store.limit).toBe(HOME_NATIONWIDE_LIST_LIMIT)
  })

  it('keeps catalog page size when toggling nationwide', () => {
    const store = useListingsStore()
    store.setCatalogMode(true)

    expect(store.limit).toBe(CATALOG_LIST_LIMIT)
    store.setMapNationwide(false)
    expect(store.limit).toBe(CATALOG_LIST_LIMIT)
  })

  it('initializes commercial catalog with listing type and sale deal', async () => {
    const store = useListingsStore()

    await store.initializeCommercialCatalog()

    expect(store.commercialCatalogActive).toBe(true)
    expect(store.listingType).toBe('commercial')
    expect(store.dealType).toBe('sale')
    expect(store.catalogBaseDealType).toBe('sale')
    expect(store.catalogCategory).toBe('commercial')
  })

  it('loads home list with random sort then restores newest for UI', async () => {
    const { fetchListings } = await import('@/api/listings')
    const store = useListingsStore()
    const calls: Array<Record<string, unknown>> = []

    vi.mocked(fetchListings).mockImplementation(async (params) => {
      calls.push({ ...params })
      return { items: [], total: 0, page: 1, limit: 10 }
    })

    await store.initializeHome()

    expect(calls.some((params) => params.sort === 'random')).toBe(true)
    expect(store.sort).toBe('newest')
    expect(calls.at(-1)?.sort).toBe('publishedAt')
  })

  it('keeps commercial listing type when switching deal type', async () => {
    const store = useListingsStore()

    await store.initializeCommercialCatalog()
    store.setDealType('rent')

    expect(store.dealType).toBe('rent')
    expect(store.catalogBaseDealType).toBe('rent')
    expect(store.listingType).toBe('commercial')
  })

  it('does not let an older marker request overwrite a newer district result', async () => {
    const { fetchListings } = await import('@/api/listings')
    const store = useListingsStore()
    let resolveFirst: ((value: any) => void) | undefined
    let call = 0

    vi.mocked(fetchListings).mockImplementation(async () => {
      call += 1
      if (call === 1) {
        return new Promise((resolve) => {
          resolveFirst = resolve
        })
      }

      return {
        items: [{ id: 2, latitude: 53.9, longitude: 27.56 }],
        total: 1,
        page: 1,
        limit: 100,
      } as any
    })

    const olderRequest = store.loadMapMarkers()
    await Promise.resolve()
    const newerRequest = store.loadMapMarkers()
    await newerRequest

    resolveFirst?.({
      items: [{ id: 1, latitude: 52.1, longitude: 23.7 }],
      total: 1,
      page: 1,
      limit: 100,
    })
    await olderRequest

    expect(store.mapItems.map((item) => item.id)).toEqual([2])
  })

  it('does not let an older search overwrite the latest district result', async () => {
    const { fetchListings } = await import('@/api/listings')
    const store = useListingsStore()
    let resolveFirst: ((value: any) => void) | undefined
    let call = 0

    store.setCatalogMode(true)
    vi.mocked(fetchListings).mockImplementation(async () => {
      call += 1
      if (call === 1) {
        return new Promise((resolve) => {
          resolveFirst = resolve
        })
      }

      return {
        items: [{ id: 2, latitude: 53.9, longitude: 27.56 }],
        total: 1,
        page: 1,
        limit: 6,
      } as any
    })

    const olderRequest = store.search()
    await Promise.resolve()
    const newerRequest = store.search()
    await newerRequest

    resolveFirst?.({
      items: [{ id: 1, latitude: 52.1, longitude: 23.7 }],
      total: 1,
      page: 1,
      limit: 6,
    })
    await olderRequest

    expect(store.items.map((item) => item.id)).toEqual([2])
    expect(store.error).toBeNull()
    expect(store.loading).toBe(false)
  })

  it('loads Minsk markers together with Minsk region cities without a conflicting region filter', async () => {
    const { fetchListings } = await import('@/api/listings')
    const store = useListingsStore()
    const calls: Array<Record<string, unknown>> = []

    store.regionSlug = 'minsk-region'
    vi.mocked(fetchListings).mockImplementation(async (params) => {
      calls.push({ ...params })
      return {
        items: [{ id: Number(params.cityId), latitude: 53.9, longitude: 27.56 }],
        total: 1,
        page: 1,
        limit: 100,
      } as any
    })

    await store.loadMapMarkersForCityIds([235, 236])

    expect(calls).toHaveLength(2)
    expect(calls.every((params) => params.regionSlug === undefined)).toBe(true)
    expect(store.mapItems.map((item) => item.id)).toEqual([235, 236])
  })

  it('keeps Minsk in the final marker set when search reloads Minsk region markers', async () => {
    const { fetchListings } = await import('@/api/listings')
    const store = useListingsStore()
    const calls: Array<Record<string, unknown>> = []

    store.cities = [
      { id: 235, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' },
      { id: 236, name: 'Борисов', slug: 'borisov', regionSlug: 'minsk-region' },
    ]
    store.regionSlug = 'minsk-region'
    vi.mocked(fetchListings).mockImplementation(async (params) => {
      calls.push({ ...params })
      return {
        items: [{ id: Number(params.cityId), latitude: 53.9, longitude: 27.56 }],
        total: 1,
        page: 1,
        limit: 100,
      } as any
    })

    await store.loadMapMarkers()

    expect(calls.map((params) => params.cityId)).toEqual([235, 236])
    expect(calls.every((params) => params.regionSlug === undefined)).toBe(true)
    expect(store.mapItems.map((item) => item.id)).toEqual([235, 236])
  })

  it('keeps markers from all city districts when one district is selected', async () => {
    const { fetchListings } = await import('@/api/listings')
    const store = useListingsStore()
    const calls: Array<Record<string, unknown>> = []

    store.cityId = 235
    store.regionSlug = 'minsk-city'
    store.districtId = 499
    vi.mocked(fetchListings).mockImplementation(async (params) => {
      calls.push({ ...params })
      return { items: [], total: 0, page: 1, limit: 100 }
    })

    await store.loadMapMarkers()

    expect(calls).toHaveLength(1)
    expect(calls[0]?.cityId).toBe(235)
    expect(calls[0]?.districtId).toBeUndefined()
  })
})
