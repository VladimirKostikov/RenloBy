import { describe, expect, it } from 'vitest'
import { filterFeaturesByRegion, getRegionSlugForCity, resolveRegionViewForListingClick } from '@/lib/mapZones'
import type { FeatureCollection } from 'geojson'

describe('getRegionSlugForCity', () => {
  it('maps regional capitals to oblast slugs', () => {
    expect(getRegionSlugForCity('gomel-city')).toBe('gomel')
    expect(getRegionSlugForCity('brest-city')).toBe('brest')
  })

  it('maps minsk oblast cities to minsk-region', () => {
    expect(getRegionSlugForCity('borisov')).toBe('minsk-region')
    expect(getRegionSlugForCity('minsk')).toBe('minsk-city')
  })

  it('returns null for unknown slugs', () => {
    expect(getRegionSlugForCity('unknown')).toBeNull()
  })
})

describe('filterFeaturesByRegion', () => {
  const collection = {
    type: 'FeatureCollection',
    features: [
      {
        type: 'Feature',
        properties: { slug: 'minsk', regionSlug: 'minsk-city', hasDistricts: true, level: 'city', name: 'Минск' },
        geometry: { type: 'Polygon', coordinates: [[[27.4, 53.8], [27.5, 53.8], [27.5, 53.9], [27.4, 53.9], [27.4, 53.8]]] },
      },
      {
        type: 'Feature',
        properties: { slug: 'borisov', regionSlug: 'minsk-region', level: 'city', name: 'Борисов' },
        geometry: { type: 'Polygon', coordinates: [[[28.4, 54.2], [28.5, 54.2], [28.5, 54.3], [28.4, 54.3], [28.4, 54.2]]] },
      },
    ],
  } as FeatureCollection

  it('includes minsk in minsk-region city layer', () => {
    const features = filterFeaturesByRegion(collection, 'minsk-region')
    expect(features.map((feature) => feature.properties?.slug)).toEqual(['minsk', 'borisov'])
  })

  it('returns minsk city for minsk-city region layer', () => {
    const features = filterFeaturesByRegion(collection, 'minsk-city')
    expect(features.map((feature) => feature.properties?.slug)).toEqual(['minsk'])
  })
})

describe('resolveRegionViewForListingClick', () => {
  it('opens oblast view from country level', () => {
    expect(resolveRegionViewForListingClick('country', 'gomel-city')).toEqual({
      mode: 'cities',
      regionSlug: 'gomel',
      citySlug: 'gomel-city',
    })
  })

  it('opens minsk districts from country level', () => {
    expect(resolveRegionViewForListingClick('country', 'minsk')).toEqual({
      mode: 'districts',
      regionSlug: 'minsk-city',
      citySlug: 'minsk',
    })
  })

  it('does not change view when already drilled down', () => {
    expect(resolveRegionViewForListingClick('cities', 'gomel-city')).toBeNull()
    expect(resolveRegionViewForListingClick('districts', 'minsk')).toBeNull()
  })
})
