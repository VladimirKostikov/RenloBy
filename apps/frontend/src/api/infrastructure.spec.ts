import { describe, expect, it } from 'vitest'
import {
  buildOverpassQuery,
  clampInfrastructureBbox,
  formatInfrastructureBbox,
  getInfrastructureBboxSpan,
  parseOverpassElements,
} from '@/api/infrastructure'

describe('infrastructure api', () => {
  const bbox = { south: 53.8, west: 27.4, north: 54.0, east: 27.7 }

  it('formats bbox for overpass', () => {
    expect(formatInfrastructureBbox(bbox)).toBe('53.8,27.4,54,27.7')
  })

  it('builds query for selected infrastructure types', () => {
    const query = buildOverpassQuery(['pharmacy', 'school'], bbox)

    expect(query).toContain('amenity"="pharmacy"')
    expect(query).toContain('way["amenity"="pharmacy"]')
    expect(query).toContain('amenity"~"^(school|kindergarten)$"')
    expect(query).not.toContain('["shop"]')
  })

  it('clamps large bbox to keep requests bounded', () => {
    const clamped = clampInfrastructureBbox(
      { south: 53.0, west: 27.0, north: 54.0, east: 28.0 },
      0.18,
    )

    expect(clamped.north - clamped.south).toBeCloseTo(0.18, 5)
    expect(clamped.east - clamped.west).toBeCloseTo(0.18, 5)
  })

  it('uses wider span on lower zoom levels', () => {
    expect(getInfrastructureBboxSpan(15)).toBeLessThan(getInfrastructureBboxSpan(12))
  })

  it('parses overpass elements into map pois', () => {
    const pois = parseOverpassElements(
      [
        {
          id: 1,
          type: 'node',
          lat: 53.9,
          lon: 27.5,
          tags: { amenity: 'pharmacy', name: 'Аптека 1' },
        },
        {
          id: 3,
          type: 'node',
          lat: 53.92,
          lon: 27.52,
          tags: { leisure: 'park', name: 'Парк' },
        },
      ],
      { shop: 'Магазин', pharmacy: 'Аптека', school: 'Школа', park: 'Парк' },
    )

    expect(pois).toHaveLength(2)
    expect(pois[0]?.type).toBe('pharmacy')
    expect(pois[1]?.type).toBe('park')
  })
})
