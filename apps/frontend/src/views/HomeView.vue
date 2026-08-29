<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import FilterBar from '@/components/FilterBar.vue'
import ListingList from '@/components/ListingList.vue'
import ListingDetailModal from '@/components/ListingDetailModal.vue'
import MapPanel from '@/components/MapPanel.vue'
import SeoPageHeading from '@/modules/seo/components/SeoPageHeading.vue'
import { getPageH1 } from '@/modules/seo'
import { listingSeoFromDistrictLabel } from '@/modules/seo/listingSeo'
import { peekSeoOverrides, seoOverridesVersion } from '@/modules/seo/seoOverrides'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import { useListingsStore } from '@/stores/listings'

const route = useRoute()
const router = useRouter()
const { locale } = useI18n()
const listings = useListingsStore()

onMounted(() => {
  void listings.initializeHome()
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

const listingSeoContext = computed(() => {
  const listing = detailListing.value
  if (!listing) {
    return null
  }

  return listingSeoFromDistrictLabel(listing, detailDistrictName.value)
})

const pageH1 = computed(() => {
  void seoOverridesVersion.value
  const currentLocale = locale.value === 'en' ? 'en' : 'ru'
  return getPageH1(route.path, currentLocale, peekSeoOverrides(currentLocale))
})

useRoutePageSeo({ listing: listingSeoContext })

watch(
  () => route.name,
  (name) => {
    if (name !== 'listing-detail' && listings.detailListingId !== null) {
      listings.closeDetailListing()
    }
  },
)

watch(
  () => route.params.id,
  (id) => {
    if (route.name !== 'listing-detail' || typeof id !== 'string') {
      return
    }

    const listingId = Number(id)
    if (!Number.isFinite(listingId)) {
      void router.replace({ name: 'home' })
      return
    }

    if (listings.detailListingId !== listingId) {
      void listings.openDetailListing(listingId)
    }
  },
  { immediate: true },
)

function closeDetailModal() {
  listings.closeDetailListing()
  if (route.name === 'listing-detail') {
    void router.replace({ name: 'home' })
  }
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
  <div class="home">
    <SeoPageHeading :title="pageH1" />
    <FilterBar />
    <main class="page-shell home__main">
      <ListingList show-more-mode="navigate" />
      <MapPanel />
    </main>

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
.home {
  display: flex;
  flex-direction: column;
  flex: 1 0 auto;
  min-height: 100%;
  background: var(--figma-surface);
}

.home__main {
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex: 1 1 auto;
  min-height: 0;
  padding-top: 0;
  padding-bottom: 24px;
  background: var(--figma-surface);
}

@media (min-width: 1280px) {
  .home__main {
    flex-direction: row;
    align-items: stretch;
    min-height: var(--figma-map-min-height);
  }
}
</style>
