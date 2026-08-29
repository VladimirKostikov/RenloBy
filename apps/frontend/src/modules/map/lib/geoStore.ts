import type { Feature, FeatureCollection, Geometry } from 'geojson'
import type { MapViewState, MapZoneProperties } from '@/types/map'
import { DISTRICT_GEO_URLS, MAP_GEO_URLS } from '@/lib/mapManifest'
import { filterFeaturesByRegion, loadGeoJson, toFeatureCollection } from '@/lib/mapZones'

type GeoCache = {
  regions: FeatureCollection
  cities: FeatureCollection
  districts: Map<string, FeatureCollection>
}

let cache: GeoCache | null = null

function withoutInteriorRings(feature: Feature): Feature {
  if (feature.geometry.type === 'Polygon') {
    return {
      ...feature,
      geometry: {
        ...feature.geometry,
        coordinates: feature.geometry.coordinates.slice(0, 1),
      },
    }
  }

  if (feature.geometry.type === 'MultiPolygon') {
    return {
      ...feature,
      geometry: {
        ...feature.geometry,
        coordinates: feature.geometry.coordinates.map((polygon) => polygon.slice(0, 1)),
      },
    }
  }

  return feature
}

function withoutCollectionInteriorRings(collection: FeatureCollection): FeatureCollection {
  return {
    ...collection,
    features: collection.features.map(withoutInteriorRings),
  }
}

export async function preloadGeoData(): Promise<GeoCache> {
  if (cache) {
    return cache
  }

  const [regions, cities] = await Promise.all([
    loadGeoJson(MAP_GEO_URLS.regions),
    loadGeoJson(MAP_GEO_URLS.cities),
  ])

  const districts = new Map<string, FeatureCollection>()
  const districtEntries = await Promise.all(
    Object.entries(DISTRICT_GEO_URLS).map(async ([citySlug, url]) => {
      const collection = await loadGeoJson(url)
      return [citySlug, collection] as const
    }),
  )

  for (const [citySlug, collection] of districtEntries) {
    districts.set(citySlug, collection)
  }

  cache = { regions, cities, districts }
  return cache
}

export function resetGeoCache(): void {
  cache = null
}

export function getCollectionForView(view: MapViewState, geo: GeoCache): FeatureCollection {
  if (view.mode === 'country') {
    return withoutCollectionInteriorRings(geo.regions)
  }

  if (view.mode === 'cities' && view.regionSlug) {
    const features = filterFeaturesByRegion(geo.cities, view.regionSlug)
    if (view.citySlug) {
      const selected = features.filter((feature) => {
        const props = feature.properties as MapZoneProperties | null
        return props?.slug === view.citySlug || props?.citySlug === view.citySlug
      })
      return toFeatureCollection(selected)
    }
    return toFeatureCollection(features)
  }

  if (view.mode === 'districts' && view.citySlug) {
    const districts = geo.districts.get(view.citySlug)
    if (districts) {
      return districts
    }
  }

  return geo.regions
}

export function findCityFeature(
  geo: GeoCache,
  citySlug: string,
): Feature<Geometry, MapZoneProperties> | null {
  const match = geo.cities.features.find((feature) => {
    const props = feature.properties as MapZoneProperties | null
    return props?.slug === citySlug || props?.citySlug === citySlug
  })

  return (match as Feature<Geometry, MapZoneProperties> | undefined) ?? null
}

export function findRegionFeature(
  geo: GeoCache,
  regionSlug: string,
): Feature<Geometry, MapZoneProperties> | null {
  const match = geo.regions.features.find((feature) => {
    const props = feature.properties as MapZoneProperties | null
    return props?.slug === regionSlug
  })

  return match
    ? withoutInteriorRings(match) as Feature<Geometry, MapZoneProperties>
    : null
}

export function getOutlineForView(
  view: MapViewState,
  geo: GeoCache,
): Feature<Geometry, MapZoneProperties> | null {
  if (view.mode === 'cities' && view.regionSlug && !view.citySlug) {
    return findRegionFeature(geo, view.regionSlug)
  }

  return null
}

export function getFitCollectionForView(view: MapViewState, geo: GeoCache): FeatureCollection {
  if (view.mode === 'cities' && view.regionSlug && !view.citySlug) {
    const region = findRegionFeature(geo, view.regionSlug)
    if (region) {
      return toFeatureCollection([region])
    }
  }

  return getCollectionForView(view, geo)
}
