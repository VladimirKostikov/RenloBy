<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import FilterBar from '@/components/FilterBar.vue'
import ListingDetailModal from '@/components/ListingDetailModal.vue'
import ListingList from '@/components/ListingList.vue'
import MapPanel from '@/components/MapPanel.vue'
import SearchSidebarFilters from '@/modules/search/components/SearchSidebarFilters.vue'
import { isExtendedFiltersOpen } from '@/modules/search/lib/buildSearchRoute'
import SeoPageHeading from '@/modules/seo/components/SeoPageHeading.vue'
import { getPageH1 } from '@/modules/seo'
import { listingSeoFromDistrictLabel } from '@/modules/seo/listingSeo'
import { peekSeoOverrides, seoOverridesVersion } from '@/modules/seo/seoOverrides'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import { isRoomsFilterActive } from '@/lib/listingRooms'
import { useListingsStore } from '@/stores/listings'

type MobilePanel = 'list' | 'map'

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const listings = useListingsStore()

const mobilePanel = ref<MobilePanel>('list')

onMounted(async () => {
  listings.setCatalogMode(false)

  if (listings.cities.length === 0) {
    await listings.initialize()
    return
  }

  if (itemsEmpty.value) {
    await listings.search()
    return
  }

  await listings.loadMapMarkers()
})

const itemsEmpty = computed(() => listings.items.length === 0 && !listings.loading)

const extendedFiltersOpen = computed(() => isExtendedFiltersOpen(route.query.panel))

const detailListing = computed(() => listings.detailListing)
const detailOpen = computed(() => listings.detailListingId !== null && detailListing.value !== null)

const activeFilterCount = computed(() => {
  let count = 0
  if (listings.listingType) count++
  if (listings.regionSlug) count++
  if (listings.cityId) count++
  if (listings.districtId) count++
  if (isRoomsFilterActive(listings.rooms)) count++
  if (listings.minArea) count++
  if (listings.maxPrice) count++
  if (listings.verifiedOnly) count++
  return count
})

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
    if (name !== 'search-listing-detail' && listings.detailListingId !== null) {
      listings.closeDetailListing()
    }
  },
)

watch(
  () => route.params.id,
  (id) => {
    if (route.name !== 'search-listing-detail' || typeof id !== 'string') {
      return
    }

    const listingId = Number(id)
    if (!Number.isFinite(listingId)) {
      void router.replace({ name: 'search-map' })
      return
    }

    if (listings.detailListingId !== listingId) {
      void listings.openDetailListing(listingId)
    }
  },
  { immediate: true },
)

watch(
  () => listings.verifiedOnly,
  () => {
    void listings.search()
  },
)

watch(
  () => listings.mapFocusListingId,
  (id) => {
    if (id !== null) {
      mobilePanel.value = 'map'
    }
  },
)

function closeDetailModal() {
  listings.closeDetailListing()
  if (route.name === 'search-listing-detail') {
    void router.replace({ name: 'search-map' })
  }
}

function openListingOnMap() {
  const listing = detailListing.value
  if (!listing) {
    return
  }

  mobilePanel.value = 'map'
  listings.focusListingOnMap(listing.id)
}

function toggleExtendedFilters() {
  if (extendedFiltersOpen.value) {
    void router.replace({ name: 'search-map' })
    return
  }

  void router.replace({ name: 'search-map', query: { panel: 'extended' } })
}

function closeMobileFilters() {
  if (!extendedFiltersOpen.value) {
    return
  }

  void router.replace({ name: 'search-map' })
}

function setMobilePanel(panel: MobilePanel) {
  mobilePanel.value = panel
}
</script>

<template>
  <div class="search-map">
    <SeoPageHeading :title="pageH1" />
    <FilterBar
      v-if="!extendedFiltersOpen"
      class="search-map__filter-bar"
      more-filters-mode="toggle"
      compact
      @toggle-more-filters="toggleExtendedFilters"
    />

    <main class="search-map__main">
      <div
        class="page-shell search-map__layout"
        :class="{ 'search-map__layout--with-filters': extendedFiltersOpen }"
      >
        <Transition name="search-map-filters">
          <div
            v-if="extendedFiltersOpen"
            class="search-map__filters"
          >
            <button
              type="button"
              class="search-map__filters-close"
              @click="closeMobileFilters"
            >
              {{ t('searchMap.closeFilters') }}
            </button>
            <SearchSidebarFilters />
          </div>
        </Transition>

        <div
          v-if="extendedFiltersOpen"
          class="search-map__backdrop"
          @click="closeMobileFilters"
        />

        <section
          class="search-map__content"
          :class="{
            'search-map__content--list': mobilePanel === 'list',
            'search-map__content--map': mobilePanel === 'map',
          }"
        >
          <div class="search-map__mobile-toolbar">
            <button
              type="button"
              class="search-map__filters-open"
              :aria-expanded="extendedFiltersOpen"
              @click="toggleExtendedFilters"
            >
              <img data-theme-ink src="/figma/filter.svg" alt="" width="16" height="16" />
              <span>{{ t('filters.title') }}</span>
              <span v-if="activeFilterCount > 0" class="search-map__filters-badge">{{ activeFilterCount }}</span>
            </button>

            <div class="search-map__mobile-switch" role="tablist" :aria-label="t('searchMap.panels')">
              <button
                type="button"
                role="tab"
                class="search-map__mobile-switch-btn"
                :class="{ 'search-map__mobile-switch-btn--active': mobilePanel === 'list' }"
                :aria-selected="mobilePanel === 'list'"
                @click="setMobilePanel('list')"
              >
                {{ t('searchMap.panelList') }}
              </button>
              <button
                type="button"
                role="tab"
                class="search-map__mobile-switch-btn"
                :class="{ 'search-map__mobile-switch-btn--active': mobilePanel === 'map' }"
                :aria-selected="mobilePanel === 'map'"
                @click="setMobilePanel('map')"
              >
                {{ t('searchMap.panelMap') }}
              </button>
            </div>
          </div>

          <div class="search-map__panels">
            <ListingList detail-route-name="search-listing-detail" />
            <MapPanel />
          </div>
        </section>
      </div>
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
.search-map {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  width: 100%;
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
  background: var(--figma-surface);
}

.search-map :deep(.search-map__filter-bar),
.search-map :deep(.filter-bar) {
  flex-shrink: 0;
}

.search-map__mobile-toolbar {
  display: none;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
}

.search-map__filters-open {
  display: none;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  min-height: var(--touch-target-min);
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.search-map__filters-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 50px;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
}

.search-map__main {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
  background: var(--figma-page-bg);
}

.search-map__layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  align-items: stretch;
  height: 100%;
  max-height: 100%;
  min-height: 0;
  padding-top: 12px;
  padding-bottom: 12px;
  overflow: hidden;
}

.search-map__layout--with-filters {
  grid-template-columns: minmax(260px, var(--figma-search-sidebar-width)) minmax(0, 1fr);
}

.search-map__filters {
  min-width: 0;
  min-height: 0;
  overflow: auto;
}

.search-map__filters-close {
  display: none;
}

.search-map__backdrop {
  display: none;
}

.search-map__content {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  min-height: 0;
  height: 100%;
  overflow: hidden;
}

.search-map__mobile-switch {
  display: none;
}

.search-map__panels {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  gap: 12px;
  min-width: 0;
  min-height: 0;
  height: 100%;
  overflow: hidden;
}

.search-map__panels :deep(.listing-panel),
.search-map__panels :deep(.map-panel) {
  min-height: 0;
  height: auto;
  max-height: none;
}

.search-map__panels :deep(.listing-panel) {
  flex: 1 1 42%;
  width: 100%;
}

.search-map__panels :deep(.listing-panel__list) {
  max-height: none;
}

.search-map__panels :deep(.map-panel) {
  flex: 1 1 58%;
  width: 100%;
}

@media (min-width: 1280px) {
  .search-map__layout {
    padding-top: 16px;
    padding-bottom: 16px;
    gap: 24px;
  }

  .search-map__filters {
    display: flex;
    flex-direction: column;
    min-height: 0;
    height: 100%;
  }

  .search-map__filters :deep(.search-sidebar) {
    flex: 1 1 auto;
    width: 100%;
    max-width: none;
    min-height: 0;
    height: 100%;
    overflow: auto;
  }

  .search-map__panels {
    flex-direction: row;
    gap: 16px;
  }

  .search-map__panels :deep(.listing-panel) {
    flex: 0 0 var(--figma-list-width);
    width: var(--figma-list-width);
    height: 100%;
  }

  .search-map__panels :deep(.map-panel) {
    flex: 1 1 auto;
    width: auto;
    height: 100%;
  }
}

@media (max-width: 1279px) {
  .search-map__layout,
  .search-map__layout.search-map__layout--with-filters {
    grid-template-columns: 1fr;
  }

  .search-map__filters {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 1500;
    width: min(100%, 360px);
    max-width: 100%;
    padding: 16px;
    padding-top: max(16px, env(safe-area-inset-top, 0px));
    padding-bottom: max(16px, env(safe-area-inset-bottom, 0px));
    overflow-y: auto;
    background: var(--figma-surface);
    box-shadow: 8px 0 32px rgba(0, 0, 0, 0.12);
  }

  .search-map__filters-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: var(--touch-target-min);
    margin-bottom: 12px;
    border: 1px solid var(--figma-border);
    border-radius: var(--figma-radius-chip);
    background: var(--figma-surface);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
  }

  .search-map__backdrop {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 1400;
    background: rgba(0, 0, 0, 0.35);
  }

  .search-map__mobile-toolbar {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .search-map__mobile-switch {
    display: grid;
    grid-template-columns: 1fr 1fr;
    flex-shrink: 0;
    gap: 4px;
    padding: 4px;
    border: 1px solid var(--figma-border);
    border-radius: 50px;
    background: var(--figma-surface);
  }

  .search-map__mobile-switch-btn {
    min-height: var(--touch-target-min);
    border: none;
    border-radius: 50px;
    background: transparent;
    font-size: 13px;
    font-weight: 700;
    color: var(--color-text-muted);
    cursor: pointer;
    transition:
      color 0.2s ease,
      background-color 0.2s ease;
  }

  .search-map__mobile-switch-btn--active {
    color: var(--figma-on-accent);
    background: var(--figma-accent);
  }

  .search-map__panels {
    position: relative;
    gap: 0;
  }

  .search-map__panels :deep(.listing-panel),
  .search-map__panels :deep(.map-panel) {
    flex: 1 1 auto;
    height: 100%;
    min-height: 0;
  }

  .search-map__panels :deep(.listing-panel__list) {
    max-height: none;
    flex: 1 1 auto;
  }

  .search-map__content--list .search-map__panels :deep(.listing-panel) {
    display: flex;
  }

  .search-map__content--list .search-map__panels :deep(.map-panel) {
    position: absolute;
    inset: 0;
    visibility: hidden;
    pointer-events: none;
    z-index: 0;
  }

  .search-map__content--map .search-map__panels :deep(.listing-panel) {
    visibility: hidden;
    pointer-events: none;
    position: absolute;
    inset: 0;
    z-index: 0;
  }

  .search-map__content--map .search-map__panels :deep(.map-panel) {
    display: block;
    position: relative;
    visibility: visible;
    pointer-events: auto;
    z-index: 1;
  }
}

@media (max-width: 767px) {
  .search-map :deep(.search-map__filter-bar),
  .search-map :deep(.filter-bar) {
    display: none;
  }

  .search-map__filters-open {
    display: inline-flex;
  }

  .search-map__layout {
    padding-top: 8px;
    padding-bottom: 8px;
    gap: 8px;
  }

  .search-map__content {
    gap: 8px;
  }
}

.search-map-filters-enter-active,
.search-map-filters-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.search-map-filters-enter-from,
.search-map-filters-leave-to {
  opacity: 0;
  transform: translateX(-12px);
}

@media (prefers-reduced-motion: reduce) {
  .search-map-filters-enter-active,
  .search-map-filters-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
