<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import CatalogGridCard from '@/components/CatalogGridCard.vue'
import { syncUserCollections } from '@/modules/collections/syncUserCollections'
import { listingDetailPath } from '@/lib/fullPageNav'
import { useComparisonsStore } from '@/stores/comparisons'
import { useFavoritesStore } from '@/stores/favorites'
import { useListingsStore } from '@/stores/listings'

const props = withDefaults(
  defineProps<{
    detailRouteName?: string
    columns?: 3 | 4
    embedded?: boolean
    compact?: boolean
  }>(),
  {
    columns: 4,
    embedded: false,
    compact: false,
  },
)

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const listingsStore = useListingsStore()
const favorites = useFavoritesStore()
const comparisons = useComparisonsStore()
const { items, loading, total, error } = storeToRefs(listingsStore)
const { favoriteListingIds } = storeToRefs(favorites)
const { comparedListingIds } = storeToRefs(comparisons)

onMounted(() => {
  void syncUserCollections()
})

const metroById = computed(() => {
  const map = new Map<number, (typeof listingsStore.metroStations)[number]>()
  for (const station of listingsStore.metroStations) {
    map.set(station.id, station)
  }
  return map
})

const districtById = computed(() => {
  const map = new Map<number, (typeof listingsStore.districts)[number]>()
  for (const district of listingsStore.districts) {
    map.set(district.id, district)
  }
  return map
})

function getMetroStation(listing: (typeof items.value)[number]) {
  if (!listing.metroStationId) {
    return undefined
  }
  return metroById.value.get(listing.metroStationId)
}

function getDistrictLabel(listing: (typeof items.value)[number]) {
  const district = listing.districtId === null
    ? undefined
    : districtById.value.get(listing.districtId)
  const city = listingsStore.cities.find((item) => item.id === listing.cityId)
  if (!district || !city) {
    return undefined
  }
  return `${district.name}, ${city.name}`
}

function openListing(id: number) {
  listingsStore.selectListing(id)
  void listingsStore.openDetailListing(id)

  const routeName = props.detailRouteName ?? 'listing-detail'
  const href = listingDetailPath(id, { detailRouteName: routeName })
  if (route.path !== href) {
    void router.push(href)
  }
}

function handleFavorite(listingId: number) {
  const listing = items.value.find((item) => item.id === listingId)
  void favorites.toggle(listingId, listing)
}

function handleCompare(listingId: number) {
  const listing = items.value.find((item) => item.id === listingId)
  void comparisons.toggle(listingId, listing)
}

</script>

<template>
  <section
    class="catalog-grid-section"
    :class="[
      props.columns === 3 ? 'catalog-grid-section--cols-3' : 'catalog-grid-section--cols-4',
      {
        'page-shell': !props.embedded,
        'catalog-grid-section--embedded': props.embedded,
        'catalog-grid-section--compact': props.compact,
      },
    ]"
  >
    <div v-if="loading && items.length === 0" class="catalog-grid-section__state">
      {{ t('listing.loading') }}
    </div>
    <div v-else-if="items.length === 0" class="catalog-grid-section__state">
      {{ error ? t('listing.error') : t('listing.noResults') }}
    </div>
    <div v-else class="catalog-grid">
      <CatalogGridCard
        v-for="listing in items"
        :key="listing.id"
        :listing="listing"
        :metro-station="getMetroStation(listing)"
        :district-name="getDistrictLabel(listing)"
        :favorited="favoriteListingIds.includes(listing.id)"
        :compared="comparedListingIds.includes(listing.id)"
        :compact="props.compact"
        @open="openListing"
        @favorite="handleFavorite"
        @compare="handleCompare"
      />
    </div>

    <div v-if="items.length > 0 && items.length < total" class="catalog-grid-section__more-wrap">
      <button
        type="button"
        class="catalog-grid-section__more"
        :disabled="loading"
        @click="listingsStore.loadMore"
      >
        {{ t('listing.showMore') }}
      </button>
    </div>
  </section>
</template>

<style scoped>
.catalog-grid-section {
  flex: 1;
  padding-top: 16px;
  padding-bottom: 32px;
}

.catalog-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  width: 100%;
  min-width: 0;
  align-items: stretch;
}

.catalog-grid > :deep(.catalog-card) {
  display: flex;
  flex-direction: column;
  align-self: stretch;
  width: 100%;
  min-width: 0;
  height: 100%;
  min-height: 100%;
}

.catalog-grid-section--embedded {
  width: 100%;
  min-width: 0;
  max-width: none;
  margin: 0;
  padding-left: 0;
  padding-right: 0;
}

.catalog-grid-section--compact .catalog-grid {
  gap: 16px;
}

.catalog-grid-section__state {
  display: grid;
  place-items: center;
  min-height: 280px;
  font-size: 14px;
  color: var(--figma-text-muted);
}

.catalog-grid-section__more-wrap {
  display: flex;
  justify-content: center;
  padding-top: 24px;
}

.catalog-grid-section__more {
  min-width: 220px;
  height: 43px;
  padding: 0 24px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: rgba(146, 146, 146, 0.1);
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    transform 0.2s ease;
}

.catalog-grid-section__more:hover:not(:disabled) {
  background: rgba(146, 146, 146, 0.16);
}

.catalog-grid-section__more:disabled {
  opacity: 0.6;
  cursor: default;
}

@media (min-width: 640px) {
  .catalog-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
  }
}

@media (min-width: 1024px) {
  .catalog-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1280px) {
  .catalog-grid-section--cols-4 .catalog-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 24px;
  }

  .catalog-grid-section--cols-3 .catalog-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--figma-catalog-grid-gap, 24px);
  }

  .catalog-grid-section--cols-3.catalog-grid-section--compact .catalog-grid {
    gap: 16px;
  }
}
</style>
