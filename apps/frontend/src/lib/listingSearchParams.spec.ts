import { describe, expect, it } from 'vitest'
import { buildListingSearchParams } from '@/lib/listingSearchParams'

const baseFilters = {
  dealType: 'sale' as const,
  listingType: undefined,
  cityId: 1,
  districtId: undefined,
  rooms: undefined,
  floor: undefined,
  minArea: undefined,
  maxArea: undefined,
  minPrice: undefined,
  maxPrice: undefined,
  verifiedOnly: false,
  searchQuery: '',
  rentTerm: 'daily' as const,
  rentDeposit: false,
  rentUtilitiesIncluded: false,
  rentNoCommission: false,
  saleNoAgents: false,
  saleFromOwner: false,
  saleWithRenovation: false,
}

describe('buildListingSearchParams', () => {
  it('maps rent toggles to API params', () => {
    const params = buildListingSearchParams(
      {
        ...baseFilters,
        dealType: 'rent',
        verifiedOnly: true,
        rentDeposit: true,
        rentUtilitiesIncluded: true,
        rentNoCommission: true,
        rentTerm: 'long',
        searchQuery: '  Михалово ',
      },
      1,
      20,
    )

    expect(params.verified).toBe(true)
    expect(params.hasDeposit).toBe(true)
    expect(params.utilitiesIncluded).toBe(true)
    expect(params.rentTerm).toBe('long')
    expect(params.noCommission).toBe(true)
    expect(params.query).toBe('Михалово')
    expect(params.fromOwner).toBeUndefined()
    expect(params.hasRenovation).toBeUndefined()
  })

  it('maps sale toggles to API params', () => {
    const params = buildListingSearchParams(
      {
        ...baseFilters,
        saleNoAgents: true,
        saleFromOwner: true,
        saleWithRenovation: true,
      },
      2,
      6,
    )

    expect(params.noCommission).toBe(true)
    expect(params.fromOwner).toBe(true)
    expect(params.hasRenovation).toBe(true)
    expect(params.rentTerm).toBeUndefined()
    expect(params.page).toBe(2)
    expect(params.limit).toBe(6)
  })

  it('maps sort options to API sort and direction', () => {
    const cheapest = buildListingSearchParams({ ...baseFilters, sort: 'priceAsc' }, 1, 20)
    expect(cheapest.sort).toBe('price')
    expect(cheapest.direction).toBe('ASC')

    const popular = buildListingSearchParams({ ...baseFilters, sort: 'viewsDesc' }, 1, 20)
    expect(popular.sort).toBe('views')
    expect(popular.direction).toBe('DESC')

    const random = buildListingSearchParams({ ...baseFilters, sort: 'random' }, 1, 10)
    expect(random.sort).toBe('random')
  })

  it('sends regionSlug only when city is not selected', () => {
    const byRegion = buildListingSearchParams(
      { ...baseFilters, cityId: undefined, regionSlug: 'gomel' },
      1,
      20,
    )
    expect(byRegion.regionSlug).toBe('gomel')
    expect(byRegion.cityId).toBeUndefined()

    const byCity = buildListingSearchParams(
      { ...baseFilters, cityId: 5, regionSlug: 'gomel' },
      1,
      20,
    )
    expect(byCity.cityId).toBe(5)
    expect(byCity.regionSlug).toBeUndefined()
  })
})
