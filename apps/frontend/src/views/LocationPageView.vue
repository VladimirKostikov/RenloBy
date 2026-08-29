<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { fetchMapStats } from '@/api/map'
import FilterBar from '@/components/FilterBar.vue'
import ListingDetailModal from '@/components/ListingDetailModal.vue'
import ListingList from '@/components/ListingList.vue'
import MapPanel from '@/components/MapPanel.vue'
import LocationBreadcrumbs from '@/modules/location/components/LocationBreadcrumbs.vue'
import LocationCityGrid from '@/modules/location/components/LocationCityGrid.vue'
import LocationDistrictGrid from '@/modules/location/components/LocationDistrictGrid.vue'
import LocationHero from '@/modules/location/components/LocationHero.vue'
import { listingSeoFromDistrictLabel } from '@/modules/seo/listingSeo'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import {
  buildCityCards,
  buildDistrictCards,
  getCityStatsFromResponse,
  getDistrictStatsFromResponse,
  getRegionStatsFromResponse,
} from '@/modules/location/lib/locationStats'
import {
  cityHasDistricts,
  resolveLocation,
  resolveRegionLocation,
  type ResolvedLocation,
} from '@/modules/location/lib/resolveLocation'
import { useListingsStore } from '@/stores/listings'
import type { MapStatsResponse } from '@/types/map'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const listings = useListingsStore()

const loading = ref(true)
const notFound = ref(false)
const location = ref<ResolvedLocation | null>(null)
const mapStats = ref<MapStatsResponse>({ cities: [], districts: [] })

const isRegionRoute = computed(() => {
  const name = String(route.name ?? '')
  return name === 'region-location' || name === 'region-listing-detail'
})

const regionSlug = computed(() => {
  if (!isRegionRoute.value) {
    return ''
  }
  return String(route.params.regionSlug ?? '')
})

const citySlug = computed(() => {
  if (isRegionRoute.value) {
    return ''
  }
  return String(route.params.citySlug ?? '')
})

const districtSlug = computed(() => {
  if (isRegionRoute.value) {
    return undefined
  }
  const value = route.params.districtSlug
  return typeof value === 'string' && value.length > 0 ? value : undefined
})

const isRegionPage = computed(() => location.value?.kind === 'region')
const isDistrictPage = computed(() => location.value?.kind === 'district')
const isCityPage = computed(() => location.value?.kind === 'city')

const regionName = computed(() => {
  if (!location.value || location.value.kind !== 'region') {
    return ''
  }
  return t(`map.regions.${location.value.regionSlug}`)
})

const cityRegionSlug = computed(() => {
  if (!location.value || location.value.kind === 'region') {
    return undefined
  }
  return location.value.city.regionSlug
})

const cityRegionName = computed(() => {
  const slug = cityRegionSlug.value
  if (!slug) {
    return undefined
  }
  return t(`map.regions.${slug}`)
})

const pageTitle = computed(() => {
  if (!location.value) {
    return ''
  }

  if (location.value.kind === 'region') {
    return t('location.regionTitle', { name: regionName.value })
  }

  if (location.value.kind === 'district') {
    return t('location.districtTitle', {
      district: location.value.district.name,
      city: location.value.city.name,
    })
  }

  return t('location.cityTitle', { name: location.value.city.name })
})

const heroStats = computed(() => {
  if (!location.value) {
    return { count: 0, avgPrice: 0, avgPricePerSqm: 0 }
  }

  if (location.value.kind === 'region') {
    return getRegionStatsFromResponse(listings.cities, location.value.regionSlug, mapStats.value)
  }

  if (location.value.kind === 'district') {
    return getDistrictStatsFromResponse(location.value.district.id, mapStats.value)
  }

  return getCityStatsFromResponse(location.value.city.id, mapStats.value)
})

const districtCards = computed(() => {
  if (!location.value || location.value.kind !== 'city') {
    return []
  }

  return buildDistrictCards(listings.districts, location.value.city.id, mapStats.value)
})

const cityCards = computed(() => {
  if (!location.value || location.value.kind !== 'region') {
    return []
  }

  return buildCityCards(listings.cities, location.value.regionSlug, mapStats.value)
})

const showDistrictGrid = computed(() => {
  if (!location.value || location.value.kind !== 'city') {
    return false
  }

  return cityHasDistricts(listings.districts, location.value.city.id) && districtCards.value.length > 0
})

const showCityGrid = computed(() => isRegionPage.value && cityCards.value.length > 0)

const detailRouteName = computed(() => {
  if (isRegionPage.value) {
    return 'region-listing-detail'
  }
  if (isDistrictPage.value) {
    return 'district-listing-detail'
  }
  return 'city-listing-detail'
})

const detailListing = computed(() => listings.detailListing)
const detailOpen = computed(() => listings.detailListingId !== null && detailListing.value !== null)

const detailMetroStation = computed(() => {
  const listing = detailListing.value
  if (!listing?.metroStationId) {
    return undefined
  }
  return listings.metroStations.find((station) => station.id === listing.metroStationId)
})

const detailDistrictName = computed(() => {
  const listing = detailListing.value
  if (!listing) {
    return undefined
  }
  const district = listings.districts.find((item) => item.id === listing.districtId)
  const city = listings.cities.find((item) => item.id === listing.cityId)
  if (!district || !city) {
    return undefined
  }
  return `${district.name}, ${city.name}`
})

const locationSeoContext = computed(() => {
  if (!location.value) {
    return null
  }

  if (location.value.kind === 'region') {
    return {
      regionName: regionName.value,
      regionSlug: location.value.regionSlug,
    }
  }

  return {
    cityName: location.value.city.name,
    citySlug: location.value.city.slug,
    districtName: location.value.kind === 'district' ? location.value.district.name : undefined,
    districtSlug: location.value.kind === 'district' ? location.value.district.slug : undefined,
    regionName: cityRegionName.value,
    regionSlug: cityRegionSlug.value,
  }
})

const listingSeoContext = computed(() => {
  const listing = detailListing.value
  if (!listing) {
    return null
  }

  return listingSeoFromDistrictLabel(listing, detailDistrictName.value)
})

useRoutePageSeo({
  location: locationSeoContext,
  listing: listingSeoContext,
  noindex: computed(() => notFound.value),
})

async function applyLocationFilters(resolved: ResolvedLocation) {
  listings.setMapNationwide(false)

  if (resolved.kind === 'region') {
    listings.regionSlug = resolved.regionSlug
    listings.cityId = undefined
    listings.districtId = undefined
    await listings.search()
    return
  }

  listings.cityId = resolved.city.id
  listings.regionSlug = resolved.city.regionSlug
  listings.districtId = resolved.kind === 'district' ? resolved.district.id : undefined

  await listings.loadDistricts(resolved.city.id)
  await listings.search()
}

async function loadCityOrDistrictPage() {
  const resolved = resolveLocation(
    listings.cities,
    listings.districts,
    citySlug.value,
    districtSlug.value,
  )

  if (!resolved) {
    if (districtSlug.value) {
      const cityOnly = resolveLocation(listings.cities, listings.districts, citySlug.value)
      if (cityOnly && cityOnly.kind === 'city') {
        await listings.loadDistricts(cityOnly.city.id)
        const retry = resolveLocation(
          listings.cities,
          listings.districts,
          citySlug.value,
          districtSlug.value,
        )
        if (!retry) {
          notFound.value = true
          location.value = null
          return
        }
        location.value = retry
        mapStats.value = await fetchMapStats({ dealType: listings.dealType })
        await applyLocationFilters(retry)
        return
      }
    }

    notFound.value = true
    location.value = null
    return
  }

  if (resolved.kind === 'district' && listings.districts[0]?.cityId !== resolved.city.id) {
    await listings.loadDistricts(resolved.city.id)
    const retry = resolveLocation(
      listings.cities,
      listings.districts,
      citySlug.value,
      districtSlug.value,
    )
    if (!retry) {
      notFound.value = true
      location.value = null
      return
    }
    location.value = retry
    mapStats.value = await fetchMapStats({ dealType: listings.dealType })
    await applyLocationFilters(retry)
    return
  }

  location.value = resolved
  mapStats.value = await fetchMapStats({ dealType: listings.dealType })
  await applyLocationFilters(resolved)
}

async function loadRegionPage() {
  const resolved = resolveRegionLocation(regionSlug.value)
  if (!resolved) {
    notFound.value = true
    location.value = null
    return
  }

  location.value = resolved
  mapStats.value = await fetchMapStats({ dealType: listings.dealType })
  await applyLocationFilters(resolved)
}

async function loadLocationPage() {
  loading.value = true
  notFound.value = false

  try {
    if (listings.cities.length === 0) {
      await listings.loadReferenceData()
    }

    if (isRegionRoute.value) {
      await loadRegionPage()
      return
    }

    await loadCityOrDistrictPage()
  } catch {
    notFound.value = true
    location.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadLocationPage()
})

watch([citySlug, districtSlug, regionSlug, isRegionRoute], () => {
  void loadLocationPage()
})

watch(
  () => route.name,
  (name) => {
    const detailRoutes = ['city-listing-detail', 'district-listing-detail', 'region-listing-detail']
    if (!detailRoutes.includes(String(name)) && listings.detailListingId !== null) {
      listings.closeDetailListing()
    }
  },
)

watch(
  () => route.params.id,
  (id) => {
    const detailRoutes = ['city-listing-detail', 'district-listing-detail', 'region-listing-detail']
    if (!detailRoutes.includes(String(route.name)) || typeof id !== 'string') {
      return
    }

    const listingId = Number(id)
    if (!Number.isFinite(listingId)) {
      if (isRegionPage.value) {
        void router.replace({ name: 'region-location', params: { regionSlug: regionSlug.value } })
      } else {
        void router.replace({
          name: isDistrictPage.value ? 'district-location' : 'city-location',
          params: route.params,
        })
      }
      return
    }

    if (listings.detailListingId !== listingId) {
      void listings.openDetailListing(listingId)
    }
  },
  { immediate: true },
)

watch(
  () => listings.dealType,
  async () => {
    if (!location.value) {
      return
    }

    mapStats.value = await fetchMapStats({ dealType: listings.dealType })
    await listings.search()
  },
)

function closeDetailModal() {
  listings.closeDetailListing()
  const detailRoutes = ['city-listing-detail', 'district-listing-detail', 'region-listing-detail']
  if (!detailRoutes.includes(String(route.name))) {
    return
  }

  if (isRegionPage.value) {
    void router.replace({ name: 'region-location', params: { regionSlug: regionSlug.value } })
    return
  }

  if (isDistrictPage.value && districtSlug.value) {
    void router.replace({
      name: 'district-location',
      params: { citySlug: citySlug.value, districtSlug: districtSlug.value },
    })
    return
  }

  void router.replace({ name: 'city-location', params: { citySlug: citySlug.value } })
}

function openListingOnMap() {
  const listing = detailListing.value
  if (!listing) {
    return
  }

  listings.focusListingOnMap(listing.id)
}
</script>

<template>
  <div class="location-page">
    <main class="page-shell location-page__main">
      <div v-if="loading" class="location-page__state">{{ t('location.loading') }}</div>
      <div v-else-if="notFound" class="location-page__state">{{ t('location.notFound') }}</div>

      <template v-else-if="location">
        <LocationBreadcrumbs
          v-if="location.kind === 'region'"
          :region-slug="location.regionSlug"
          :region-name="regionName"
        />
        <LocationBreadcrumbs
          v-else
          :region-slug="cityRegionSlug"
          :region-name="cityRegionName"
          :city-slug="location.city.slug"
          :city-name="location.city.name"
          :district-name="location.kind === 'district' ? location.district.name : undefined"
        />

        <LocationHero
          :kind="location.kind"
          :title="pageTitle"
          :stats="heroStats"
        />

        <LocationCityGrid
          v-if="showCityGrid"
          :region-name="regionName"
          :items="cityCards"
        />

        <LocationDistrictGrid
          v-if="showDistrictGrid && location.kind === 'city'"
          :city-slug="location.city.slug"
          :city-name="location.city.name"
          :items="districtCards"
        />
      </template>
    </main>

    <FilterBar
      v-if="location"
      :hide-region="isRegionPage || isCityPage || isDistrictPage"
      :hide-city="isCityPage || isDistrictPage"
      :hide-district="isDistrictPage"
    />

    <section v-if="location" class="page-shell location-page__content">
      <ListingList :detail-route-name="detailRouteName" show-more-mode="navigate" />
      <MapPanel />
    </section>

    <ListingDetailModal
      v-if="detailOpen && detailListing"
      :listing="detailListing"
      :metro-station="detailMetroStation"
      :district-name="detailDistrictName"
      :loading="listings.detailLoading"
      @close="closeDetailModal"
      @show-on-map="openListingOnMap"
    />
  </div>
</template>

<style scoped>
.location-page {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  background: var(--figma-surface);
}

.location-page__main {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding-top: 20px;
  padding-bottom: 16px;
  background: var(--figma-page-bg);
}

.location-page__content {
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex: 1;
  min-height: 0;
  padding-top: 0;
  padding-bottom: 16px;
  background: var(--figma-page-bg);
}

.location-page__state {
  padding: 40px 0;
  text-align: center;
  color: rgba(0, 0, 0, 0.72);
}

@media (min-width: 1280px) {
  .location-page__content {
    flex-direction: row;
    align-items: stretch;
    min-height: var(--figma-map-min-height);
    padding-bottom: 24px;
  }
}
</style>
