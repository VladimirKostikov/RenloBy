import { describe, expect, it } from 'vitest'
import { filterCitiesByRegionSlug, isFilterRegionSlug } from '@/lib/filterRegions'

describe('filterRegions', () => {
  it('validates known region slugs', () => {
    expect(isFilterRegionSlug('minsk-region')).toBe(true)
    expect(isFilterRegionSlug('unknown')).toBe(false)
  })

  it('filters cities by region slug', () => {
    const cities = [
      { id: 1, regionSlug: 'minsk-city' },
      { id: 2, regionSlug: 'minsk-region' },
      { id: 3, regionSlug: 'brest' },
    ]

    expect(filterCitiesByRegionSlug(cities, 'brest').map((city) => city.id)).toEqual([3])
    expect(filterCitiesByRegionSlug(cities, undefined)).toHaveLength(3)
  })
})
