import { describe, expect, it } from 'vitest'
import {
  resolveLocation,
  resolveRegionLocation,
  cityHasDistricts,
} from '@/modules/location/lib/resolveLocation'
import type { CityDto, DistrictDto } from '@/types'

const cities: CityDto[] = [
  { id: 1, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' },
  { id: 2, name: 'Брест', slug: 'brest-city', regionSlug: 'brest' },
]

const districts: DistrictDto[] = [
  { id: 10, name: 'Центральный', slug: 'centralny', cityId: 1 },
  { id: 11, name: 'Советский', slug: 'sovetsky', cityId: 1 },
]

describe('resolveLocation', () => {
  it('resolves city by slug', () => {
    const result = resolveLocation(cities, districts, 'minsk')
    expect(result).toEqual({ kind: 'city', city: cities[0] })
  })

  it('resolves district by slug within city', () => {
    const result = resolveLocation(cities, districts, 'minsk', 'centralny')
    expect(result?.kind).toBe('district')
    expect(result?.kind === 'district' && result.district.id).toBe(10)
  })

  it('returns null for unknown city', () => {
    expect(resolveLocation(cities, districts, 'unknown')).toBeNull()
  })

  it('returns null for unknown district', () => {
    expect(resolveLocation(cities, districts, 'minsk', 'unknown')).toBeNull()
  })
})

describe('resolveRegionLocation', () => {
  it('resolves known region slug', () => {
    expect(resolveRegionLocation('gomel')).toEqual({ kind: 'region', regionSlug: 'gomel' })
  })

  it('returns null for unknown region', () => {
    expect(resolveRegionLocation('unknown')).toBeNull()
  })
})

describe('cityHasDistricts', () => {
  it('detects districts for city', () => {
    expect(cityHasDistricts(districts, 1)).toBe(true)
    expect(cityHasDistricts(districts, 2)).toBe(false)
  })
})
