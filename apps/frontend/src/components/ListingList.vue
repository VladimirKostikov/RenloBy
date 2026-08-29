<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import ListingCard from '@/components/ListingCard.vue'
import FilterSelect from '@/components/FilterSelect.vue'
import { formatFoundCount } from '@/lib/formatPrice'
import { listingDetailPath, navigateTo } from '@/lib/fullPageNav'
import { buildSearchMapLocation } from '@/modules/search/lib/buildSearchRoute'
import type { ListingSortOption } from '@/lib/listingSearchParams'
import { useListingsStore } from '@/stores/listings'
import { useFavoritesStore } from '@/stores/favorites'
import { useAiAssistantStore } from '@/stores/aiAssistant'
import { useAiAssistantModal } from '@/modules/consultant/composables/useAiAssistantModal'

const props = withDefaults(
  defineProps<{
    detailRouteName?: string
    showMoreMode?: 'load' | 'navigate'
    previewLimit?: number
  }>(),
  {
    detailRouteName: 'listing-detail',
    showMoreMode: 'load',
    previewLimit: 0,
  },
)

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const listingsStore = useListingsStore()
const favorites = useFavoritesStore()
const aiAssistant = useAiAssistantStore()
const aiAssistantModal = useAiAssistantModal()
const { items, mapItems, total, error, selectedListingId, sort } = storeToRefs(listingsStore)
const sortOptions = computed(() => [
  { value: 'newest', label: t('listing.sortNewest') },
  { value: 'priceAsc', label: t('listing.sortPriceAsc') },
  { value: 'priceDesc', label: t('listing.sortPriceDesc') },
  { value: 'areaDesc', label: t('listing.sortAreaDesc') },
  { value: 'viewsDesc', label: t('listing.sortViewsDesc') },
])
const listRoot = ref<HTMLElement | null>(null)
const cardRefs = ref<Record<number, HTMLElement | null>>({})
const sorting = ref(false)
const lockedListMinHeight = ref<number | null>(null)

const visibleItems = computed(() => {
  const id = selectedListingId.value
  let source = items.value

  if (id !== null) {
    if (items.value.some((item) => item.id === id)) {
      source = items.value
    } else {
      const fromMap = mapItems.value.find((item) => item.id === id)
      source = fromMap ? [fromMap, ...items.value] : items.value
    }
  }

  if (props.previewLimit > 0) {
    return source.slice(0, props.previewLimit)
  }

  return source
})

const listStyle = computed(() => (
  lockedListMinHeight.value
    ? { minHeight: `${lockedListMinHeight.value}px` }
    : undefined
))

function handleShowMore() {
  if (props.showMoreMode === 'navigate') {
    navigateTo(buildSearchMapLocation({ panel: 'extended' }))
    return
  }

  void listingsStore.loadMore()
}

async function onSortChange(value: string | number | undefined) {
  const next = String(value ?? 'newest') as ListingSortOption
  if (next === sort.value) {
    return
  }

  lockedListMinHeight.value = listRoot.value?.offsetHeight ?? null
  sorting.value = true
  sort.value = next

  try {
    await listingsStore.search()
    await nextTick()
  } finally {
    window.setTimeout(() => {
      sorting.value = false
      lockedListMinHeight.value = null
    }, 320)
  }
}

function setCardRef(id: number, el: HTMLElement | null) {
  if (el) {
    cardRefs.value[id] = el
  } else {
    delete cardRefs.value[id]
  }
}

function openListing(id: number) {
  listingsStore.selectListing(id)
  void listingsStore.openDetailListing(id)

  const href = listingDetailPath(id, {
    detailRouteName: props.detailRouteName,
    citySlug: String(route.params.citySlug ?? ''),
    districtSlug: String(route.params.districtSlug ?? ''),
    regionSlug: String(route.params.regionSlug ?? ''),
  })

  if (route.path !== href) {
    void router.push(href)
  }
}

function handleFavorite(listingId: number) {
  const listing = visibleItems.value.find((item) => item.id === listingId)
  favorites.toggle(listingId, listing)
}

async function scrollToSelected(id: number | null) {
  if (id === null || !listRoot.value) {
    return
  }

  await nextTick()
  const card = cardRefs.value[id]
  if (!card) {
    return
  }

  card.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
}

watch(selectedListingId, (id) => {
  void scrollToSelected(id)
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
</script>

<template>
  <section class="listing-panel">
    <div class="listing-panel__toolbar">
      <p class="listing-panel__count">{{ t('listing.found', { n: formatFoundCount(total) }) }}</p>
      <FilterSelect
        overlay-id="listing-sort"
        :model-value="sort"
        :options="sortOptions"
        select-class="listing-panel__sort-select"
        @update:model-value="onSortChange"
      />
    </div>

    <div class="listing-panel__divider" />

    <div v-if="items.length === 0" class="listing-panel__state">
      {{ error ? t('listing.error') : t('listing.noResults') }}
    </div>
    <div
      v-else
      ref="listRoot"
      class="listing-panel__list"
      :class="{ 'listing-panel__list--sorting': sorting }"
      :style="listStyle"
      :aria-busy="sorting"
    >
      <TransitionGroup name="listing-sort" tag="div" class="listing-panel__list-track">
      <div
        v-for="listing in visibleItems"
        :key="listing.id"
        :ref="(el) => setCardRef(listing.id, el as HTMLElement | null)"
        class="listing-panel__item"
        :class="{ 'listing-panel__item--active': selectedListingId === listing.id }"
        @click="openListing(listing.id)"
      >
        <ListingCard
          :listing="listing"
          :metro-station="getMetroStation(listing)"
          :district-name="getDistrictLabel(listing)"
          :active="selectedListingId === listing.id"
          :favorited="favorites.isFavorite(listing.id)"
          :ai-recommended="aiAssistant.isRecommended(listing.id)"
          @favorite="handleFavorite"
        />
      </div>
      </TransitionGroup>
    </div>

    <div class="listing-panel__footer">
      <button type="button" class="listing-panel__more" @click="handleShowMore">{{ t('listing.showMore') }}</button>
      <button type="button" class="listing-panel__ai" @click="aiAssistantModal.open()">
        <img src="/figma/ai-assistant.svg" alt="" width="18" height="18" />
        {{ t('listing.aiAssistant') }}
      </button>
    </div>
  </section>
</template>

<style scoped>
.listing-panel {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  width: 100%;
  height: auto;
  min-height: 0;
  max-height: 100%;
  background: var(--figma-surface);
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  overflow: hidden;
}

.listing-panel__toolbar {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 16px 10px;
}

.listing-panel__count {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.listing-panel__sort-select :deep(.filter-select__trigger) {
  flex-direction: row;
  align-items: center;
  gap: 8px;
  width: auto;
  min-width: 0;
  height: auto;
  padding: 0;
  border: none;
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.listing-panel__sort-select :deep(.filter-chip__value) {
  font-size: 14px;
}

.listing-panel__sort-select :deep(.filter-chip__chevron) {
  position: static;
  transform: rotate(90deg);
}

.listing-panel__divider {
  flex-shrink: 0;
  height: 1px;
  margin: 0 16px;
  background: var(--figma-border);
}

.listing-panel__list {
  position: relative;
  flex: 1 1 auto;
  min-height: 0;
  max-height: min(52vh, 480px);
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0 16px;
  transition: opacity 0.22s ease;
}

.listing-panel__list-track {
  position: relative;
}

.listing-panel__list--sorting {
  opacity: 0.55;
  pointer-events: none;
}

.listing-panel__item {
  position: relative;
  cursor: pointer;
  border-radius: var(--figma-radius-chip);
  transition: background-color 0.2s ease;
}

.listing-panel__item--active {
  background: rgba(225, 69, 84, 0.06);
}

.listing-panel__state {
  flex: 1 1 auto;
  display: grid;
  place-items: center;
  min-height: 200px;
  color: var(--figma-text-muted);
  font-size: 14px;
}

.listing-panel__footer {
  flex-shrink: 0;
  display: flex;
  flex-direction: row;
  gap: 12px;
  padding: 16px;
  border-top: 1px solid var(--figma-border);
}

.listing-panel__more {
  flex: 1 1 0;
  min-width: 0;
  height: 43px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: rgba(146, 146, 146, 0.1);
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.listing-panel__ai {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex: 1 1 0;
  min-width: 0;
  height: 43px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
}

@media (min-width: 1280px) {
  .listing-panel {
    width: var(--figma-list-width);
    height: var(--figma-map-min-height);
  }

  .listing-panel__list {
    max-height: none;
  }

  .listing-panel__ai {
    font-size: 16px;
  }
}

@media (max-width: 767px) {
  .listing-panel {
    border: none;
    border-radius: 0;
    background: transparent;
  }

  .listing-panel__toolbar {
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px 4px 12px;
  }

  .listing-panel__footer {
    flex-direction: column;
    gap: 8px;
    padding: 8px 4px 12px;
  }

  .listing-panel__more,
  .listing-panel__ai {
    width: 100%;
    min-height: var(--touch-target-min);
  }

  .listing-panel__list {
    max-height: min(52vh, 480px);
    padding: 0 4px;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
  }

  .listing-panel__divider {
    display: none;
  }

  .listing-panel__state {
    padding-left: 4px;
    padding-right: 4px;
  }

  .listing-panel__item {
    border-radius: 16px;
  }

  .listing-panel__item--active {
    background: transparent;
  }

  .listing-panel__item--active :deep(.listing-card) {
    border-color: color-mix(in srgb, var(--figma-accent) 45%, var(--figma-border));
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--figma-accent) 25%, transparent);
  }
}
</style>

<style>
.listing-sort-move,
.listing-sort-enter-active,
.listing-sort-leave-active {
  transition:
    opacity 0.28s ease,
    transform 0.36s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-sort-enter-from,
.listing-sort-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.listing-sort-leave-active {
  position: absolute;
  left: 16px;
  right: 16px;
  z-index: 0;
  pointer-events: none;
}

@media (prefers-reduced-motion: reduce) {
  .listing-sort-move,
  .listing-sort-enter-active,
  .listing-sort-leave-active {
    transition-duration: 0.01ms;
  }

  .listing-sort-enter-from,
  .listing-sort-leave-to {
    transform: none;
  }
}
</style>
