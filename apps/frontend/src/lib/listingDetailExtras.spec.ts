import { describe, expect, it } from 'vitest'
import {
  buildListingDealConditions,
  buildListingDetailExtras,
  findSimilarListings,
  resolveCharacteristicText,
  SIMILAR_LISTINGS_LIMIT,
} from '@/lib/listingDetailExtras'
import type { ListingDto } from '@/types'

const listing: ListingDto = {
  id: 7,
  dealType: 'sale',
  listingType: 'apartment',
  status: 'published',
  price: 145000,
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
  priceNegotiable: false,
  views: 152,
  images: [],
  publishedAt: '2026-01-01T00:00:00Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: 1,
}

function makeListing(id: number, overrides: Partial<ListingDto> = {}): ListingDto {
  return { ...listing, id, ...overrides }
}

describe('listingDetailExtras', () => {
  it('builds only real characteristics from listing data', () => {
    const extras = buildListingDetailExtras(listing)

    expect(extras.characteristics.map((row) => row.label)).toEqual([
      'listingDetail.rooms',
      'listingDetail.floor',
      'listingDetail.totalArea',
      'listingDetail.objectType',
    ])
    expect(extras.characteristics.find((row) => row.label === 'listingDetail.floor')?.value).toBe('7/12')
    expect(extras.characteristics.find((row) => row.label === 'listingDetail.rooms')?.value).toBe('2')
    expect(
      extras.characteristics.find((row) => row.label === 'listingDetail.objectType')?.value,
    ).toBe('listingType.apartment')
    expect(extras.description).toBe('')
    expect(extras.infrastructure).toEqual([])
  })

  it('shows studio label for zero rooms', () => {
    const extras = buildListingDetailExtras({ ...listing, rooms: 0 })

    expect(extras.characteristics.find((row) => row.label === 'listingDetail.rooms')?.value).toBe(
      'listing.studio',
    )
  })

  it('skips missing floor and keeps rooms and area', () => {
    const extras = buildListingDetailExtras(
      makeListing(8, { floor: null, totalFloors: null, hasRenovation: true, fromOwner: true }),
    )

    expect(extras.characteristics.map((row) => row.label)).toEqual([
      'listingDetail.rooms',
      'listingDetail.totalArea',
      'listingDetail.objectType',
    ])
  })

  it('builds deal conditions from listing flags', () => {
    const sale = buildListingDealConditions(listing)
    expect(sale.map((row) => row.label)).toEqual([
      'listingDetail.ownerType',
      'listingDetail.commission',
      'listingDetail.renovation',
      'listingDetail.priceNegotiable',
    ])
    expect(sale.find((row) => row.label === 'listingDetail.ownerType')?.value).toBe(
      'listingDetail.ownerTypeAgent',
    )

    const rent = buildListingDealConditions(
      makeListing(9, {
        dealType: 'rent',
        rentTerm: 'long',
        hasDeposit: true,
        utilitiesIncluded: true,
        fromOwner: true,
        noCommission: true,
        hasRenovation: true,
        priceNegotiable: true,
      }),
    )

    expect(rent.map((row) => row.label)).toEqual([
      'listingDetail.landlordType',
      'listingDetail.commission',
      'listingDetail.renovation',
      'listingDetail.priceNegotiable',
      'listingDetail.rentTerm',
      'listingDetail.deposit',
      'listingDetail.utilitiesIncluded',
    ])
    expect(rent.find((row) => row.label === 'listingDetail.rentTerm')?.value).toBe(
      'listingDetail.rentTerms.long',
    )
    expect(rent.find((row) => row.label === 'listingDetail.deposit')?.value).toBe('listingDetail.yes')
  })

  it('resolves characteristic i18n keys and keeps plain values', () => {
    const translate = (key: string) => {
      const map: Record<string, string> = {
        'listingDetail.renovationYes': 'Есть',
        'listingDetail.ownerTypeOwner': 'Собственник',
        'listing.studio': 'Студия',
        'listingType.apartment': 'Квартира',
      }
      return map[key] ?? key
    }

    expect(resolveCharacteristicText('listingDetail.renovationYes', translate)).toBe('Есть')
    expect(resolveCharacteristicText('listingDetail.ownerTypeOwner', translate)).toBe('Собственник')
    expect(resolveCharacteristicText('listing.studio', translate)).toBe('Студия')
    expect(resolveCharacteristicText('listingType.apartment', translate)).toBe('Квартира')
    expect(resolveCharacteristicText('58 м²', translate)).toBe('58 м²')
    expect(resolveCharacteristicText('7/12', translate)).toBe('7/12')
  })

  it('fills similar listings up to at least four from broader pool', () => {
    const pool = [
      makeListing(1, { cityId: 2, rooms: 1, dealType: 'rent' }),
      makeListing(2, { cityId: 1, rooms: 3, dealType: 'sale' }),
      makeListing(3, { cityId: 3, rooms: 2, dealType: 'sale' }),
      makeListing(4, { cityId: 4, rooms: 4, listingType: 'commercial' }),
      makeListing(5, { cityId: 1, rooms: 2, dealType: 'sale' }),
      makeListing(7),
    ]

    const similar = findSimilarListings(listing, pool)

    expect(similar).toHaveLength(SIMILAR_LISTINGS_LIMIT)
    expect(similar.map((item) => item.id)).toEqual([5, 2, 3, 4])
    expect(similar.every((item) => item.id !== listing.id)).toBe(true)
  })
})
