import { describe, expect, it } from 'vitest'
import {
  findBestComparisonIndexes,
  isBestComparisonValue,
} from '@/lib/comparisonHighlights'
import type { ListingDto } from '@/types'

function listing(overrides: Partial<ListingDto>): ListingDto {
  return {
    id: 1,
    dealType: 'rent',
    listingType: 'apartment',
    status: 'published',
    price: 1000,
    pricePerSqm: 20,
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

describe('comparisonHighlights', () => {
  it('marks lowest price and price per sqm as best', () => {
    const listings = [
      listing({ id: 1, price: 980, pricePerSqm: 9 }),
      listing({ id: 2, price: 520, pricePerSqm: 10 }),
      listing({ id: 3, price: 650, pricePerSqm: 9 }),
    ]

    expect([...findBestComparisonIndexes(listings, 'price')]).toEqual([1])
    expect(isBestComparisonValue(listings, 'pricePerSqm', 0)).toBe(true)
    expect(isBestComparisonValue(listings, 'pricePerSqm', 1)).toBe(false)
    expect(isBestComparisonValue(listings, 'pricePerSqm', 2)).toBe(true)
  })

  it('marks largest area and rooms as best', () => {
    const listings = [
      listing({ id: 1, area: 61, rooms: 2 }),
      listing({ id: 2, area: 110, rooms: 4 }),
      listing({ id: 3, area: 82, rooms: 3 }),
    ]

    expect([...findBestComparisonIndexes(listings, 'area')]).toEqual([1])
    expect([...findBestComparisonIndexes(listings, 'rooms')]).toEqual([1])
  })

  it('does not highlight when all values are equal or row is not comparable', () => {
    const listings = [
      listing({ id: 1, price: 500, area: 50 }),
      listing({ id: 2, price: 500, area: 50 }),
    ]

    expect(findBestComparisonIndexes(listings, 'price').size).toBe(0)
    expect(findBestComparisonIndexes(listings, 'address').size).toBe(0)
    expect(findBestComparisonIndexes([listings[0]], 'price').size).toBe(0)
  })
})
