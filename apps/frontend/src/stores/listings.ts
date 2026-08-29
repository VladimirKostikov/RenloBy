import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchListings, fetchListing } from '@/api/listings'
import { fetchCities, fetchDistricts, fetchMetroStations } from '@/api/reference'
import type {
  CityDto,
  DealType,
  DistrictDto,
  ListingDto,
  ListingType,
  MetroStationDto,
} from '@/types'
import type { CatalogCategory, RentTerm } from '@/lib/catalogListing'
import { resolveCategoryDealType, resolveCategoryListingType } from '@/lib/catalogListing'
import { buildListingSearchParams, type ListingSortOption } from '@/lib/listingSearchParams'

export const HOME_LIST_LIMIT = 20
export const HOME_NATIONWIDE_LIST_LIMIT = 10
export const CATALOG_LIST_LIMIT = 6

export const useListingsStore = defineStore('listings', () => {
  const items = ref<ListingDto[]>([])
  const mapItems = ref<ListingDto[]>([])
  const total = ref(0)
  const page = ref(1)
  const limit = ref(HOME_NATIONWIDE_LIST_LIMIT)
  const loading = ref(false)
  const dealType = ref<DealType>('sale')
  const listingType = ref<ListingType | undefined>(undefined)
  const regionSlug = ref<string | undefined>()
  const cityId = ref<number | undefined>()
  const districtId = ref<number | undefined>()
  const rooms = ref<number | undefined>()
  const minArea = ref<number | undefined>()
  const maxArea = ref<number | undefined>()
  const minPrice = ref<number | undefined>()
  const maxPrice = ref<number | undefined>()
  const verifiedOnly = ref(false)
  const floor = ref<number | undefined>()
  const searchQuery = ref('')
  const sort = ref<ListingSortOption>('newest')
  const catalogCategory = ref<CatalogCategory>('all')
  const rentTerm = ref<RentTerm>('long')
  const rentDeposit = ref(false)
  const rentUtilitiesIncluded = ref(false)
  const rentNoCommission = ref(false)
  const saleNoAgents = ref(false)
  const saleFromOwner = ref(false)
  const saleWithRenovation = ref(false)
  const catalogMode = ref(false)
  const catalogBaseDealType = ref<DealType>('rent')
  const commercialCatalogActive = ref(false)

  const cities = ref<CityDto[]>([])
  const districts = ref<DistrictDto[]>([])
  const metroStations = ref<MetroStationDto[]>([])
  const selectedListingId = ref<number | null>(null)
  const detailListingId = ref<number | null>(null)
  const detailListing = ref<ListingDto | null>(null)
  const detailLoading = ref(false)
  const mapFocusListingId = ref<number | null>(null)
  const error = ref<string | null>(null)
  const mapNationwide = ref(true)
  let searchRequestGeneration = 0
  let mapMarkerRequestGeneration = 0

  function resolveListLimit(): number {
    if (catalogMode.value) {
      return CATALOG_LIST_LIMIT
    }
    return mapNationwide.value ? HOME_NATIONWIDE_LIST_LIMIT : HOME_LIST_LIMIT
  }

  function syncListLimit() {
    limit.value = resolveListLimit()
  }

  function setMapNationwide(value: boolean) {
    mapNationwide.value = value
    syncListLimit()
  }

  function selectListing(id: number | null) {
    selectedListingId.value = id
  }

  function findListingLocally(id: number): ListingDto | undefined {
    return items.value.find((item) => item.id === id) ?? mapItems.value.find((item) => item.id === id)
  }

  async function openDetailListing(id: number) {
    detailListingId.value = id
    selectListing(id)
    detailLoading.value = true

    const cached = findListingLocally(id)
    if (cached) {
      detailListing.value = cached
    }

    try {
      detailListing.value = await fetchListing(id)
    } catch {
      if (!cached) {
        detailListing.value = null
        detailListingId.value = null
      }
    } finally {
      detailLoading.value = false
    }
  }

  function closeDetailListing() {
    detailListingId.value = null
    detailListing.value = null
    detailLoading.value = false
  }

  function focusListingOnMap(id: number) {
    mapFocusListingId.value = id
    selectListing(id)
  }

  function clearMapFocusRequest() {
    mapFocusListingId.value = null
  }

  function setDefaultCity() {
    regionSlug.value = undefined
    cityId.value = undefined
    districtId.value = undefined
    setMapNationwide(true)
  }

  async function loadReferenceData() {
    try {
      cities.value = await fetchCities()
      districts.value = await fetchDistricts(cityId.value)
      metroStations.value = await fetchMetroStations(cityId.value)
    } catch {
      cities.value = []
      districts.value = []
      metroStations.value = []
    }
  }

  async function initialize() {
    await loadReferenceData()
    await search()
  }

  async function initializeHome() {
    setCatalogMode(false)
    setMapNationwide(true)
    regionSlug.value = undefined
    cityId.value = undefined
    districtId.value = undefined
    await loadReferenceData()
    sort.value = 'random'
    await search()
    sort.value = 'newest'
    await loadMapMarkers()
  }

  async function loadDistricts(city?: number) {
    districts.value = await fetchDistricts(city)
    metroStations.value = await fetchMetroStations(city)
  }

  function buildSearchParams(pageNum: number, pageLimit: number) {
    return buildListingSearchParams(
      {
        dealType: dealType.value,
        listingType: listingType.value,
        cityId: cityId.value,
        regionSlug: regionSlug.value,
        districtId: districtId.value,
        rooms: rooms.value,
        floor: floor.value,
        minArea: minArea.value,
        maxArea: maxArea.value,
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
        verifiedOnly: verifiedOnly.value,
        searchQuery: searchQuery.value,
        rentTerm: rentTerm.value,
        rentDeposit: rentDeposit.value,
        rentUtilitiesIncluded: rentUtilitiesIncluded.value,
        rentNoCommission: rentNoCommission.value,
        saleNoAgents: saleNoAgents.value,
        saleFromOwner: saleFromOwner.value,
        saleWithRenovation: saleWithRenovation.value,
        sort: sort.value,
      },
      pageNum,
      pageLimit,
    )
  }

  async function loadMapMarkers() {
    if (catalogMode.value) {
      return
    }

    if (regionSlug.value === 'minsk-region' && cityId.value === undefined) {
      const regionCityIds = cities.value
        .filter((city) => city.regionSlug === 'minsk-region' || city.slug === 'minsk')
        .map((city) => city.id)

      if (regionCityIds.length > 0) {
        await loadMapMarkersForCityIds(regionCityIds)
        return
      }
    }

    const generation = ++mapMarkerRequestGeneration
    try {
      const nationwide =
        mapNationwide.value && cityId.value === undefined && regionSlug.value === undefined
      const baseParams = {
        ...buildSearchParams(1, 100),
        cityId: nationwide ? undefined : cityId.value,
        regionSlug: nationwide ? undefined : regionSlug.value,
        // District selection narrows the list, but the map keeps the full city context.
        districtId: undefined,
      }

      const nextItems = await fetchMapMarkerItems(baseParams)
      if (generation === mapMarkerRequestGeneration) {
        mapItems.value = nextItems
      }
    } catch {
      if (generation === mapMarkerRequestGeneration) {
        mapItems.value = []
      }
    }
  }

  async function loadMapMarkersForCityIds(targetCityIds: number[]) {
    if (catalogMode.value) {
      return
    }

    if (targetCityIds.length === 0) {
      mapMarkerRequestGeneration += 1
      mapItems.value = []
      return
    }

    const generation = ++mapMarkerRequestGeneration
    try {
      const batches = await Promise.all(
        targetCityIds.map(async (targetCityId) => {
          const params = {
            ...buildSearchParams(1, 100),
            cityId: targetCityId,
            regionSlug: undefined,
            districtId: undefined,
          }
          return fetchMapMarkerItems(params)
        }),
      )

      const merged = new Map<number, ListingDto>()
      for (const batch of batches) {
        for (const item of batch) {
          merged.set(item.id, item)
        }
      }

      if (generation === mapMarkerRequestGeneration) {
        mapItems.value = [...merged.values()]
      }
    } catch {
      if (generation === mapMarkerRequestGeneration) {
        mapItems.value = []
      }
    }
  }

  async function fetchMapMarkerItems(
    baseParams: ReturnType<typeof buildSearchParams>,
  ): Promise<ListingDto[]> {
    const collected: ListingDto[] = []
    let pageNum = 1
    let totalCount = Number.POSITIVE_INFINITY

    while (collected.length < totalCount && pageNum <= 10) {
      const result = await fetchListings({ ...baseParams, page: pageNum })
      const batch = Array.isArray(result.items) ? result.items : []
      collected.push(...batch)
      totalCount = typeof result.total === 'number' ? result.total : batch.length
      if (batch.length === 0) {
        break
      }
      pageNum += 1
    }

    return collected.filter(
      (item) => Number.isFinite(item.latitude) && Number.isFinite(item.longitude),
    )
  }

  async function search(nextPage = 1, append = false) {
    const generation = ++searchRequestGeneration
    loading.value = true
    error.value = null
    page.value = nextPage
    try {
      const result = await fetchListings(buildSearchParams(page.value, limit.value))
      if (generation !== searchRequestGeneration) {
        return
      }

      const nextItems = Array.isArray(result.items) ? result.items : []
      items.value = append ? [...items.value, ...nextItems] : nextItems
      total.value = typeof result.total === 'number' ? result.total : nextItems.length
      if (!append) {
        await loadMapMarkers()
      }
    } catch {
      if (generation !== searchRequestGeneration) {
        return
      }

      error.value = 'search_failed'
      if (!append) {
        items.value = []
        mapMarkerRequestGeneration += 1
        mapItems.value = []
        total.value = 0
      }
    } finally {
      if (generation === searchRequestGeneration) {
        loading.value = false
      }
    }
  }

  async function loadMore() {
    if (loading.value || items.value.length >= total.value) {
      return
    }
    await search(page.value + 1, true)
  }

  function setDealType(value: DealType) {
    dealType.value = value
    if (commercialCatalogActive.value) {
      catalogBaseDealType.value = value
      listingType.value = 'commercial'
    }
  }

  function resetFilters() {
    listingType.value = commercialCatalogActive.value ? 'commercial' : undefined
    if (catalogMode.value) {
      setDefaultCity()
    } else {
      regionSlug.value = undefined
      cityId.value = undefined
    }
    districtId.value = undefined
    rooms.value = undefined
    minArea.value = undefined
    maxArea.value = undefined
    minPrice.value = undefined
    maxPrice.value = undefined
    verifiedOnly.value = catalogMode.value && catalogBaseDealType.value === 'rent'
    floor.value = undefined
    searchQuery.value = ''
    catalogCategory.value = commercialCatalogActive.value ? 'commercial' : 'all'
    rentTerm.value = 'long'
    rentDeposit.value = false
    rentUtilitiesIncluded.value = false
    rentNoCommission.value = false
    saleNoAgents.value = false
    saleFromOwner.value = false
    saleWithRenovation.value = false
  }

  function setCatalogMode(value: boolean) {
    catalogMode.value = value
    syncListLimit()
    if (!value) {
      verifiedOnly.value = false
      commercialCatalogActive.value = false
    }
  }

  async function applyCatalogCategory(category: CatalogCategory) {
    if (commercialCatalogActive.value) {
      catalogCategory.value = 'commercial'
      listingType.value = 'commercial'
      dealType.value = catalogBaseDealType.value
      await search()
      return
    }
    catalogCategory.value = category
    dealType.value = resolveCategoryDealType(category, catalogBaseDealType.value)
    listingType.value = resolveCategoryListingType(category)
    await search()
  }

  async function initializeRentCatalog() {
    setCatalogMode(true)
    commercialCatalogActive.value = false
    catalogBaseDealType.value = 'rent'
    dealType.value = 'rent'
    catalogCategory.value = 'all'
    listingType.value = undefined
    verifiedOnly.value = true
    rentTerm.value = 'long'
    await loadReferenceData()
    setDefaultCity()
    await loadDistricts(cityId.value)
    await search()
  }

  async function initializeSaleCatalog() {
    setCatalogMode(true)
    commercialCatalogActive.value = false
    catalogBaseDealType.value = 'sale'
    dealType.value = 'sale'
    catalogCategory.value = 'all'
    listingType.value = undefined
    verifiedOnly.value = false
    await loadReferenceData()
    setDefaultCity()
    await loadDistricts(cityId.value)
    await search()
  }

  async function initializeCommercialCatalog() {
    setCatalogMode(true)
    commercialCatalogActive.value = true
    catalogBaseDealType.value = 'sale'
    dealType.value = 'sale'
    catalogCategory.value = 'commercial'
    listingType.value = 'commercial'
    verifiedOnly.value = false
    rentTerm.value = 'long'
    await loadReferenceData()
    setDefaultCity()
    await loadDistricts(cityId.value)
    await search()
  }

  function getFilterSnapshot(): Record<string, unknown> {
    return {
      dealType: dealType.value,
      listingType: listingType.value,
      regionSlug: regionSlug.value,
      cityId: cityId.value,
      districtId: districtId.value,
      rooms: rooms.value,
      minArea: minArea.value,
      maxArea: maxArea.value,
      minPrice: minPrice.value,
      maxPrice: maxPrice.value,
      verifiedOnly: verifiedOnly.value,
      floor: floor.value,
      searchQuery: searchQuery.value,
      catalogCategory: catalogCategory.value,
      rentTerm: rentTerm.value,
    }
  }

  return {
    items,
    mapItems,
    total,
    page,
    limit,
    loading,
    dealType,
    listingType,
    regionSlug,
    cityId,
    districtId,
    rooms,
    minArea,
    maxArea,
    minPrice,
    maxPrice,
    verifiedOnly,
    floor,
    searchQuery,
    sort,
    catalogCategory,
    rentTerm,
    rentDeposit,
    rentUtilitiesIncluded,
    rentNoCommission,
    saleNoAgents,
    saleFromOwner,
    saleWithRenovation,
    catalogMode,
    catalogBaseDealType,
    commercialCatalogActive,
    cities,
    districts,
    metroStations,
    selectedListingId,
    detailListingId,
    detailListing,
    detailLoading,
    mapFocusListingId,
    error,
    mapNationwide,
    selectListing,
    openDetailListing,
    closeDetailListing,
    focusListingOnMap,
    clearMapFocusRequest,
    loadReferenceData,
    loadDistricts,
    initialize,
    initializeHome,
    search,
    loadMapMarkers,
    loadMapMarkersForCityIds,
    loadMore,
    setDealType,
    setCatalogMode,
    applyCatalogCategory,
    initializeRentCatalog,
    initializeSaleCatalog,
    initializeCommercialCatalog,
    setMapNationwide,
    resetFilters,
    getFilterSnapshot,
  }
})
