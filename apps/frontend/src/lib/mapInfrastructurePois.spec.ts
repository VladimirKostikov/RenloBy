import { describe, expect, it } from 'vitest'
import { buildInfrastructurePoisFromListings } from '@/lib/mapInfrastructurePois'
import type { ListingDto } from '@/types'

const listing: ListingDto = {
  id: 42,
  dealType: 'sale',
  listingType: 'apartment',
  status: 'published',
  price: 100000,
  pricePerSqm: 2000,
  rooms: 2,
  area: 50,
  floor: 5,
  totalFloors: 10,
  address: 'ул. Ленина, 10',
  latitude: 53.9045,
  longitude: 27.5615,
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
  publishedAt: '2026-01-01T00:00:00Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: null,
}

describe('buildInfrastructurePoisFromListings', () => {
  it('creates pois only from visible map listings', () => {
    const bounds = { south: 53.90, west: 27.55, north: 53.91, east: 27.57 }
    const pois = buildInfrastructurePoisFromListings(
      [listing],
      ['pharmacy'],
      { shop: 'Магазин', pharmacy: 'Аптека', school: 'Школа', park: 'Парк' },
      bounds,
    )

    expect(pois).toHaveLength(1)
    expect(pois[0]?.latitude).toBe(listing.latitude)
    expect(pois[0]?.longitude).toBe(listing.longitude)
    expect(pois[0]?.address).toBe(listing.address)
  })

  it('skips listings outside viewport bounds', () => {
    const bounds = { south: 52.0, west: 23.0, north: 52.1, east: 23.1 }
    const pois = buildInfrastructurePoisFromListings(
      [listing],
      ['shop'],
      { shop: 'Магазин', pharmacy: 'Аптека', school: 'Школа', park: 'Парк' },
      bounds,
    )

    expect(pois).toHaveLength(0)
  })
})
