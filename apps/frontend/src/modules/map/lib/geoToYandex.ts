import type { Feature, FeatureCollection, Geometry } from 'geojson'

export type LatLon = [number, number]

export function lonLatToYandex(lon: number, lat: number): LatLon {
  return [lat, lon]
}

export function ringToYandex(ring: number[][]): LatLon[] {
  return ring.map(([lon, lat]) => lonLatToYandex(lon, lat))
}

export function polygonToYandexCoordinates(geometry: Extract<Geometry, { type: 'Polygon' }>): LatLon[][] {
  return geometry.coordinates.map((ring) => ringToYandex(ring))
}

export function geometryToYandexParts(geometry: Geometry): LatLon[][][] {
  if (geometry.type === 'Polygon') {
    return [polygonToYandexCoordinates(geometry)]
  }

  if (geometry.type === 'MultiPolygon') {
    return geometry.coordinates.map((polygon) => polygon.map((ring) => ringToYandex(ring)))
  }

  return []
}

export function geometryToYandexPolygons(geometry: Geometry): LatLon[][] {
  const parts = geometryToYandexParts(geometry)
  if (parts.length === 0) {
    return []
  }

  return parts[0]
}

export function collectPointsFromGeometry(geometry: Geometry): LatLon[] {
  const points: LatLon[] = []

  const walk = (coords: unknown): void => {
    if (!Array.isArray(coords)) {
      return
    }
    if (typeof coords[0] === 'number' && typeof coords[1] === 'number') {
      points.push(lonLatToYandex(coords[0], coords[1]))
      return
    }
    for (const part of coords) {
      walk(part)
    }
  }

  if (geometry.type === 'GeometryCollection') {
    for (const part of geometry.geometries) {
      points.push(...collectPointsFromGeometry(part))
    }
    return points
  }

  walk(geometry.coordinates)
  return points
}

export function boundsFromFeatureCollection(collection: FeatureCollection): LatLon[] {
  const points: LatLon[] = []
  for (const item of collection.features) {
    points.push(...collectPointsFromGeometry(item.geometry))
  }
  return points
}

export function boundsFromFeature(feature: Feature): LatLon[] {
  return collectPointsFromGeometry(feature.geometry)
}
