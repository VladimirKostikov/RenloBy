import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import type { FeatureCollection } from 'geojson'
import {
  findRegionFeature,
  getCollectionForView,
  getFitCollectionForView,
  getOutlineForView,
  resetGeoCache,
} from '@/modules/map/lib/geoStore'

function readGeo(name: string): FeatureCollection {
  return JSON.parse(readFileSync(resolve(process.cwd(), 'public/geo', name), 'utf8')) as FeatureCollection
}

describe('geoStore region outline', () => {
  const geo = {
    regions: readGeo('belarus-regions.geojson'),
    cities: readGeo('belarus-cities.geojson'),
    districts: new Map([['minsk', readGeo('minsk-districts.geojson')]]),
  }

  it('finds region feature by slug', () => {
    const region = findRegionFeature(geo, 'vitebsk')
    expect(region?.properties?.slug).toBe('vitebsk')
  })

  it('removes inner administrative rings from region borders', () => {
    const collection = getCollectionForView(
      { mode: 'country', regionSlug: null, citySlug: null },
      geo,
    )
    const minskRegion = collection.features.find((feature) => feature.properties?.slug === 'minsk-region')
    expect(minskRegion?.geometry.type).toBe('Polygon')
    if (minskRegion?.geometry.type === 'Polygon') {
      expect(minskRegion.geometry.coordinates).toHaveLength(1)
    }
  })

  it('keeps region outline for oblast cities overview', () => {
    const outline = getOutlineForView(
      { mode: 'cities', regionSlug: 'vitebsk', citySlug: null },
      geo,
    )
    expect(outline?.properties?.slug).toBe('vitebsk')
  })

  it('hides the parent region outline when a city is selected', () => {
    const outline = getOutlineForView(
      { mode: 'cities', regionSlug: 'vitebsk', citySlug: 'vitebsk-city' },
      geo,
    )
    expect(outline).toBeNull()
  })

  it('returns only the selected city in cities mode', () => {
    const collection = getCollectionForView(
      { mode: 'cities', regionSlug: 'vitebsk', citySlug: 'vitebsk-city' },
      geo,
    )
    expect(collection.features).toHaveLength(1)
    expect(collection.features[0]?.properties?.slug).toBe('vitebsk-city')
  })

  it('fits oblast view to region bounds instead of only city polygons', () => {
    const fit = getFitCollectionForView(
      { mode: 'cities', regionSlug: 'vitebsk', citySlug: null },
      geo,
    )
    expect(fit.features).toHaveLength(1)
    expect(fit.features[0]?.properties?.slug).toBe('vitebsk')
  })

  it('does not show a city outline in minsk districts overview', () => {
    const outline = getOutlineForView(
      { mode: 'districts', regionSlug: 'minsk-city', citySlug: 'minsk' },
      geo,
    )
    expect(outline).toBeNull()
  })

  it('resets geo cache helper', () => {
    resetGeoCache()
    expect(true).toBe(true)
  })
})
