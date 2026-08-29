<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { catalogPathFromRouteName, navigateTo } from '@/lib/fullPageNav'
import ListingDetailPanel from '@/components/ListingDetailPanel.vue'
import SeoPageHeading from '@/modules/seo/components/SeoPageHeading.vue'
import { listingSeoFromDistrictLabel } from '@/modules/seo/listingSeo'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import { useListingsStore } from '@/stores/listings'

const route = useRoute()

const { t } = useI18n()
const listings = useListingsStore()

const catalogRouteName = computed(() => String(route.meta.catalogRouteName ?? 'home'))

const detailListing = computed(() => listings.detailListing)

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

const pageTitle = computed(() => detailListing.value?.address ?? '')

useRoutePageSeo({ listing: listingSeoContext })

onMounted(async () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
  if (listings.cities.length === 0) {
    await listings.loadReferenceData()
  }
})

onBeforeUnmount(() => {
  listings.closeDetailListing()
})

watch(
  () => route.params.id,
  (id) => {
    if (typeof id !== 'string') {
      return
    }

    const listingId = Number(id)
    if (!Number.isFinite(listingId)) {
      navigateTo(catalogPathFromRouteName(catalogRouteName.value))
      return
    }

    if (listings.detailListingId !== listingId) {
      window.scrollTo({ top: 0, behavior: 'smooth' })
      void listings.openDetailListing(listingId)
    }
  },
  { immediate: true },
)

function goBack() {
  navigateTo(catalogPathFromRouteName(catalogRouteName.value))
}
</script>

<template>
  <div class="listing-detail-view catalog-page">
    <SeoPageHeading :title="pageTitle" />

    <main class="listing-detail-view__main">
      <div class="page-shell listing-detail-view__shell">
        <button type="button" class="listing-detail-view__back" @click="goBack">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          {{ t('listingDetail.back') }}
        </button>

        <Transition name="listing-detail-page" mode="out-in">
          <ListingDetailPanel
            v-if="detailListing"
            :key="detailListing.id"
            as-page
            :show-close="false"
            :listing="detailListing"
            :metro-station="detailMetroStation"
            :district-name="detailDistrictName"
            :loading="listings.detailLoading"
            @close="goBack"
          />
          <div v-else-if="listings.detailLoading" key="loading" class="listing-detail-view__state">
            {{ t('listing.loading') }}
          </div>
          <div v-else key="missing" class="listing-detail-view__state">
            {{ t('listing.error') }}
          </div>
        </Transition>
      </div>
    </main>
  </div>
</template>

<style scoped>
.listing-detail-view {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  background: var(--figma-surface);
}

.listing-detail-view__main {
  flex: 1;
  background: var(--figma-page-bg);
  padding-top: 16px;
  padding-bottom: 40px;
}

.listing-detail-view__shell {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 1180px;
  margin: 0 auto;
}

.listing-detail-view__back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  width: fit-content;
  min-height: 36px;
  border: none;
  background: transparent;
  padding: 4px 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  cursor: pointer;
  transition: color 0.2s ease;
}

.listing-detail-view__back:hover {
  color: var(--figma-accent);
}

.listing-detail-view__state {
  display: grid;
  place-items: center;
  min-height: 320px;
  padding: 24px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-catalog-card-radius, 20px);
  background: var(--figma-surface);
  font-size: 14px;
  color: var(--figma-text-muted);
}

.listing-detail-page-enter-active,
.listing-detail-page-leave-active {
  transition:
    opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-detail-page-enter-from,
.listing-detail-page-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

@media (prefers-reduced-motion: reduce) {
  .listing-detail-page-enter-active,
  .listing-detail-page-leave-active {
    transition: none;
  }
}
</style>
