import { formatMarkerPrice } from '@/lib/formatPrice'
import type { ListingDto } from '@/types'
import type { MapViewMode } from '@/types/map'
import { getEventContainerPoint } from '@/modules/map/lib/yandexMapEvents'
import { markerZIndexForView } from '@/modules/map/lib/zoneStyles'

export type ListingLayerOptions = {
  gridSize?: number
}

export type ListingClusterClickPayload = {
  listingIds: number[]
  points: number[][]
}

type ListingSelectHandler = (id: number, point?: { x: number; y: number }) => void
type ListingClusterClickHandler = (payload: ListingClusterClickPayload) => void

type ClusterGeoObject = {
  getGeoObjects?: () => Array<{
    properties?: { get?: (key: string) => unknown }
    geometry?: { getCoordinates?: () => number[] }
  }>
  getBounds?: () => YandexMapBounds | null
}

function readListingId(object: {
  properties?: { get?: (key: string) => unknown }
}): number | null {
  const raw = object.properties?.get?.('listingId')
  return typeof raw === 'number' && Number.isFinite(raw) ? raw : null
}

function readCoordinates(object: {
  geometry?: { getCoordinates?: () => number[] }
}): number[] | null {
  const coords = object.geometry?.getCoordinates?.()
  if (!coords || coords.length < 2) {
    return null
  }
  if (!Number.isFinite(coords[0]) || !Number.isFinite(coords[1])) {
    return null
  }
  return [coords[0], coords[1]]
}

function boundsToPoints(bounds: YandexMapBounds): number[][] {
  const southWest = bounds.getSouthWest()
  const northEast = bounds.getNorthEast()
  return [
    [southWest[0], southWest[1]],
    [southWest[0], northEast[1]],
    [northEast[0], northEast[1]],
    [northEast[0], southWest[1]],
  ]
}

export class YandexListingLayer {
  private map: YandexMapInstance
  private clusterer: YandexGeoObject | null = null
  private onSelect: ListingSelectHandler | null = null
  private onClusterClick: ListingClusterClickHandler | null = null
  private selectedId: number | null = null

  constructor(map: YandexMapInstance) {
    this.map = map
  }

  setSelectHandler(handler: ListingSelectHandler): void {
    this.onSelect = handler
  }

  setClusterClickHandler(handler: ListingClusterClickHandler): void {
    this.onClusterClick = handler
  }

  setSelectedId(id: number | null): void {
    this.selectedId = id
  }

  clear(): void {
    if (this.clusterer) {
      this.map.geoObjects.remove(this.clusterer)
      this.clusterer = null
    }
  }

  sync(
    listings: ListingDto[],
    mode: MapViewMode,
    options: ListingLayerOptions = {},
    selectedCitySlug: string | null = null,
    selectedDistrictSlug: string | null = null,
  ): void {
    this.clear()

    if (listings.length === 0) {
      return
    }

    const mapElement = this.map.container.getElement()
    const markerZIndex = markerZIndexForView(mode, selectedCitySlug, selectedDistrictSlug)

    const clusterer = new ymaps.Clusterer({
      preset: 'islands#redClusterIcons',
      groupByCoordinates: false,
      clusterDisableClickZoom: true,
      clusterOpenBalloonOnClick: false,
      gridSize: options.gridSize ?? 64,
      clusterZIndex: markerZIndex,
      clusterZIndexHover: markerZIndex + 20,
    })

    const placemarks = listings
      .filter((item) => Number.isFinite(item.latitude) && Number.isFinite(item.longitude))
      .map((item) => {
        const isSelected = item.id === this.selectedId
        const placemark = new ymaps.Placemark(
          [item.latitude, item.longitude],
          {
            listingId: item.id,
            hintContent: formatMarkerPrice(item.price),
            iconCaption: formatMarkerPrice(item.price),
          },
          {
            preset: isSelected
              ? 'islands#redDotIconWithCaption'
              : 'islands#redCircleDotIconWithCaption',
            interactivityModel: 'default#opaque',
            cursor: 'pointer',
            zIndex: markerZIndex,
            zIndexHover: markerZIndex + 20,
          },
        )

        const handleSelect = (event: YandexMapEvent) => {
          event.stopPropagation()
          const domEvent = event.get('domEvent')
          if (domEvent instanceof MouseEvent) {
            domEvent.stopPropagation()
          }
          const point = getEventContainerPoint(event, mapElement)
          this.onSelect?.(item.id, point ?? undefined)
        }

        placemark.events.add('click', handleSelect)
        placemark.events.add('tap', handleSelect)
        return placemark
      })

    if (placemarks.length === 0) {
      return
    }

    clusterer.add?.(placemarks)

    const handleClusterClick = (event: YandexMapEvent) => {
      const target = event.get('target') as ClusterGeoObject | null
      if (!target || typeof target.getGeoObjects !== 'function') {
        return
      }

      const geoObjects = target.getGeoObjects()
      const listingIds = geoObjects
        .map((object) => readListingId(object))
        .filter((id): id is number => id !== null)
      const points = geoObjects
        .map((object) => readCoordinates(object))
        .filter((point): point is number[] => point !== null)

      if (points.length === 0) {
        const bounds = target.getBounds?.()
        if (!bounds) {
          return
        }
        points.push(...boundsToPoints(bounds))
      }

      if (points.length === 0) {
        return
      }

      event.stopPropagation()
      const domEvent = event.get('domEvent')
      if (domEvent instanceof MouseEvent) {
        domEvent.stopPropagation()
      }

      this.onClusterClick?.({ listingIds, points })
    }

    clusterer.events.add('click', handleClusterClick)
    clusterer.events.add('tap', handleClusterClick)

    this.clusterer = clusterer
    this.map.geoObjects.add(clusterer)
  }

  destroy(): void {
    this.clear()
  }
}
