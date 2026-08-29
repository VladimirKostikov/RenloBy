import type { Feature, FeatureCollection, Geometry } from 'geojson'
import type { MapViewMode, MapZoneProperties } from '@/types/map'
import {
  boundsFromFeature,
  boundsFromFeatureCollection,
  geometryToYandexParts,
} from '@/modules/map/lib/geoToYandex'
import { getEventPointer } from '@/modules/map/lib/yandexMapEvents'
import {
  cityOutlineStyle,
  createRegionOutlineStyle,
  createZoneBorderStyle,
  createZoneFillStyle,
  zoneZIndexForView,
} from '@/modules/map/lib/zoneStyles'

type ZoneHandlers = {
  onHover: (props: MapZoneProperties, pointer: { x: number; y: number } | null) => void
  onHoverMove: (props: MapZoneProperties, pointer: { x: number; y: number } | null) => void
  onHoverEnd: (props: MapZoneProperties) => void
  onClick: (props: MapZoneProperties) => void
}

type YandexStyle = Record<string, unknown>

function toYandexZoneOptions(
  mode: MapViewMode,
  slug: string,
  hovered: string | null,
  selected: string | null,
  selectedCitySlug: string | null,
  selectedDistrictSlug: string | null,
): YandexStyle {
  const fill = createZoneFillStyle(mode, slug, hovered, selected)
  const border = createZoneBorderStyle(mode, slug, hovered, selected)
  const isOutlineOnly = mode === 'districts' && selectedDistrictSlug !== null

  return {
    fillColor: fill.fillColor,
    fillOpacity: fill.fillOpacity,
    strokeColor: border.color,
    strokeWidth: border.weight,
    strokeOpacity: border.opacity,
    cursor: isOutlineOnly ? 'default' : 'pointer',
    zIndex: zoneZIndexForView(mode, selectedCitySlug, selectedDistrictSlug),
    interactivityModel: isOutlineOnly ? 'default#silent' : 'default#opaque',
  }
}

function toYandexOutlineOptions(
  mode: MapViewMode,
  selectedCitySlug: string | null,
  selectedDistrictSlug: string | null,
): YandexStyle {
  if (mode === 'cities') {
    if (selectedCitySlug) {
      return {
        fillColor: '#2563eb',
        fillOpacity: 0,
        strokeColor: '#1e3a8a',
        strokeWidth: 2,
        strokeOpacity: 0.75,
        zIndex: zoneZIndexForView(mode, selectedCitySlug, selectedDistrictSlug) - 50,
        interactivityModel: 'default#silent',
      }
    }

    const style = createRegionOutlineStyle()
    return {
      fillColor: style.fillColor,
      fillOpacity: style.fillOpacity,
      strokeColor: style.color,
      strokeWidth: style.weight,
      strokeOpacity: style.opacity,
      zIndex: zoneZIndexForView(mode, selectedCitySlug, selectedDistrictSlug) - 50,
      interactivityModel: 'default#silent',
    }
  }

  return {
    strokeColor: cityOutlineStyle.color,
    strokeWidth: cityOutlineStyle.weight,
    strokeOpacity: cityOutlineStyle.opacity,
    fillOpacity: 0,
    strokeStyle: 'dash',
    zIndex: zoneZIndexForView(mode, selectedCitySlug, selectedDistrictSlug) - 50,
    interactivityModel: 'default#silent',
  }
}

export class YandexZoneLayer {
  private map: YandexMapInstance
  private zoneCollection: YandexGeoObjectCollection
  private outlineCollection: YandexGeoObjectCollection
  private mode: MapViewMode = 'country'
  private selectedCitySlug: string | null = null
  private selectedDistrictSlug: string | null = null
  private hoveredSlug: string | null = null
  private selectedSlug: string | null = null
  private handlers: ZoneHandlers | null = null
  private polygonsBySlug = new Map<string, YandexGeoObject[]>()

  private polygonProps = new WeakMap<YandexGeoObject, MapZoneProperties>()

  constructor(map: YandexMapInstance) {
    this.map = map
    this.zoneCollection = new ymaps.GeoObjectCollection()
    this.outlineCollection = new ymaps.GeoObjectCollection()
    map.geoObjects.add(this.outlineCollection)
    map.geoObjects.add(this.zoneCollection)
  }

  setHandlers(handlers: ZoneHandlers): void {
    this.handlers = handlers
  }

  setInteractionState(
    hoveredSlug: string | null,
    selectedSlug: string | null,
    selectedDistrictSlug: string | null = this.selectedDistrictSlug,
  ): void {
    this.hoveredSlug = hoveredSlug
    this.selectedSlug = selectedSlug
    this.selectedDistrictSlug = selectedDistrictSlug
    this.refreshStyles()
  }

  clear(): void {
    this.zoneCollection.removeAll()
    this.outlineCollection.removeAll()
    this.polygonsBySlug.clear()
    this.polygonProps = new WeakMap()
  }

  render(
    collection: FeatureCollection,
    mode: MapViewMode,
    cityOutline: Feature<Geometry, MapZoneProperties> | null,
    selectedCitySlug: string | null = null,
    selectedDistrictSlug: string | null = null,
  ): void {
    this.clear()
    this.mode = mode
    this.selectedCitySlug = selectedCitySlug
    this.selectedDistrictSlug = selectedDistrictSlug

    const features = mode === 'districts' && selectedDistrictSlug
      ? collection.features.filter((item) => {
            const props = item.properties as MapZoneProperties
            return props.slug === selectedDistrictSlug || props.districtSlug === selectedDistrictSlug
          })
      : collection.features

    for (const item of features) {
      const props = item.properties as MapZoneProperties
      const parts = geometryToYandexParts(item.geometry)
      if (parts.length === 0) {
        continue
      }

      const polygons: YandexGeoObject[] = []
      const isOutlineOnly = mode === 'districts' && selectedDistrictSlug !== null

      for (const coordinates of parts) {
        const polygon = new ymaps.Polygon(
          coordinates,
          {},
          toYandexZoneOptions(
            mode,
            props.slug,
            this.hoveredSlug,
            this.selectedSlug,
            selectedCitySlug,
            selectedDistrictSlug,
          ),
        )
        if (!isOutlineOnly) {
          this.bindZoneEvents(polygon, props)
        }
        this.polygonProps.set(polygon, props)
        polygons.push(polygon)
        this.zoneCollection.add(polygon)
      }

      this.polygonsBySlug.set(props.slug, polygons)
    }

    if (cityOutline && selectedDistrictSlug === null) {
      const outlineOptions = toYandexOutlineOptions(mode, selectedCitySlug, selectedDistrictSlug)
      for (const coordinates of geometryToYandexParts(cityOutline.geometry)) {
        this.outlineCollection.add(new ymaps.Polygon(coordinates, {}, outlineOptions))
      }
    }
  }

  refreshStyles(): void {
    for (const [slug, polygons] of this.polygonsBySlug) {
      const options = toYandexZoneOptions(
        this.mode,
        slug,
        this.hoveredSlug,
        this.selectedSlug,
        this.selectedCitySlug,
        this.selectedDistrictSlug,
      )
      for (const polygon of polygons) {
        for (const [key, value] of Object.entries(options)) {
          polygon.options.set(key, value)
        }
      }
    }
  }

  fitCollection(collection: FeatureCollection, maxZoom: number): void {
    const points = boundsFromFeatureCollection(collection)
    if (points.length === 0) {
      return
    }

    this.setCameraToPoints(points, 30, maxZoom)
  }

  zoomToSlug(
    collection: FeatureCollection,
    slug: string,
    slugField: 'slug' | 'citySlug' | 'districtSlug',
    maxZoom: number,
  ): void {
    const match = collection.features.find((item) => {
      const props = item.properties as MapZoneProperties
      const fieldValue = props[slugField] ?? props.slug
      return fieldValue === slug
        || props.slug === slug
        || props.citySlug === slug
        || props.districtSlug === slug
    })

    if (!match) {
      return
    }

    const points = boundsFromFeature(match)
    if (points.length === 0) {
      return
    }

    this.setCameraToPoints(points, 36, maxZoom)
  }

  private setCameraToPoints(points: [number, number][], zoomMargin: number, maxZoom: number): void {
    const bounds = ymaps.util.bounds.fromPoints(points)
    const size = this.map.container.getSize()
    const centerAndZoom = ymaps.util.bounds.getCenterAndZoom(
      bounds,
      size,
      this.map.options.get('projection'),
      { zoomMargin },
    )

    this.map.setCenter(centerAndZoom.center, Math.min(centerAndZoom.zoom, maxZoom), {
      duration: 280,
      checkZoomRange: true,
    })
  }

  bringToFront(): void {
    this.map.geoObjects.remove(this.outlineCollection)
    this.map.geoObjects.remove(this.zoneCollection)
    this.map.geoObjects.add(this.outlineCollection)
    this.map.geoObjects.add(this.zoneCollection)
  }

  destroy(): void {
    this.map.geoObjects.remove(this.zoneCollection)
    this.map.geoObjects.remove(this.outlineCollection)
    this.clear()
  }

  private bindZoneEvents(polygon: YandexGeoObject, props: MapZoneProperties): void {
    const handleEnter = (event: YandexMapEvent) => {
      this.hoveredSlug = props.slug
      this.refreshStyles()
      this.handlers?.onHover(props, getEventPointer(event))
    }

    const handleMove = (event: YandexMapEvent) => {
      this.handlers?.onHoverMove(props, getEventPointer(event))
    }

    const handleLeave = () => {
      this.hoveredSlug = null
      this.refreshStyles()
      this.handlers?.onHoverEnd(props)
    }

    const handleClick = (event: YandexMapEvent) => {
      event.stopPropagation()
      this.handlers?.onClick(props)
    }

    polygon.events.add('mouseenter', handleEnter)
    polygon.events.add('mousemove', handleMove)
    polygon.events.add('mouseleave', handleLeave)
    polygon.events.add('click', handleClick)
    polygon.events.add('tap', handleClick)
  }
}
