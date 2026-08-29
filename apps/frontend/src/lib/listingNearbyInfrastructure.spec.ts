import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as infrastructureApi from '@/api/infrastructure'
import {
  clearListingInfrastructureCache,
  fetchListingInfrastructureSummary,
  fetchListingNearbyPlaces,
  walkingMinutes,
} from '@/lib/listingNearbyInfrastructure'
import type { ListingDto } from '@/types'

vi.mock('@/api/infrastructure', () => ({
  fetchInfrastructurePois: vi.fn(),
}))

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

const fallbackNames = {
  shop: 'Магазин',
  pharmacy: 'Аптека',
  school: 'Школа',
  park: 'Парк',
}

describe('listingNearbyInfrastructure', () => {
  beforeEach(() => {
    clearListingInfrastructureCache()
    vi.clearAllMocks()
  })

  it('calculates walking minutes from coordinates', () => {
    const minutes = walkingMinutes(53.9, 27.5, 53.901, 27.501)
    expect(minutes).toBeGreaterThan(0)
    expect(minutes).toBeLessThan(5)
  })

  it('reuses cached pois for summary and nearby panel', async () => {
    vi.mocked(infrastructureApi.fetchInfrastructurePois).mockResolvedValue([
      {
        id: 'poi-1',
        type: 'shop',
        name: 'Евроопт',
        address: 'ул. Ленина, 1',
        latitude: 53.901,
        longitude: 27.501,
      },
    ])

    const summary = await fetchListingInfrastructureSummary(listing, undefined, fallbackNames)
    const nearby = await fetchListingNearbyPlaces(listing, fallbackNames)

    expect(summary[0]?.label).toBe('Евроопт')
    expect(nearby).toHaveLength(1)
    expect(infrastructureApi.fetchInfrastructurePois).toHaveBeenCalledTimes(1)
  })
})
