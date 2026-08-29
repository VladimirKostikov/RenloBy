import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import type { FeatureCollection, Geometry } from 'geojson'
import { DISTRICT_GEO_URLS, REGION_CITY_SLUGS } from '@/lib/mapManifest'

function readGeo(name: string): FeatureCollection {
  const path = resolve(process.cwd(), 'public/geo', name)
  return JSON.parse(readFileSync(path, 'utf8')) as FeatureCollection
}

function geometrySpan(geometry: Geometry): { lon: number; lat: number } {
  const points: [number, number][] = []

  const walk = (coords: unknown): void => {
    if (!Array.isArray(coords)) {
      return
    }
    if (typeof coords[0] === 'number' && typeof coords[1] === 'number') {
      points.push([coords[0], coords[1]])
      return
    }
    for (const part of coords) {
      walk(part)
    }
  }

  walk(geometry.coordinates)

  const lons = points.map((point) => point[0])
  const lats = points.map((point) => point[1])

  return {
    lon: Math.max(...lons) - Math.min(...lons),
    lat: Math.max(...lats) - Math.min(...lats),
  }
}

describe('map geojson boundaries', () => {
  it('uses compact city boundaries instead of rayon-sized polygons', () => {
    const cities = readGeo('belarus-cities.geojson')

    expect(cities.features).toHaveLength(9)

    for (const feature of cities.features) {
      const slug = feature.properties?.slug as string
      const span = geometrySpan(feature.geometry as Geometry)
      const maxSpan = slug === 'minsk' ? 0.5 : 0.45

      expect(span.lon, `${slug} lon span`).toBeLessThanOrEqual(maxSpan)
      expect(span.lat, `${slug} lat span`).toBeLessThanOrEqual(maxSpan)
      expect(feature.geometry.type, `${slug} geometry`).toBe('Polygon')
    }

    const minsk = cities.features.find((feature) => feature.properties?.slug === 'minsk')
    expect(minsk?.properties?.regionSlug).toBe('minsk-city')

    const brest = cities.features.find((feature) => feature.properties?.slug === 'brest-city')
    expect(brest).toBeTruthy()
    const brestSpan = geometrySpan(brest!.geometry as Geometry)
    expect(brestSpan.lat).toBeLessThan(0.2)
  })

  it('contains nine minsk district polygons from manifest', () => {
    const districts = readGeo('minsk-districts.geojson')

    expect(districts.features).toHaveLength(9)
    expect(DISTRICT_GEO_URLS.minsk).toBe('/geo/minsk-districts.geojson')
    expect(REGION_CITY_SLUGS['minsk-city']).toEqual(['minsk'])
  })

  it('keeps minsk district borders topologically aligned', () => {
    const districts = readGeo('minsk-districts.geojson')
    const edgeCount = new Map<string, number>()

    const walkRing = (ring: number[][]) => {
      for (let index = 0; index < ring.length - 1; index += 1) {
        const a = ring[index]
        const b = ring[index + 1]
        const key = [
          a[0].toFixed(5),
          a[1].toFixed(5),
          b[0].toFixed(5),
          b[1].toFixed(5),
        ].sort().join('|')
        edgeCount.set(key, (edgeCount.get(key) ?? 0) + 1)
      }
    }

    const walkCoords = (coords: unknown) => {
      if (!Array.isArray(coords)) {
        return
      }
      if (typeof coords[0] === 'number') {
        return
      }
      if (typeof coords[0][0] === 'number') {
        walkRing(coords as number[][])
        return
      }
      for (const part of coords) {
        walkCoords(part)
      }
    }

    for (const item of districts.features) {
      walkCoords((item.geometry as Geometry).coordinates)
    }

    let shared = 0
    let single = 0
    for (const count of edgeCount.values()) {
      if (count >= 2) {
        shared += 1
      } else {
        single += 1
      }
    }

    expect(shared).toBeGreaterThan(120)
    expect(shared / (shared + single)).toBeGreaterThan(0.24)
  })

  it('keeps simplified region geojson lightweight', () => {
    const regions = readGeo('belarus-regions.geojson')
    expect(regions.features).toHaveLength(7)
    const raw = readFileSync(resolve(process.cwd(), 'public/geo/belarus-regions.geojson'), 'utf8')
    expect(raw.length).toBeLessThan(250000)
  })
})
