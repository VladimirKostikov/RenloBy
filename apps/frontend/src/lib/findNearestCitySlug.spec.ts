import { describe, expect, it } from 'vitest'
import { CITY_COORDS } from '@/lib/cityCoords'
import { findNearestCitySlug, haversineKm } from '@/lib/findNearestCitySlug'

describe('findNearestCitySlug', () => {
  it('returns minsk for minsk coordinates', () => {
    const [lat, lng] = CITY_COORDS.minsk
    expect(findNearestCitySlug(lat, lng, CITY_COORDS)).toBe('minsk')
  })

  it('returns brest-city for brest coordinates', () => {
    const [lat, lng] = CITY_COORDS['brest-city']
    expect(findNearestCitySlug(lat, lng, CITY_COORDS)).toBe('brest-city')
  })

  it('calculates haversine distance', () => {
    const [minskLat, minskLng] = CITY_COORDS.minsk
    const [brestLat, brestLng] = CITY_COORDS['brest-city']
    const distance = haversineKm(minskLat, minskLng, brestLat, brestLng)

    expect(distance).toBeGreaterThan(300)
    expect(distance).toBeLessThan(400)
  })
})
