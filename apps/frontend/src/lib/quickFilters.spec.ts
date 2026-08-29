import { describe, expect, it } from 'vitest'
import { applyQuickFilters } from '@/lib/quickFilters'
import type { ListingDto } from '@/types'

function createListing(overrides: Partial<ListingDto> = {}): ListingDto {
  return {
    id: 1,
    dealType: 'sale',
    listingType: 'apartment',
    status: 'published',
    price: 145_000,
    pricePerSqm: 2500,
    rooms: 2,
    area: 58,
    floor: 7,
    totalFloors: 12,
    address: 'ул. Петра Мстиславца, 18',
    latitude: 53.9,
    longitude: 27.5,
    metroMinutes: 8,
    verified: true,
    aiGoodPrice: true,
    rentTerm: null,
    hasDeposit: false,
    utilitiesIncluded: false,
    noCommission: false,
    fromOwner: false,
    hasRenovation: false,
    views: 152,
    images: ['https://example.com/photo.jpg'],
    publishedAt: new Date().toISOString(),
    userId: 1,
    cityId: 1,
    districtId: 1,
    metroStationId: 1,
    ...overrides,
  }
}

describe('applyQuickFilters', () => {
  it('returns all listings when no quick filters are active', () => {
    const listings = [createListing(), createListing({ id: 2, images: [] })]
    expect(applyQuickFilters(listings, new Set())).toHaveLength(2)
  })

  it('filters listings without photos when withPhoto is active', () => {
    const listings = [
      createListing({ id: 1, images: ['https://example.com/photo.jpg'] }),
      createListing({ id: 2, images: [] }),
    ]

    const result = applyQuickFilters(listings, new Set(['withPhoto']))

    expect(result).toHaveLength(1)
    expect(result[0]?.id).toBe(1)
  })

  it('filters unverified listings when fromOwner is active', () => {
    const listings = [
      createListing({ id: 1, verified: true }),
      createListing({ id: 2, verified: false }),
    ]

    const result = applyQuickFilters(listings, new Set(['fromOwner']))

    expect(result).toHaveLength(1)
    expect(result[0]?.id).toBe(1)
  })
})
