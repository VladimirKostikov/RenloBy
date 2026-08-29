<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import CatalogGridCard from '@/components/CatalogGridCard.vue'
import { listingPath, navigateTo } from '@/lib/fullPageNav'
import { syncUserCollections } from '@/modules/collections/syncUserCollections'
import { useComparisonsStore } from '@/stores/comparisons'
import { useFavoritesStore } from '@/stores/favorites'
import { useListingsStore } from '@/stores/listings'

defineProps<{
  embedded?: boolean
}>()

const { t } = useI18n()
const favorites = useFavoritesStore()
const comparisons = useComparisonsStore()
const listingsStore = useListingsStore()
const { listings, loading, pageLoaded } = storeToRefs(favorites)
const { comparedListingIds } = storeToRefs(comparisons)

onMounted(async () => {
  if (!listingsStore.cities.length) {
    await listingsStore.initialize()
  }
  await Promise.all([favorites.loadPage(), syncUserCollections()])
})

const metroById = computed(() => {
  const map = new Map<number, (typeof listingsStore.metroStations)[number]>()
  for (const station of listingsStore.metroStations) {
    map.set(station.id, station)
  }
  return map
})

function getMetroStation(listing: (typeof listings.value)[number]) {
  if (!listing.metroStationId) {
    return undefined
  }
  return metroById.value.get(listing.metroStationId)
}

function getDistrictLabel(listing: (typeof listings.value)[number]) {
  const district = listingsStore.districts.find((item) => item.id === listing.districtId)
  const city = listingsStore.cities.find((item) => item.id === listing.cityId)
  if (!district || !city) {
    return undefined
  }
  return `${district.name}, ${city.name}`
}

function openListing(id: number) {
  navigateTo(listingPath(id))
}

function handleFavorite(listingId: number) {
  const listing = listings.value.find((item) => item.id === listingId)
  void favorites.toggle(listingId, listing)
}

function handleCompare(listingId: number) {
  const listing = listings.value.find((item) => item.id === listingId)
  void comparisons.toggle(listingId, listing)
}
</script>

<template>
  <div class="favorites-panel" :class="{ 'favorites-panel--embedded': embedded }">
    <header class="favorites-panel__header">
      <div class="favorites-panel__heading">
        <h1 class="favorites-panel__title">{{ t('collections.favorites.title') }}</h1>
        <span v-if="listings.length > 0" class="favorites-panel__count">{{ listings.length }}</span>
      </div>
      <p class="favorites-panel__subtitle">{{ t('collections.favorites.subtitle') }}</p>
    </header>

    <div v-if="loading && !pageLoaded" class="favorites-panel__state">
      {{ t('listing.loading') }}
    </div>

    <div v-else-if="listings.length === 0" class="favorites-panel__state">
      <p>{{ t('collections.favorites.empty') }}</p>
      <a href="/" class="favorites-panel__cta">
        {{ t('collections.browseListings') }}
      </a>
    </div>

    <div v-else class="favorites-panel__grid">
      <CatalogGridCard
        v-for="listing in listings"
        :key="listing.id"
        :listing="listing"
        :metro-station="getMetroStation(listing)"
        :district-name="getDistrictLabel(listing)"
        :favorited="true"
        :compared="comparedListingIds.includes(listing.id)"
        @open="openListing"
        @favorite="handleFavorite"
        @compare="handleCompare"
      />
    </div>
  </div>
</template>

<style scoped>
.favorites-panel {
  padding: 24px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  border: 1px solid var(--figma-border);
}

.favorites-panel--embedded {
  padding: 0;
  border: none;
  border-radius: 0;
  background: transparent;
}

.favorites-panel__header {
  margin-bottom: 24px;
}

.favorites-panel--embedded .favorites-panel__header {
  margin-bottom: 16px;
}

.favorites-panel__heading {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.favorites-panel__title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  line-height: 1.25;
  color: var(--figma-ink);
}

.favorites-panel--embedded .favorites-panel__title {
  font-size: 22px;
}

.favorites-panel__count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 8px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
  color: var(--figma-accent);
  font-size: 13px;
  font-weight: 600;
  line-height: 1;
}

.favorites-panel__subtitle {
  margin: 0;
  font-size: 14px;
  line-height: 1.4;
  color: var(--figma-text-muted);
}

.favorites-panel__state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  min-height: 220px;
  padding: 24px 8px;
  text-align: center;
  color: var(--figma-text-muted);
  font-size: 14px;
}

.favorites-panel--embedded .favorites-panel__state {
  min-height: 160px;
}

.favorites-panel__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  align-items: stretch;
}

.favorites-panel__grid > :deep(.catalog-card) {
  display: flex;
  flex-direction: column;
  align-self: stretch;
  width: 100%;
  min-width: 0;
  height: 100%;
  min-height: 100%;
}

.favorites-panel__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 180px;
  height: 44px;
  padding: 0 18px;
  border: none;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.favorites-panel__cta:hover {
  background: var(--figma-accent-hover);
}

.favorites-panel__cta:active {
  transform: scale(0.98);
}

@media (max-width: 767px) {
  .favorites-panel:not(.favorites-panel--embedded) {
    padding: 16px;
  }

  .favorites-panel__title {
    font-size: 20px;
  }
}

@media (min-width: 768px) {
  .favorites-panel__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
  }
}

@media (min-width: 1280px) {
  .favorites-panel__title {
    font-size: 28px;
  }

  .favorites-panel__grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 22px;
  }

  .favorites-panel--embedded .favorites-panel__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
