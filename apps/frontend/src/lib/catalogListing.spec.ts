import { describe, expect, it } from 'vitest'
import {
  isCatalogTopListing,
  resolveCategoryDealType,
  resolveCategoryListingType,
} from '@/lib/catalogListing'
import type { ListingDto } from '@/types'

const baseListing: ListingDto = {
  id: 1,
  dealType: 'rent',
  listingType: 'apartment',
  status: 'published',
  price: 500,
  pricePerSqm: 10,
  rooms: 2,
  area: 50,
  floor: 5,
  totalFloors: 12,
  address: 'Test',
  latitude: 53.9,
  longitude: 27.5,
  metroMinutes: 8,
  verified: false,
  aiGoodPrice: false,
  rentTerm: null,
  hasDeposit: false,
  utilitiesIncluded: false,
  noCommission: false,
  fromOwner: false,
  hasRenovation: false,
  priceNegotiable: false,
  views: 10,
  images: [],
  publishedAt: '2026-07-14T00:00:00Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: 1,
}

describe('catalogListing', () => {
  it('marks verified and ai listings as top', () => {
    expect(isCatalogTopListing(baseListing)).toBe(false)
    expect(isCatalogTopListing({ ...baseListing, verified: true })).toBe(true)
    expect(isCatalogTopListing({ ...baseListing, aiGoodPrice: true })).toBe(true)
  })

  it('resolves category listing type', () => {
    expect(resolveCategoryListingType('all')).toBeUndefined()
    expect(resolveCategoryListingType('apartment')).toBe('apartment')
    expect(resolveCategoryListingType('commercial')).toBe('commercial')
  })

  it('resolves category deal type without commercial', () => {
    expect(resolveCategoryDealType('apartment', 'rent')).toBe('rent')
    expect(resolveCategoryDealType('apartment', 'sale')).toBe('sale')
    expect(resolveCategoryDealType('commercial', 'sale')).toBe('sale')
    expect(resolveCategoryDealType('commercial', 'rent')).toBe('rent')
    expect(resolveCategoryDealType('all', 'rent')).toBe('rent')
  })
})
