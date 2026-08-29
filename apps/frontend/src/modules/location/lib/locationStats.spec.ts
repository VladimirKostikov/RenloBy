import { describe, expect, it } from 'vitest'
import {
  buildCityCards,
  buildDistrictCards,
  getCityStatsFromResponse,
  getDistrictStatsFromResponse,
  getRegionStatsFromResponse,
} from '@/modules/location/lib/locationStats'
import type { CityDto, DistrictDto } from '@/types'

const cities: CityDto[] = [
  { id: 1, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' },
  { id: 2, name: 'Брест', slug: 'brest-city', regionSlug: 'brest' },
]

const districts: DistrictDto[] = [
  { id: 10, name: 'Центральный', slug: 'centralny', cityId: 1 },
  { id: 11, name: 'Советский', slug: 'sovetsky', cityId: 1 },
]

const response = {
  cities: [
    { id: 1, count: 50, avgPrice: 80000, avgPricePerSqm: 1200 },
    { id: 2, count: 10, avgPrice: 40000, avgPricePerSqm: 800 },
  ],
  districts: [
    { id: 10, count: 20, avgPrice: 90000, avgPricePerSqm: 1300 },
    { id: 11, count: 15, avgPrice: 70000, avgPricePerSqm: 1100 },
  ],
}

describe('locationStats', () => {
  it('reads city stats by id', () => {
    expect(getCityStatsFromResponse(1, response)).toEqual({
      count: 50,
      avgPrice: 80000,
      avgPricePerSqm: 1200,
    })
  })

  it('reads district stats by id', () => {
    expect(getDistrictStatsFromResponse(10, response)).toEqual({
      count: 20,
      avgPrice: 90000,
      avgPricePerSqm: 1300,
    })
  })

  it('aggregates region stats from cities', () => {
    expect(getRegionStatsFromResponse(cities, 'minsk-city', response)).toEqual({
      count: 50,
      avgPrice: 80000,
      avgPricePerSqm: 1200,
    })
  })

  it('builds district cards for city', () => {
    const cards = buildDistrictCards(districts, 1, response)
    expect(cards).toHaveLength(2)
    const slugs = cards.map((card) => card.district.slug)
    expect(slugs).toContain('centralny')
    expect(slugs).toContain('sovetsky')
    const central = cards.find((card) => card.district.slug === 'centralny')
    expect(central?.stats.count).toBe(20)
  })

  it('builds city cards for region', () => {
    const cards = buildCityCards(cities, 'brest', response)
    expect(cards).toHaveLength(1)
    expect(cards[0]?.city.slug).toBe('brest-city')
    expect(cards[0]?.stats.count).toBe(10)
  })
})
