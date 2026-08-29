<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import MapZoneTooltip from '@/components/MapZoneTooltip.vue'
import MapListingCard from '@/components/MapListingCard.vue'
import { resolveLocationPageHref } from '@/modules/location/lib/locationPageHref'
import { useMapPanel } from '@/modules/map/composables/useMapPanel'
import { useListingsStore } from '@/stores/listings'

const props = withDefaults(
  defineProps<{
    compact?: boolean
  }>(),
  {
    compact: false,
  },
)

const { t } = useI18n()
const route = useRoute()
const listings = useListingsStore()
const mapRoot = ref<HTMLElement | null>(null)
const mapPanelRef = ref<HTMLElement | null>(null)

const {
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
  getMetroStation,
  getDistrictLabel,
} = useMapPanel(mapRoot, mapPanelRef)

const locationPageHref = computed(() => {
  const city = listings.cities.find((item) => item.id === listings.cityId)
  const district = listings.districts.find((item) => item.id === listings.districtId)

  return resolveLocationPageHref({
    regionSlug: listings.regionSlug ?? viewState.value.regionSlug,
    citySlug: city?.slug ?? viewState.value.citySlug,
    districtSlug: district?.slug,
  })
})

const showLocationPageLink = computed(() => {
  const href = locationPageHref.value
  if (!href) {
    return false
  }

  const current = route.path.replace(/\/$/, '') || '/'
  return current !== href
})
</script>

<template>
  <aside
    ref="mapPanelRef"
    class="map-panel"
    :class="{ 'map-panel--compact': props.compact }"
  >
    <div class="map-panel__canvas-wrap">
      <div ref="mapRoot" class="map-panel__canvas" :class="{ 'map-panel__canvas--hidden': mapLoadError }" />
      <div v-if="mapLoadError" class="map-panel__error" role="alert">
        {{ t('map.unavailable') }}
      </div>
    </div>

    <Transition name="map-preloader">
      <div v-if="isMapLoading" class="map-panel__preloader" aria-live="polite" aria-busy="true">
        <div class="map-panel__spinner" />
        <span class="map-panel__preloader-text">{{ t('map.loading') }}</span>
      </div>
    </Transition>

    <div class="map-panel__nav">
      <button
        v-if="viewState.mode !== 'country'"
        type="button"
        class="map-panel__back"
        @click="goBack"
      >
        ← {{ t('map.back') }}
      </button>
      <span class="map-panel__breadcrumb">{{ breadcrumb }}</span>
    </div>

    <a
      v-if="showLocationPageLink && locationPageHref"
      :href="locationPageHref"
      class="map-panel__region-link"
    >
      {{ t('map.regionPage') }}
    </a>

    <MapZoneTooltip :data="tooltip" />

    <MapListingCard
      v-if="selectedListing && popupPosition"
      ref="popupCardRef"
      :listing="selectedListing"
      :metro-station="getMetroStation(selectedListing)"
      :district-name="getDistrictLabel(selectedListing)"
      :left="popupPosition.x"
      :top="popupPosition.y"
      :placement="popupPosition.placement"
      :card-width="popupPosition.cardWidth"
      :max-height="popupPosition.cardMaxHeight"
      :loading="listingCardLoading"
      @close="closeListingPopup"
      @view-details="openListingDetail(selectedListing!.id)"
    />
  </aside>
</template>

<style scoped>
.map-panel {
  position: relative;
  isolation: isolate;
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  min-height: var(--figma-map-min-height);
  height: var(--figma-map-min-height);
  background: var(--figma-placeholder);
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  overflow: hidden;
}

.map-panel--compact {
  min-height: 280px;
  height: 280px;
}

@media (min-width: 1280px) {
  .map-panel--compact {
    min-height: 360px;
    height: 360px;
  }
}

.map-panel__canvas-wrap {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  overflow: hidden;
  z-index: 1;
}

.map-panel__canvas {
  width: 100%;
  height: 100%;
}

.map-panel__canvas--hidden {
  visibility: hidden;
}

.map-panel__error {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 24px;
  text-align: center;
  font-size: 14px;
  color: var(--figma-text-muted);
  background: var(--figma-page-bg);
}

.map-panel__preloader {
  position: absolute;
  inset: 0;
  z-index: 650;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  background: rgba(246, 246, 248, 0.82);
  backdrop-filter: blur(2px);
  pointer-events: none;
}

.map-panel__spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(225, 69, 84, 0.18);
  border-top-color: var(--figma-accent);
  border-radius: 50%;
  animation: map-panel-spin 0.8s linear infinite;
}

.map-panel__preloader-text {
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-ink);
}

@keyframes map-panel-spin {
  to {
    transform: rotate(360deg);
  }
}

.map-preloader-enter-active,
.map-preloader-leave-active {
  transition: opacity 0.2s ease;
}

.map-preloader-enter-from,
.map-preloader-leave-to {
  opacity: 0;
}

.map-panel__nav {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 600;
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: calc(100% - 24px);
}

.map-panel__back {
  height: 32px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 16px;
  background: var(--figma-surface);
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-ink);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.map-panel__breadcrumb {
  height: 32px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 16px;
  background: var(--figma-surface);
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-ink);
  line-height: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.map-panel__region-link {
  position: absolute;
  bottom: 12px;
  left: 12px;
  z-index: 600;
  display: inline-flex;
  align-items: center;
  height: 36px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: 18px;
  background: var(--figma-surface);
  font-size: 13px;
  font-weight: 600;
  color: var(--figma-ink);
  text-decoration: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition:
    color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.map-panel__region-link:hover {
  color: var(--figma-accent);
  border-color: rgba(225, 69, 84, 0.35);
  transform: translateY(-1px);
}
</style>
