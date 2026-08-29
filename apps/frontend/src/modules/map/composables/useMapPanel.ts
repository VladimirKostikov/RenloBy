import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { fetchMapStats } from '@/api/map'
import {
  coordsToContainerPoint,
  createYandexMap,
  fitMapToBounds,
  flyMapTo,
  getBelarusBoundsPoints,
  getCityCenter,
} from '@/lib/mapConfig'
import { shouldToggleCloseOnReselect } from '@/lib/mapListingSelection'
import { computeMapPopupPosition, type MapPopupPosition } from '@/lib/mapPopupPosition'
import {
  buildCityStatsMap,
  buildDistrictStatsMap,
  emptyStats,
  getRegionStats,
} from '@/lib/mapZones'
import { REGION_CITY_SLUGS, CITY_TO_REGION } from '@/lib/mapManifest'
import { getCollectionForView, getFitCollectionForView, getOutlineForView, preloadGeoData } from '@/modules/map/lib/geoStore'
import {
  breadcrumbKey,
  createCountryView,
  fitMaxZoomForView,
  mapViewsEqual,
  shouldUseNationwideMarkers,
  viewAfterBack,
  viewAfterCityClick,
  viewAfterRegionClick,
  viewFromCitySlug,
  viewFromListingClick,
  viewFromRegionSlug,
} from '@/modules/map/lib/mapViewFsm'
import { clusterOptionsForView, shouldShowListingMarkers, shouldShowZonePolygons } from '@/modules/map/lib/zoneStyles'
import { resolveClusterLocationFocus } from '@/modules/map/lib/resolveClusterLocationFocus'
import { YandexListingLayer, type ListingClusterClickPayload } from '@/modules/map/lib/yandexListingLayer'
import { YandexZoneLayer } from '@/modules/map/lib/yandexZoneLayer'
import { useListingsStore } from '@/stores/listings'
import type { FeatureCollection } from 'geojson'
import type { ListingDto } from '@/types'
import type { MapViewState, MapZoneProperties, MapZoneStats, ZoneTooltipData } from '@/types/map'

type GeoCache = Awaited<ReturnType<typeof preloadGeoData>>

export function useMapPanel(mapRoot: { value: HTMLElement | null }, mapPanelRef: { value: HTMLElement | null }) {
  const { t } = useI18n()
  const listings = useListingsStore()
  const { mapItems, selectedListingId } = storeToRefs(listings)

  const isMapLoading = ref(true)
  const mapLoadError = ref(false)
  const viewState = ref<MapViewState>(createCountryView())
  const hoveredSlug = ref<string | null>(null)
  const selectedSlug = ref<string | null>(null)
  const tooltip = ref<ZoneTooltipData | null>(null)
  const popupPosition = ref<MapPopupPosition | null>(null)
  const listingCardLoading = ref(false)
  const popupCardRef = ref<{ getRootElement: () => HTMLElement | null } | null>(null)

  const cityStats = ref<Map<number, MapZoneStats>>(new Map())
  const districtStats = ref<Map<number, MapZoneStats>>(new Map())

  let map: YandexMapInstance | null = null
  let zoneLayer: YandexZoneLayer | null = null
  let listingLayer: YandexListingLayer | null = null
  let geoCache: GeoCache | null = null
  let mapLoadingDepth = 0
  let filterSyncLock = false
  let mapNavigationLock = false
  let navigationDepth = 0
  let navigationGeneration = 0
  let suppressMarkerWatch = false
  let markerRenderFrame: number | null = null
  let tooltipMoveFrame: number | null = null
  let pendingTooltipMove: { props: MapZoneProperties; pointer: { x: number; y: number } | null } | null = null
  let lastZoneClickKey = ''
  let lastZoneClickAt = 0
  let popupResizeObserver: ResizeObserver | null = null
  let mapResizeObserver: ResizeObserver | null = null
  let lastMapSize = { width: 0, height: 0 }

  function isUsableMapSize(width: number, height: number): boolean {
    return width >= 80 && height >= 80
  }

  function shouldFitCountryOverview(): boolean {
    return (
      viewState.value.mode === 'country'
      && listings.cityId === undefined
      && !listings.regionSlug
    )
  }

  function syncMapContainerSize(options?: { forceCountryFit?: boolean }) {
    if (!map || !mapRoot.value) {
      return
    }

    const width = mapRoot.value.clientWidth
    const height = mapRoot.value.clientHeight
    const wasUnusable = !isUsableMapSize(lastMapSize.width, lastMapSize.height)
    lastMapSize = { width, height }

    if (!isUsableMapSize(width, height)) {
      return
    }

    map.container.fitToViewport()

    if (options?.forceCountryFit || (wasUnusable && shouldFitCountryOverview())) {
      fitMapToBounds(map, getBelarusBoundsPoints(), fitMaxZoomForView(viewState.value), 0)
    }
  }

  function disconnectMapResizeObserver() {
    mapResizeObserver?.disconnect()
    mapResizeObserver = null
  }

  function connectMapResizeObserver() {
    disconnectMapResizeObserver()

    if (!mapRoot.value || typeof ResizeObserver === 'undefined') {
      return
    }

    mapResizeObserver = new ResizeObserver(() => {
      syncMapContainerSize()
    })
    mapResizeObserver.observe(mapRoot.value)

    const panel = mapPanelRef.value
    if (panel && panel !== mapRoot.value) {
      mapResizeObserver.observe(panel)
    }
  }

  function beginFilterSync() {
    filterSyncLock = true
  }

  function endFilterSyncSoon() {
    void nextTick(() => {
      if (navigationDepth === 0) {
        filterSyncLock = false
      }
    })
  }

  function withFilterSync<T>(action: () => T): T {
    beginFilterSync()
    try {
      return action()
    } finally {
      endFilterSyncSoon()
    }
  }

  async function runAsMapNavigation<T>(action: () => Promise<T> | T): Promise<T> {
    navigationDepth += 1
    mapNavigationLock = true
    beginFilterSync()
    const generation = ++navigationGeneration
    try {
      return await action()
    } finally {
      await nextTick()
      navigationDepth = Math.max(0, navigationDepth - 1)
      if (navigationDepth === 0 && generation === navigationGeneration) {
        mapNavigationLock = false
        filterSyncLock = false
      }
    }
  }

  const citySlugToId = computed(() => {
    const mapBySlug = new Map<string, number>()
    for (const city of listings.cities) {
      mapBySlug.set(city.slug, city.id)
    }
    return mapBySlug
  })

  const districtSlugToId = computed(() => {
    const mapBySlug = new Map<string, number>()
    for (const district of listings.districts) {
      mapBySlug.set(district.slug, district.id)
    }
    return mapBySlug
  })

  const selectedListing = computed((): ListingDto | null => {
    const id = selectedListingId.value
    if (id === null) {
      return null
    }

    return mapItems.value.find((item) => item.id === id)
      ?? listings.items.find((item) => item.id === id)
      ?? null
  })

  const metroById = computed(() => {
    const mapById = new Map<number, (typeof listings.metroStations)[number]>()
    for (const station of listings.metroStations) {
      mapById.set(station.id, station)
    }
    return mapById
  })

  const districtById = computed(() => {
    const mapById = new Map<number, (typeof listings.districts)[number]>()
    for (const district of listings.districts) {
      mapById.set(district.id, district)
    }
    return mapById
  })

  const breadcrumb = computed(() => {
    const key = breadcrumbKey(viewState.value)
    if (key === 'map.breadcrumb.districts') {
      return t(key, { city: t('filters.minsk') })
    }
    if (key.startsWith('map.regions.')) {
      return t(key)
    }
    return t(key)
  })

  function beginMapLoading() {
    mapLoadingDepth += 1
    isMapLoading.value = true
  }

  function endMapLoading() {
    mapLoadingDepth = Math.max(0, mapLoadingDepth - 1)
    isMapLoading.value = mapLoadingDepth > 0
  }

  function getMetroStation(listing: ListingDto) {
    if (!listing.metroStationId) {
      return undefined
    }
    return metroById.value.get(listing.metroStationId)
  }

  function getDistrictLabel(listing: ListingDto) {
    const district = listing.districtId === null
      ? undefined
      : districtById.value.get(listing.districtId)
    const city = listings.cities.find((item) => item.id === listing.cityId)
    if (!district || !city) {
      return undefined
    }
    return `${district.name}, ${city.name}`
  }

  function getMapContainerSize() {
    const element = mapPanelRef.value ?? mapRoot.value
    if (!element) {
      return null
    }

    return {
      width: element.clientWidth,
      height: element.clientHeight,
    }
  }

  function updatePopupPosition(measuredHeight?: number, markerPoint?: { x: number; y: number }) {
    const listing = selectedListing.value
    const container = getMapContainerSize()
    if (!map || !mapRoot.value || !listing || !container) {
      popupPosition.value = null
      return
    }

    const point = markerPoint
      ?? coordsToContainerPoint(map, listing.latitude, listing.longitude)
    if (!point) {
      popupPosition.value = null
      return
    }

    popupPosition.value = computeMapPopupPosition(point, container, {
      cardHeight: measuredHeight,
      cardWidth: popupPosition.value?.cardWidth,
    })
  }

  function disconnectPopupResizeObserver() {
    popupResizeObserver?.disconnect()
    popupResizeObserver = null
  }

  function observePopupCardSize() {
    disconnectPopupResizeObserver()
    const cardElement = popupCardRef.value?.getRootElement()
    if (!cardElement || typeof ResizeObserver === 'undefined') {
      return
    }

    popupResizeObserver = new ResizeObserver(() => {
      const height = cardElement.offsetHeight
      if (height > 0) {
        updatePopupPosition(height)
      }
    })
    popupResizeObserver.observe(cardElement)
  }

  function closeListingPopup() {
    listings.selectListing(null)
    popupPosition.value = null
    listingCardLoading.value = false
    listingLayer?.setSelectedId(null)
  }

  function openListingDetail(id: number) {
    void listings.openDetailListing(id)
  }

  function findListingById(id: number): ListingDto | null {
    return mapItems.value.find((item) => item.id === id)
      ?? listings.items.find((item) => item.id === id)
      ?? (listings.detailListing?.id === id ? listings.detailListing : null)
  }

  function getStatsForFeature(props: MapZoneProperties): MapZoneStats {
    if (props.level === 'region') {
      return getRegionStats(props.slug, citySlugToId.value, cityStats.value)
    }

    if (props.level === 'city') {
      const cityId = citySlugToId.value.get(props.citySlug ?? props.slug)
      if (cityId === undefined) {
        return emptyStats()
      }
      return cityStats.value.get(cityId) ?? emptyStats()
    }

    const districtId = districtSlugToId.value.get(props.districtSlug ?? props.slug)
    if (districtId === undefined) {
      return emptyStats()
    }
    return districtStats.value.get(districtId) ?? emptyStats()
  }

  function showTooltip(props: MapZoneProperties, pointer: { x: number; y: number } | null) {
    if (!mapRoot.value || !pointer) {
      return
    }

    const stats = getStatsForFeature(props)
    const rect = mapRoot.value.getBoundingClientRect()
    tooltip.value = {
      name: props.name,
      count: stats.count,
      avgPrice: stats.avgPrice,
      avgPricePerSqm: stats.avgPricePerSqm,
      x: pointer.x - rect.left,
      y: pointer.y - rect.top,
    }
  }

  function hideTooltip() {
    if (tooltipMoveFrame !== null) {
      cancelAnimationFrame(tooltipMoveFrame)
      tooltipMoveFrame = null
    }
    pendingTooltipMove = null
    tooltip.value = null
  }

  function scheduleTooltipMove(
    props: MapZoneProperties,
    pointer: { x: number; y: number } | null,
  ) {
    pendingTooltipMove = { props, pointer }
    if (tooltipMoveFrame !== null) {
      return
    }

    tooltipMoveFrame = requestAnimationFrame(() => {
      tooltipMoveFrame = null
      const pending = pendingTooltipMove
      pendingTooltipMove = null
      if (pending) {
        showTooltip(pending.props, pending.pointer)
      }
    })
  }

  async function ensureMinskDistrictsLoaded() {
    const cityId = citySlugToId.value.get('minsk')
    if (cityId === undefined) {
      return
    }

    if (listings.districts.length === 0 || listings.districts[0]?.cityId !== cityId) {
      await listings.loadDistricts(cityId)
    }
  }

  async function applyCityFilter(citySlug: string) {
    const cityId = citySlugToId.value.get(citySlug)
    if (cityId === undefined) {
      return false
    }

    withFilterSync(() => {
      listings.cityId = cityId
      listings.districtId = undefined
      listings.regionSlug = CITY_TO_REGION[citySlug] ?? listings.regionSlug
    })

    await listings.loadDistricts(cityId)
    return true
  }

  function syncZoneInteractionState() {
    zoneLayer?.setInteractionState(
      hoveredSlug.value,
      selectedSlug.value,
      viewState.value.mode === 'districts' ? selectedSlug.value : null,
    )
  }

  async function applyDistrictFilter(districtSlug: string) {
    await ensureMinskDistrictsLoaded()

    const districtId = districtSlugToId.value.get(districtSlug)
    if (districtId === undefined || !geoCache) {
      return false
    }

    const cityId = citySlugToId.value.get('minsk')

    withFilterSync(() => {
      if (cityId !== undefined) {
        listings.cityId = cityId
      }
      listings.districtId = districtId
      listings.regionSlug = 'minsk-city'
      selectedSlug.value = districtSlug
    })

    hoveredSlug.value = null
    hideTooltip()
    closeListingPopup()
    await listings.search()
    await renderCurrentLevel({ syncMarkers: 'reuse' })
    return true
  }

  function getRegionCityIds(regionSlug: string): number[] {
    const citySlugs = (REGION_CITY_SLUGS as Record<string, readonly string[]>)[regionSlug] ?? []
    return citySlugs
      .map((slug) => citySlugToId.value.get(slug))
      .filter((id): id is number => id !== undefined)
  }

  function scheduleRenderListingMarkers() {
    if (markerRenderFrame !== null) {
      return
    }

    markerRenderFrame = requestAnimationFrame(() => {
      markerRenderFrame = null
      renderListingMarkers()
    })
  }

  function renderListingMarkers() {
    if (!map || !listingLayer) {
      return
    }

    if (!shouldShowListingMarkers(viewState.value.mode, listings.cityId, selectedSlug.value)) {
      listingLayer.clear()
      return
    }

    listingLayer.setSelectedId(selectedListingId.value)
    listingLayer.sync(
      mapItems.value,
      viewState.value.mode,
      clusterOptionsForView(viewState.value.mode, selectedSlug.value),
      viewState.value.citySlug,
      selectedSlug.value,
    )
  }

  async function syncMapMarkersForView() {
    const view = viewState.value
    listings.setMapNationwide(shouldUseNationwideMarkers(view))

    suppressMarkerWatch = true
    try {
      if (view.mode === 'country') {
        await listings.loadMapMarkers()
      } else if (view.mode === 'cities' && view.regionSlug && !view.citySlug) {
        await listings.loadMapMarkersForCityIds(getRegionCityIds(view.regionSlug))
      } else {
        await listings.loadMapMarkers()
      }

      renderListingMarkers()
    } finally {
      await nextTick()
      suppressMarkerWatch = false
    }
  }

  function reuseMapMarkersForView() {
    listings.setMapNationwide(shouldUseNationwideMarkers(viewState.value))
    scheduleRenderListingMarkers()
  }

  function fitMapToViewCollection(collection: FeatureCollection) {
    if (!zoneLayer || !map || !geoCache) {
      return
    }

    const view = viewState.value

    if (view.mode === 'country') {
      fitMapToBounds(map, getBelarusBoundsPoints(), fitMaxZoomForView(view))
      return
    }

    if (view.mode === 'cities' && view.citySlug) {
      zoneLayer.zoomToSlug(collection, view.citySlug, 'slug', 14)
      return
    }

    if (view.mode === 'districts' && selectedSlug.value) {
      zoneLayer.zoomToSlug(collection, selectedSlug.value, 'slug', 16)
      return
    }

    const fitCollection = getFitCollectionForView(view, geoCache)
    zoneLayer.fitCollection(fitCollection, fitMaxZoomForView(view))
  }

  async function renderCurrentLevel(options?: { fitMap?: boolean; syncMarkers?: 'fetch' | 'reuse' }) {
    if (!map || !zoneLayer || !geoCache) {
      return
    }

    if (viewState.value.mode === 'districts') {
      await ensureMinskDistrictsLoaded()
    }

    hideTooltip()

    const collection = getCollectionForView(viewState.value, geoCache)
    const outline = getOutlineForView(viewState.value, geoCache)

    syncZoneInteractionState()

    if (shouldShowZonePolygons(viewState.value.mode, viewState.value.citySlug, selectedSlug.value)) {
      zoneLayer.render(
        collection,
        viewState.value.mode,
        outline,
        viewState.value.citySlug,
        selectedSlug.value,
      )
    } else {
      zoneLayer.clear()
    }

    if (options?.fitMap !== false) {
      fitMapToViewCollection(collection)
    }

    if (options?.syncMarkers === 'reuse') {
      reuseMapMarkersForView()
    } else {
      await syncMapMarkersForView()
    }
  }

  function zoomToClusterPoints(points: number[][]) {
    if (!map || points.length === 0) {
      return
    }
    const bounds = points
      .filter((point): point is [number, number] => point.length >= 2)
      .map((point): [number, number] => [point[0], point[1]])
    if (bounds.length === 0) {
      return
    }
    fitMapToBounds(map, bounds, 16, 280)
  }

  async function handleClusterClick(payload: ListingClusterClickPayload) {
    if (!map) {
      return
    }

    const clusterListings = payload.listingIds
      .map((id) => findListingById(id))
      .filter((item): item is ListingDto => item !== null)

    const citiesById = new Map(
      listings.cities.map((city) => [city.id, { slug: city.slug, regionSlug: city.regionSlug }]),
    )
    const focus = resolveClusterLocationFocus(
      clusterListings.map((item) => ({
        cityId: item.cityId,
        districtId: item.districtId,
      })),
      citiesById,
    )

    await runAsMapNavigation(async () => {
      closeListingPopup()

      if (focus.kind === 'region') {
        const alreadyFocused =
          listings.regionSlug === focus.regionSlug
          && listings.cityId === undefined
          && listings.districtId === undefined
        if (!alreadyFocused) {
          viewState.value = viewFromRegionSlug(focus.regionSlug)
          selectedSlug.value = null
          withFilterSync(() => {
            listings.regionSlug = focus.regionSlug
            listings.cityId = undefined
            listings.districtId = undefined
          })
          await listings.search()
          await renderCurrentLevel({ syncMarkers: 'reuse' })
          return
        }
        zoomToClusterPoints(payload.points)
        return
      }

      if (focus.kind === 'city') {
        const alreadyFocused =
          listings.cityId === focus.cityId
          && listings.districtId === undefined
        if (!alreadyFocused) {
          const nextView = viewFromCitySlug(focus.citySlug)
          if (nextView) {
            viewState.value = nextView
          } else {
            viewState.value = {
              mode: 'cities',
              regionSlug: focus.regionSlug,
              citySlug: focus.citySlug,
            }
          }
          selectedSlug.value = viewState.value.mode === 'cities' ? focus.citySlug : null
          await applyCityFilter(focus.citySlug)
          await listings.search()
          await renderCurrentLevel({ syncMarkers: 'reuse' })
          return
        }
        zoomToClusterPoints(payload.points)
        return
      }

      if (focus.kind === 'district') {
        const alreadyFocused = listings.districtId === focus.districtId
        if (!alreadyFocused) {
          if (focus.citySlug === 'minsk') {
            await listings.loadDistricts(focus.cityId)
            const district = listings.districts.find((item) => item.id === focus.districtId)
            if (district) {
              viewState.value = {
                mode: 'districts',
                regionSlug: 'minsk-city',
                citySlug: 'minsk',
              }
              await applyDistrictFilter(district.slug)
              return
            }
          }

          const nextView = viewFromCitySlug(focus.citySlug)
          if (nextView) {
            viewState.value = nextView
          } else {
            viewState.value = {
              mode: 'cities',
              regionSlug: focus.regionSlug,
              citySlug: focus.citySlug,
            }
          }
          selectedSlug.value = null
          withFilterSync(() => {
            listings.cityId = focus.cityId
            listings.districtId = focus.districtId
            listings.regionSlug = focus.regionSlug
          })
          await listings.loadDistricts(focus.cityId)
          await listings.search()
          await renderCurrentLevel({ fitMap: false, syncMarkers: 'reuse' })
          zoomToClusterPoints(payload.points)
          return
        }
        zoomToClusterPoints(payload.points)
        return
      }

      zoomToClusterPoints(payload.points)
    })
  }

  async function handleZoneClick(props: MapZoneProperties) {
    const clickKey = `${props.level}:${props.slug}`
    const now = Date.now()
    if (clickKey === lastZoneClickKey && now - lastZoneClickAt < 350) {
      return
    }
    lastZoneClickKey = clickKey
    lastZoneClickAt = now

    await runAsMapNavigation(async () => {
      if (props.level === 'region') {
        viewState.value = viewAfterRegionClick(props)
        if (viewState.value.mode === 'districts') {
          await applyCityFilter('minsk')
        } else {
          withFilterSync(() => {
            listings.regionSlug = viewState.value.regionSlug ?? undefined
            listings.cityId = undefined
            listings.districtId = undefined
          })
        }
        await listings.search()
        await renderCurrentLevel({ syncMarkers: 'reuse' })
        return
      }

      if (props.level === 'city') {
        const citySlug = props.citySlug ?? props.slug
        const nextView = viewAfterCityClick(props)

        hoveredSlug.value = null
        hideTooltip()
        closeListingPopup()

        if (nextView) {
          viewState.value = nextView
          selectedSlug.value = null
          await applyCityFilter('minsk')
          await listings.search()
          await renderCurrentLevel({ syncMarkers: 'reuse' })
          return
        }

        viewState.value = {
          mode: 'cities',
          regionSlug: viewState.value.regionSlug ?? CITY_TO_REGION[citySlug] ?? null,
          citySlug,
        }
        selectedSlug.value = citySlug
        await applyCityFilter(citySlug)
        await listings.search()
        await renderCurrentLevel({ syncMarkers: 'reuse' })
        return
      }

      if (props.level === 'district') {
        await applyDistrictFilter(props.districtSlug ?? props.slug)
      }
    })
  }

  async function goBack() {
    await runAsMapNavigation(async () => {
      if (viewState.value.mode === 'districts' && selectedSlug.value) {
        selectedSlug.value = null
        withFilterSync(() => {
          listings.districtId = undefined
        })
        await listings.search()
        await renderCurrentLevel({ syncMarkers: 'reuse' })
        return
      }

      const nextView = viewAfterBack(viewState.value)
      selectedSlug.value = null

      if (nextView.mode === 'country') {
        withFilterSync(() => {
          listings.regionSlug = undefined
          listings.cityId = undefined
          listings.districtId = undefined
        })
      } else if (nextView.mode === 'cities' && !nextView.citySlug) {
        withFilterSync(() => {
          listings.regionSlug = nextView.regionSlug ?? undefined
          listings.cityId = undefined
          listings.districtId = undefined
        })
      }

      viewState.value = nextView
      await listings.search()
      await renderCurrentLevel({ syncMarkers: 'reuse' })
    })
  }

  async function focusOnSelectedRegion() {
    if (!map || !geoCache) {
      return
    }

    const regionSlug = listings.regionSlug
    if (!regionSlug) {
      viewState.value = createCountryView()
      selectedSlug.value = null
      await renderCurrentLevel({ syncMarkers: 'reuse' })
      return
    }

    hideTooltip()
    listings.selectListing(null)
    selectedSlug.value = null
    const nextView = viewFromRegionSlug(regionSlug)
    if (!mapViewsEqual(viewState.value, nextView)) {
      viewState.value = nextView
    }
    await renderCurrentLevel({ syncMarkers: 'reuse' })
  }

  async function focusOnSelectedCity() {
    if (!map || !geoCache) {
      return
    }

    const city = listings.cities.find((item) => item.id === listings.cityId)
    if (!city) {
      return
    }

    hideTooltip()
    listings.selectListing(null)

    const nextView = viewFromCitySlug(city.slug)
    if (!nextView) {
      const center = getCityCenter(city.slug)
      if (center) {
        flyMapTo(map, center, 12)
      }
      reuseMapMarkersForView()
      return
    }

    if (!mapViewsEqual(viewState.value, nextView)) {
      viewState.value = nextView
    }

    if (listings.districtId && nextView.mode === 'districts') {
      const district = listings.districts.find((item) => item.id === listings.districtId)
      selectedSlug.value = district?.slug ?? null
    } else if (nextView.mode === 'cities' && nextView.citySlug) {
      selectedSlug.value = nextView.citySlug
    } else {
      selectedSlug.value = null
    }

    await renderCurrentLevel({ syncMarkers: 'reuse' })
  }

  async function selectListingFromMap(id: number, markerPoint?: { x: number; y: number }) {
    if (shouldToggleCloseOnReselect(selectedListingId.value, id)) {
      closeListingPopup()
      return
    }

    const listing = findListingById(id)
    if (!listing || !map) {
      return
    }
    const activeMap = map

    await runAsMapNavigation(async () => {
      listings.selectListing(id)
      listingLayer?.setSelectedId(id)
      updatePopupPosition(undefined, markerPoint)

      const city = listings.cities.find((item) => item.id === listing.cityId)
      const nextView = city ? viewFromListingClick(viewState.value.mode, city.slug) : null
      if (nextView && !mapViewsEqual(viewState.value, nextView)) {
        viewState.value = nextView
        if (nextView.mode === 'districts' && listing.districtId) {
          await ensureMinskDistrictsLoaded()
          const district = listings.districts.find((item) => item.id === listing.districtId)
          selectedSlug.value = district?.slug ?? null
        } else if (nextView.mode === 'cities' && nextView.citySlug) {
          selectedSlug.value = nextView.citySlug
        } else {
          selectedSlug.value = null
        }
        await renderCurrentLevel({ fitMap: false, syncMarkers: 'reuse' })
      }

      if (listings.cityId !== listing.cityId) {
        withFilterSync(() => {
          listings.cityId = listing.cityId
          listings.districtId = listing.districtId ?? undefined
          listings.regionSlug = nextView?.regionSlug ?? CITY_TO_REGION[city?.slug ?? ''] ?? listings.regionSlug
        })
        await listings.loadDistricts(listing.cityId)
      }

      flyMapTo(activeMap, [listing.latitude, listing.longitude], Math.max(activeMap.getZoom(), 15))

      await nextTick()
      updatePopupPosition()
      observePopupCardSize()
    })
  }

  function focusOnCoords(latitude: number, longitude: number, zoom = 16) {
    if (!map) {
      return
    }
    flyMapTo(map, [latitude, longitude], zoom)
  }

  async function focusListingOnMainMap(id: number) {
    let listing = findListingById(id)

    if (!listing) {
      try {
        await listings.openDetailListing(id)
        listing = listings.detailListing?.id === id ? listings.detailListing : null
      } catch {
        listing = null
      }
    }

    if (!listing || !map) {
      listings.clearMapFocusRequest()
      return
    }

    const activeMap = map
    const focusedListing = listing

    await runAsMapNavigation(async () => {
      listings.selectListing(focusedListing.id)
      listingLayer?.setSelectedId(focusedListing.id)

      const city = listings.cities.find((item) => item.id === focusedListing.cityId)
      const nextView = city ? viewFromCitySlug(city.slug) : null

      if (nextView) {
        const viewChanged = !mapViewsEqual(viewState.value, nextView)
        viewState.value = nextView

        if (nextView.mode === 'districts' && focusedListing.districtId) {
          await ensureMinskDistrictsLoaded()
          const district = listings.districts.find((item) => item.id === focusedListing.districtId)
          selectedSlug.value = district?.slug ?? null
        } else if (viewChanged) {
          selectedSlug.value = null
        }

        withFilterSync(() => {
          listings.cityId = focusedListing.cityId
          listings.regionSlug = nextView.regionSlug ?? undefined
          listings.districtId = selectedSlug.value
            ? listings.districts.find((item) => item.slug === selectedSlug.value)?.id
            : undefined
        })

        await listings.loadDistricts(focusedListing.cityId)
        await listings.search()
        await renderCurrentLevel({ fitMap: false, syncMarkers: 'reuse' })
      } else if (listings.cityId !== focusedListing.cityId) {
        withFilterSync(() => {
          listings.cityId = focusedListing.cityId
          listings.districtId = undefined
        })
        await listings.loadDistricts(focusedListing.cityId)
        await listings.search()
        reuseMapMarkersForView()
      }

      await nextTick()
      flyMapTo(activeMap, [focusedListing.latitude, focusedListing.longitude], Math.max(activeMap.getZoom(), 15))
      updatePopupPosition()
      observePopupCardSize()
    })

    listings.clearMapFocusRequest()
  }

  async function loadStats() {
    try {
      const stats = await fetchMapStats({ dealType: listings.dealType })
      cityStats.value = buildCityStatsMap(stats.cities)
      districtStats.value = buildDistrictStatsMap(stats.districts)
    } catch {
      cityStats.value = new Map()
      districtStats.value = new Map()
    }
  }

  function handleMapBackgroundClick(event: YandexMapEvent) {
    if (event.get('target') !== map) {
      return
    }

    hideTooltip()

    if (selectedListingId.value !== null) {
      closeListingPopup()
    }
  }

  function handleDocumentKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && selectedListingId.value !== null) {
      closeListingPopup()
    }
  }

  function onMapViewportChange() {
    updatePopupPosition()
  }

  function handleMapActionEnd() {
    onMapViewportChange()
  }

  onMounted(async () => {
    await nextTick()

    if (!mapRoot.value) {
      isMapLoading.value = false
      return
    }

    beginMapLoading()
    try {
      if (listings.cities.length === 0) {
        await listings.loadReferenceData()
      }

      geoCache = await preloadGeoData()
      map = await createYandexMap(mapRoot.value)

      zoneLayer = new YandexZoneLayer(map)
      listingLayer = new YandexListingLayer(map)
      listingLayer.setSelectHandler((id, point) => {
        void selectListingFromMap(id, point)
      })
      listingLayer.setClusterClickHandler((payload) => {
        void handleClusterClick(payload)
      })

      zoneLayer.setHandlers({
        onHover: (props, pointer) => {
          hoveredSlug.value = props.slug
          showTooltip(props, pointer)
        },
        onHoverMove: (props, pointer) => scheduleTooltipMove(props, pointer),
        onHoverEnd: () => {
          hoveredSlug.value = null
          hideTooltip()
        },
        onClick: (props) => {
          void handleZoneClick(props)
        },
      })

      map.events.add('actionend', handleMapActionEnd)
      map.events.add('click', handleMapBackgroundClick)
      document.addEventListener('keydown', handleDocumentKeydown)

      await loadStats()
      await nextTick()
      connectMapResizeObserver()
      syncMapContainerSize({ forceCountryFit: shouldFitCountryOverview() })

      if (listings.cityId !== undefined) {
        await focusOnSelectedCity()
      } else if (listings.regionSlug) {
        await focusOnSelectedRegion()
      } else {
        await renderCurrentLevel()
        syncMapContainerSize({ forceCountryFit: true })
      }
    } catch {
      map?.destroy()
      map = null
      zoneLayer = null
      listingLayer = null
      mapLoadError.value = true
    } finally {
      endMapLoading()
    }
  })

  watch(mapItems, () => {
    if (suppressMarkerWatch) {
      return
    }
    scheduleRenderListingMarkers()
  })

  watch(
    () => listings.mapFocusListingId,
    (id) => {
      if (id !== null) {
        void focusListingOnMainMap(id)
      }
    },
  )

  watch(selectedListingId, (id) => {
    if (id === null) {
      popupPosition.value = null
      disconnectPopupResizeObserver()
    } else {
      updatePopupPosition()
    }
    listingLayer?.setSelectedId(id)
    scheduleRenderListingMarkers()
  })

  watch(selectedListing, async () => {
    updatePopupPosition()
    await nextTick()
    observePopupCardSize()
  })

  watch(() => listings.dealType, async () => {
    await loadStats()
    await syncMapMarkersForView()
  })

  watch(() => listings.cityId, async (cityId, previousCityId) => {
    if (cityId === previousCityId || !map || filterSyncLock || mapNavigationLock) {
      return
    }

    await runAsMapNavigation(async () => {
      if (cityId === undefined) {
        selectedSlug.value = null
        withFilterSync(() => {
          listings.districtId = undefined
        })
        if (listings.regionSlug) {
          await focusOnSelectedRegion()
        } else {
          viewState.value = createCountryView()
          await renderCurrentLevel({ syncMarkers: 'reuse' })
        }
        return
      }

      await focusOnSelectedCity()
    })
  })

  watch(() => listings.regionSlug, async (regionSlug, previousRegionSlug) => {
    if (regionSlug === previousRegionSlug || !map || filterSyncLock || mapNavigationLock) {
      return
    }

    if (listings.cityId !== undefined) {
      return
    }

    await runAsMapNavigation(async () => {
      selectedSlug.value = null

      if (!regionSlug) {
        viewState.value = createCountryView()
        await renderCurrentLevel({ syncMarkers: 'reuse' })
        return
      }

      await focusOnSelectedRegion()
    })
  })

  watch(() => listings.districtId, async (districtId, previousDistrictId) => {
    if (districtId === previousDistrictId || !map || filterSyncLock || mapNavigationLock || !geoCache) {
      return
    }

    await runAsMapNavigation(async () => {
      if (viewState.value.mode !== 'districts' || viewState.value.citySlug !== 'minsk') {
        if (listings.cityId !== undefined) {
          await focusOnSelectedCity()
        } else {
          reuseMapMarkersForView()
        }
        return
      }

      if (!districtId) {
        selectedSlug.value = null
        await renderCurrentLevel({ syncMarkers: 'reuse' })
        return
      }

      const district = listings.districts.find((item) => item.id === districtId)
      if (!district) {
        return
      }

      selectedSlug.value = district.slug
      await renderCurrentLevel({ syncMarkers: 'reuse' })
    })
  })

  onUnmounted(() => {
    if (markerRenderFrame !== null) {
      cancelAnimationFrame(markerRenderFrame)
    }
    if (tooltipMoveFrame !== null) {
      cancelAnimationFrame(tooltipMoveFrame)
    }
    document.removeEventListener('keydown', handleDocumentKeydown)
    disconnectPopupResizeObserver()
    disconnectMapResizeObserver()
    zoneLayer?.destroy()
    listingLayer?.destroy()
    map?.destroy()
    map = null
    zoneLayer = null
    listingLayer = null
  })

  return {
    isMapLoading,
    mapLoadError,
    viewState,
    tooltip,
    popupPosition,
    popupCardRef,
    listingCardLoading,
    selectedListing,
    breadcrumb,
    goBack,
    closeListingPopup,
    openListingDetail,
    focusOnCoords,
    getMetroStation,
    getDistrictLabel,
  }
}
