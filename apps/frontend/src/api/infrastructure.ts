import type { InfrastructureBbox, InfrastructurePoi, InfrastructureType } from '@/types/infrastructure'
import { MAX_INFRA_BBOX_SPAN } from '@/types/infrastructure'

interface OverpassElement {
  id: number
  type: 'node' | 'way' | 'relation'
  lat?: number
  lon?: number
  center?: { lat: number; lon: number }
  tags?: Record<string, string>
}

const TYPE_FILTERS: Record<InfrastructureType, string[]> = {
  shop: ['node["shop"]({bbox});', 'way["shop"]({bbox});'],
  pharmacy: ['node["amenity"="pharmacy"]({bbox});', 'way["amenity"="pharmacy"]({bbox});'],
  school: [
    'node["amenity"~"^(school|kindergarten)$"]({bbox});',
    'way["amenity"~"^(school|kindergarten)$"]({bbox});',
  ],
  park: [
    'node["leisure"~"^(park|garden)$"]({bbox});',
    'way["leisure"~"^(park|garden)$"]({bbox});',
  ],
}

export function formatInfrastructureBbox(bbox: InfrastructureBbox): string {
  return `${bbox.south},${bbox.west},${bbox.north},${bbox.east}`
}

export function buildOverpassQuery(types: InfrastructureType[], bbox: InfrastructureBbox): string {
  const bboxValue = formatInfrastructureBbox(bbox)
  const filters = types.flatMap((type) =>
    TYPE_FILTERS[type].map((filter) => filter.replace('{bbox}', bboxValue)),
  )

  if (filters.length === 0) {
    return ''
  }

  return `[out:json][timeout:10];(${filters.join('')});out 60;`
}

export function clampInfrastructureBbox(
  bbox: InfrastructureBbox,
  maxSpan = 1.2,
): InfrastructureBbox {
  const latSpan = bbox.north - bbox.south
  const lngSpan = bbox.east - bbox.west

  if (latSpan <= maxSpan && lngSpan <= maxSpan) {
    return bbox
  }

  const centerLat = (bbox.north + bbox.south) / 2
  const centerLng = (bbox.east + bbox.west) / 2
  const halfLat = Math.min(maxSpan / 2, latSpan / 2)
  const halfLng = Math.min(maxSpan / 2, lngSpan / 2)

  return {
    south: centerLat - halfLat,
    north: centerLat + halfLat,
    west: centerLng - halfLng,
    east: centerLng + halfLng,
  }
}

export function getInfrastructureBboxSpan(zoom: number): number {
  if (zoom >= 16) {
    return 0.08
  }
  if (zoom >= 14) {
    return 0.18
  }
  if (zoom >= 12) {
    return 0.42
  }
  return MAX_INFRA_BBOX_SPAN
}

export function resolveInfrastructureType(element: OverpassElement): InfrastructureType | null {
  const tags = element.tags ?? {}

  if (tags.amenity === 'pharmacy') {
    return 'pharmacy'
  }

  if (tags.amenity === 'school' || tags.amenity === 'kindergarten') {
    return 'school'
  }

  if (tags.shop) {
    return 'shop'
  }

  if (tags.leisure === 'park' || tags.leisure === 'garden') {
    return 'park'
  }

  return null
}

export function parseOverpassElements(
  elements: OverpassElement[],
  fallbackNames: Record<InfrastructureType, string>,
): InfrastructurePoi[] {
  const seen = new Set<string>()
  const pois: InfrastructurePoi[] = []

  for (const element of elements) {
    const type = resolveInfrastructureType(element)
    if (!type) {
      continue
    }

    const latitude = element.lat ?? element.center?.lat
    const longitude = element.lon ?? element.center?.lon
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
      continue
    }

    const id = `${type}-${element.id}`
    if (seen.has(id)) {
      continue
    }
    seen.add(id)

    pois.push({
      id,
      type,
      name: element.tags?.name?.trim() || fallbackNames[type],
      address: element.tags?.['addr:street']
        ? `${element.tags['addr:street']}${element.tags['addr:housenumber'] ? `, ${element.tags['addr:housenumber']}` : ''}`
        : `${latitude}, ${longitude}`,
      latitude: latitude as number,
      longitude: longitude as number,
    })
  }

  return pois
}

export async function fetchInfrastructurePois(
  types: InfrastructureType[],
  bbox: InfrastructureBbox,
  fallbackNames: Record<InfrastructureType, string>,
  signal?: AbortSignal,
): Promise<InfrastructurePoi[]> {
  if (types.length === 0) {
    return []
  }

  const params = new URLSearchParams({
    types: types.join(','),
    south: String(bbox.south),
    west: String(bbox.west),
    north: String(bbox.north),
    east: String(bbox.east),
    zoom: String(bbox.zoom ?? 14),
  })

  const response = await fetch(`/api/infrastructure/pois?${params.toString()}`, { signal })

  if (!response.ok) {
    throw new Error(`infrastructure_http_${response.status}`)
  }

  const payload = (await response.json()) as { items?: InfrastructurePoi[] }
  const items = Array.isArray(payload.items) ? payload.items : []

  return items.map((item) => ({
    id: item.id,
    type: item.type,
    name: item.name?.trim() || fallbackNames[item.type],
    address: item.address?.trim() || `${item.latitude}, ${item.longitude}`,
    latitude: item.latitude,
    longitude: item.longitude,
  }))
}
