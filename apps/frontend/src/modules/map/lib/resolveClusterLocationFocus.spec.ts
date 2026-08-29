import { describe, expect, it } from 'vitest'
import { resolveClusterLocationFocus } from '@/modules/map/lib/resolveClusterLocationFocus'

describe('resolveClusterLocationFocus', () => {
  const citiesById = new Map([
    [1, { slug: 'minsk', regionSlug: 'minsk-city' }],
    [2, { slug: 'vitebsk-city', regionSlug: 'vitebsk' }],
    [3, { slug: 'orsha', regionSlug: 'vitebsk' }],
    [4, { slug: 'gomel-city', regionSlug: 'gomel' }],
  ])

  it('selects district when all listings share one city and district', () => {
    expect(resolveClusterLocationFocus(
      [
        { cityId: 1, districtId: 10 },
        { cityId: 1, districtId: 10 },
      ],
      citiesById,
    )).toEqual({
      kind: 'district',
      cityId: 1,
      citySlug: 'minsk',
      districtId: 10,
      regionSlug: 'minsk-city',
    })
  })

  it('selects city when listings share city but not district', () => {
    expect(resolveClusterLocationFocus(
      [
        { cityId: 2, districtId: 1 },
        { cityId: 2, districtId: 2 },
        { cityId: 2, districtId: null },
      ],
      citiesById,
    )).toEqual({
      kind: 'city',
      cityId: 2,
      citySlug: 'vitebsk-city',
      regionSlug: 'vitebsk',
    })
  })

  it('selects region when listings share region across cities', () => {
    expect(resolveClusterLocationFocus(
      [
        { cityId: 2, districtId: null },
        { cityId: 3, districtId: null },
      ],
      citiesById,
    )).toEqual({
      kind: 'region',
      regionSlug: 'vitebsk',
    })
  })

  it('falls back to bounds for mixed regions', () => {
    expect(resolveClusterLocationFocus(
      [
        { cityId: 2, districtId: null },
        { cityId: 4, districtId: null },
      ],
      citiesById,
    )).toEqual({ kind: 'bounds' })
  })
})
