import type { Feature, FeatureCollection, Geometry } from 'geojson'
import type { MapViewState, MapZoneProperties, MapZoneStats, MapZoneStatsItem } from '@/types/map'
import { CITY_TO_REGION, GEO_VERSION, REGION_CITY_SLUGS } from '@/lib/mapManifest'
import { viewFromListingClick } from '@/modules/map/lib/mapViewFsm'

export async function loadGeoJson(url: string): Promise<FeatureCollection> {
  const separator = url.includes('?') ? '&' : '?'
  const response = await fetch(`${url}${separator}v=${GEO_VERSION}`, { cache: 'no-store' })
  if (!response.ok) {
    throw new Error(`Failed to load geo: ${url}`)
  }
  return response.json() as Promise<FeatureCollection>
}

export function emptyStats(): MapZoneStats {
  return { count: 0, avgPrice: 0, avgPricePerSqm: 0 }
}

export function buildCityStatsMap(items: MapZoneStatsItem[]): Map<number, MapZoneStats> {
  const map = new Map<number, MapZoneStats>()
  for (const item of items) {
    map.set(item.id, {
      count: item.count,
      avgPrice: item.avgPrice,
      avgPricePerSqm: item.avgPricePerSqm,
    })
  }
  return map
}

export function buildDistrictStatsMap(items: MapZoneStatsItem[]): Map<number, MapZoneStats> {
  return buildCityStatsMap(items)
}

export function aggregateStats(rows: MapZoneStats[]): MapZoneStats {
  if (rows.length === 0) {
    return emptyStats()
  }

  const count = rows.reduce((sum, row) => sum + row.count, 0)
  if (count === 0) {
    return emptyStats()
  }

  const avgPrice = Math.round(rows.reduce((sum, row) => sum + row.avgPrice * row.count, 0) / count)
  const avgPricePerSqm = Math.round(rows.reduce((sum, row) => sum + row.avgPricePerSqm * row.count, 0) / count)

  return { count, avgPrice, avgPricePerSqm }
}

export function getRegionStats(
  regionSlug: string,
  citySlugToId: Map<string, number>,
  cityStats: Map<number, MapZoneStats>,
): MapZoneStats {
  const citySlugs = (REGION_CITY_SLUGS as Record<string, readonly string[]>)[regionSlug] ?? []
  const rows: MapZoneStats[] = []

  for (const citySlug of citySlugs) {
    const cityId = citySlugToId.get(citySlug)
    if (cityId === undefined) {
      continue
    }
    rows.push(cityStats.get(cityId) ?? emptyStats())
  }

  return aggregateStats(rows)
}

export function filterFeaturesByRegion(
  collection: FeatureCollection,
  regionSlug: string,
): Feature<Geometry, MapZoneProperties>[] {
  return collection.features.filter((feature) => {
    const props = feature.properties as MapZoneProperties | null
    if (!props) {
      return false
    }

    if (regionSlug === 'minsk-region') {
      return props.regionSlug === 'minsk-region' || props.slug === 'minsk' || props.citySlug === 'minsk'
    }

    return props.regionSlug === regionSlug
  }) as Feature<Geometry, MapZoneProperties>[]
}

export function toFeatureCollection(features: Feature[]): FeatureCollection {
  return { type: 'FeatureCollection', features }
}

const CITY_TO_REGION_MAP: Record<string, string> = CITY_TO_REGION

export function getRegionSlugForCity(citySlug: string): string | null {
  return CITY_TO_REGION_MAP[citySlug] ?? null
}

export function resolveRegionViewForListingClick(
  currentMode: MapViewState['mode'],
  citySlug: string,
): MapViewState | null {
  if (currentMode === 'cities' || currentMode === 'districts') {
    return null
  }

  return viewFromListingClick(currentMode, citySlug)
}
