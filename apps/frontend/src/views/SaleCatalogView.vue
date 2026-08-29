<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import CatalogGrid from '@/components/CatalogGrid.vue'
import CatalogSidebarFilters from '@/components/catalog/CatalogSidebarFilters.vue'
import CatalogToolbar from '@/components/catalog/CatalogToolbar.vue'
import ListingDetailModal from '@/components/ListingDetailModal.vue'
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

onMounted(async () => {
  await listings.initializeSaleCatalog()
})

onBeforeUnmount(() => {
  listings.setCatalogMode(false)
})

const pageH1 = computed(() => {
  void seoOverridesVersion.value
  const currentLocale = locale.value === 'en' ? 'en' : 'ru'
  return getPageH1(route.path, currentLocale, peekSeoOverrides(currentLocale))
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

useRoutePageSeo({ listing: listingSeoContext })

watch(
  () => route.name,
  (name) => {
    if (name !== 'sale-listing-detail' && listings.detailListingId !== null) {
      listings.closeDetailListing()
    }
  },
)

watch(
  () => route.params.id,
  (id) => {
    if (route.name !== 'sale-listing-detail' || typeof id !== 'string') {
      return
    }

    const listingId = Number(id)
    if (!Number.isFinite(listingId)) {
      void router.replace({ name: 'sale-catalog' })
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
  if (route.name === 'sale-listing-detail') {
    void router.replace({ name: 'sale-catalog' })
  }
}
</script>

<template>
  <div class="sale-catalog catalog-page">
    <SeoPageHeading :title="pageH1" />
    <main class="sale-catalog__main">
      <div class="page-shell sale-catalog__layout catalog-layout">
        <div class="catalog-layout__sidebar">
          <CatalogSidebarFilters deal-type="sale" />
        </div>
        <div class="sale-catalog__content">
          <CatalogToolbar deal-type="sale" compact />
          <CatalogGrid
            detail-route-name="sale-listing-detail"
            :columns="3"
            embedded
            compact
          />
        </div>
      </div>
    </main>

    <ListingDetailModal
      v-if="detailOpen && detailListing"
      :listing="detailListing"
      :metro-station="detailMetroStation"
      :district-name="detailDistrictName"
      :loading="listings.detailLoading"
      @close="closeDetailModal"
    />
  </div>
</template>

<style scoped>
.sale-catalog {
  --figma-catalog-sidebar-width: 340px;
  display: flex;
  flex-direction: column;
  min-height: 100%;
  background: var(--figma-surface);
}

.sale-catalog__main {
  flex: 1;
  background: var(--figma-page-bg);
}

.sale-catalog__layout {
  display: grid;
  grid-template-columns: minmax(240px, var(--figma-catalog-sidebar-width)) minmax(0, 1fr);
  gap: 24px;
  align-items: start;
  padding-top: 16px;
}

.sale-catalog__content {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-width: 0;
  overflow: hidden;
}

.sale-catalog__content :deep(.catalog-grid-section) {
  padding-top: 0;
}

@media (max-width: 1279px) {
  .sale-catalog__layout {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}

@media (max-width: 767px) {
  .sale-catalog__layout {
    gap: 12px;
    padding-top: 12px;
  }

  .sale-catalog__content {
    gap: 12px;
  }
}
</style>
