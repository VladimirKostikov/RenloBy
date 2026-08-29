import { describe, expect, it } from 'vitest'
import { geometryToYandexParts } from '@/modules/map/lib/geoToYandex'

describe('geometryToYandexParts', () => {
  it('keeps polygon rings as one yandex polygon', () => {
    const parts = geometryToYandexParts({
      type: 'Polygon',
      coordinates: [
        [[27.5, 53.9], [27.6, 53.9], [27.6, 54.0], [27.5, 54.0], [27.5, 53.9]],
        [[27.55, 53.92], [27.57, 53.92], [27.57, 53.94], [27.55, 53.94], [27.55, 53.92]],
      ],
    })

    expect(parts).toHaveLength(1)
    expect(parts[0]).toHaveLength(2)
    expect(parts[0]?.[0]?.[0]).toEqual([53.9, 27.5])
  })

  it('maps each multipolygon part separately', () => {
    const parts = geometryToYandexParts({
      type: 'MultiPolygon',
      coordinates: [
        [[[27.5, 53.9], [27.6, 53.9], [27.6, 54.0], [27.5, 53.9]]],
        [[[30.3, 53.8], [30.4, 53.8], [30.4, 53.9], [30.3, 53.8]]],
      ],
    })

    expect(parts).toHaveLength(2)
    expect(parts[0]?.[0]?.[0]).toEqual([53.9, 27.5])
    expect(parts[1]?.[0]?.[0]).toEqual([53.8, 30.3])
  })
})
