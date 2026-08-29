import { describe, expect, it, vi } from 'vitest'
import { applyListingsSearchLocation } from '@/lib/applyListingsSearchLocation'

describe('applyListingsSearchLocation', () => {
  it('applies region and clears city/district', async () => {
    const listings = {
      regionSlug: undefined as string | undefined,
      cityId: 1 as number | undefined,
      districtId: 2 as number | undefined,
      searchQuery: 'x',
      loadDistricts: vi.fn(),
      search: vi.fn().mockResolvedValue(undefined),
    }

    await applyListingsSearchLocation(listings as never, {
      kind: 'region',
      regionSlug: 'vitebsk',
      label: 'Витебская область',
    })

    expect(listings.regionSlug).toBe('vitebsk')
    expect(listings.cityId).toBeUndefined()
    expect(listings.districtId).toBeUndefined()
    expect(listings.searchQuery).toBe('')
    expect(listings.loadDistricts).not.toHaveBeenCalled()
    expect(listings.search).toHaveBeenCalled()
  })

  it('applies city and loads districts', async () => {
    const listings = {
      regionSlug: undefined as string | undefined,
      cityId: undefined as number | undefined,
      districtId: 2 as number | undefined,
      searchQuery: 'x',
      loadDistricts: vi.fn().mockResolvedValue(undefined),
      search: vi.fn().mockResolvedValue(undefined),
    }

    await applyListingsSearchLocation(listings as never, {
      kind: 'city',
      cityId: 5,
      regionSlug: 'minsk-city',
      label: 'Минск',
    })

    expect(listings.cityId).toBe(5)
    expect(listings.regionSlug).toBe('minsk-city')
    expect(listings.districtId).toBeUndefined()
    expect(listings.loadDistricts).toHaveBeenCalledWith(5)
    expect(listings.search).toHaveBeenCalled()
  })

  it('applies district', async () => {
    const listings = {
      regionSlug: undefined as string | undefined,
      cityId: undefined as number | undefined,
      districtId: undefined as number | undefined,
      searchQuery: '',
      loadDistricts: vi.fn().mockResolvedValue(undefined),
      search: vi.fn().mockResolvedValue(undefined),
    }

    await applyListingsSearchLocation(listings as never, {
      kind: 'district',
      cityId: 1,
      districtId: 9,
      regionSlug: 'minsk-city',
      label: 'Центральный',
    })

    expect(listings.cityId).toBe(1)
    expect(listings.districtId).toBe(9)
    expect(listings.loadDistricts).toHaveBeenCalledWith(1)
  })
})
