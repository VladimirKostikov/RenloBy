import { loadYandexMapsApi } from '@/lib/yandexMapsLoader'
import { BELARUS_BOUNDS } from '@/types/map'

export type LatLon = [number, number]
export type ContainerPoint = { x: number; y: number }

const MINSK_CENTER: LatLon = [53.9045, 27.5615]
const BELARUS_CENTER: LatLon = [
  (BELARUS_BOUNDS[0][0] + BELARUS_BOUNDS[1][0]) / 2,
  (BELARUS_BOUNDS[0][1] + BELARUS_BOUNDS[1][1]) / 2,
]
const DEFAULT_ZOOM = 6

export const CITY_CENTERS: Record<string, LatLon> = {
  minsk: MINSK_CENTER,
  borisov: [54.2279, 28.5050],
  soligorsk: [52.7928, 27.5414],
  molodechno: [54.3107, 26.8512],
  'brest-city': [52.0976, 23.7341],
  'vitebsk-city': [55.1904, 30.2049],
  'gomel-city': [52.4345, 30.9754],
  'grodno-city': [53.6693, 23.8131],
  'mogilev-city': [53.8945, 30.3307],
  zhodino: [53.3447, 28.3236],
  berezino: [53.8378, 27.6906],
  mir: [53.4514, 26.4729],
  motol: [52.3147, 25.5739],
  chechersk: [52.9164, 30.9179],
}

export function getYandexMapsApiKey(): string {
  const key = (import.meta.env.VITE_YANDEX_MAPS_API_KEY as string | undefined)?.trim()
  if (!key) {
    throw new Error('yandex_maps_api_key_missing')
  }

  return key
}

export function getCityCenter(citySlug: string): LatLon | null {
  return CITY_CENTERS[citySlug] ?? null
}

export function getMapCenter(): LatLon {
  return BELARUS_CENTER
}

export function getDefaultZoom(): number {
  return DEFAULT_ZOOM
}

export function getBelarusBoundsPoints(): LatLon[] {
  return [
    BELARUS_BOUNDS[0],
    [BELARUS_BOUNDS[0][0], BELARUS_BOUNDS[1][1]],
    BELARUS_BOUNDS[1],
    [BELARUS_BOUNDS[1][0], BELARUS_BOUNDS[0][1]],
  ]
}

export async function createYandexMap(
  container: HTMLElement,
  center: LatLon = BELARUS_CENTER,
  zoom = DEFAULT_ZOOM,
): Promise<YandexMapInstance> {
  await loadYandexMapsApi(getYandexMapsApiKey())

  const map = new ymaps.Map(
    container,
    {
      center,
      zoom,
      controls: [],
    },
    {
      suppressMapOpenBlock: true,
      yandexMapDisablePoiInteractivity: true,
    },
  )

  map.controls.add(new ymaps.control.ZoomControl({ options: { position: { left: 10, bottom: 40 } } }))
  return map
}

export function getFitZoomMargin(mapWidth: number): number {
  if (mapWidth > 0 && mapWidth < 480) {
    return 18
  }

  return 30
}

export function fitMapToBounds(
  map: YandexMapInstance,
  bounds: LatLon[],
  maxZoom?: number,
  durationMs = 280,
): void {
  if (bounds.length === 0) {
    return
  }

  const size = map.container.getSize?.() ?? [0, 0]
  if (size[0] < 80 || size[1] < 80) {
    return
  }

  map.setBounds(ymaps.util.bounds.fromPoints(bounds), {
    checkZoomRange: true,
    zoomMargin: getFitZoomMargin(size[0]),
    duration: durationMs,
  })

  if (maxZoom !== undefined && map.getZoom() > maxZoom) {
    map.setZoom(maxZoom, { duration: durationMs })
  }
}

export function flyMapTo(
  map: YandexMapInstance,
  center: LatLon,
  zoom: number,
  durationMs = 140,
): void {
  map.setCenter(center, zoom, { duration: durationMs })
}

export function getMapBoundsBox(map: YandexMapInstance): {
  south: number
  west: number
  north: number
  east: number
} {
  const bounds = map.getBounds()
  const southWest = bounds.getSouthWest()
  const northEast = bounds.getNorthEast()

  return {
    south: southWest[0],
    west: southWest[1],
    north: northEast[0],
    east: northEast[1],
  }
}

export function coordsToContainerPoint(
  map: YandexMapInstance,
  latitude: number,
  longitude: number,
): ContainerPoint | null {
  const mapElement = map.container.getElement()
  const rect = mapElement.getBoundingClientRect()

  try {
    const globalPixels = map.converter.coordinatesToGlobalPixels([latitude, longitude], map.getZoom())
    const page = map.converter.globalToPage(globalPixels)
    return {
      x: page[0] - rect.left,
      y: page[1] - rect.top,
    }
  } catch {
  }

  if (typeof ymaps !== 'undefined' && ymaps.projection?.wgs84Mercator) {
    const projection = ymaps.projection.wgs84Mercator
    const zoom = map.getZoom()
    const center = map.getCenter()
    const globalPixelCenter = projection.toGlobalPixels(center, zoom)
    const globalPixel = projection.toGlobalPixels([latitude, longitude], zoom)
    const width = mapElement.clientWidth
    const height = mapElement.clientHeight

    return {
      x: globalPixel[0] - globalPixelCenter[0] + width / 2,
      y: globalPixel[1] - globalPixelCenter[1] + height / 2,
    }
  }

  return null
}
