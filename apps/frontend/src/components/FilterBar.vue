<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { createSavedSearch } from '@/api/savedSearches'
import { resolveApiError } from '@/lib/resolveApiError'
import FilterNumberChip from '@/components/FilterNumberChip.vue'
import FilterSelect from '@/components/FilterSelect.vue'
import { provideFilterOverlayGroup } from '@/lib/filterOverlayGroup'
import { FILTER_REGION_SLUGS, filterCitiesByRegionSlug } from '@/lib/filterRegions'
import { convertFromUsd, convertToUsd, formatListingPrice } from '@/lib/formatPrice'
import {
  buildRoomFilterOptions,
  formatListingRoomsShort,
  isRoomsFilterActive,
} from '@/lib/listingRooms'
import { useRequireAuth } from '@/modules/auth/composables/useRequireAuth'
import { useCurrencyStore } from '@/stores/currency'
import { buildSearchMapLocation } from '@/modules/search/lib/buildSearchRoute'
import { navigateTo } from '@/lib/fullPageNav'
import { useListingsStore } from '@/stores/listings'
import type { ListingType } from '@/types'

const props = withDefaults(
  defineProps<{
    hideCity?: boolean
    hideDistrict?: boolean
    hideRegion?: boolean
    moreFiltersMode?: 'navigate' | 'toggle' | 'none'
    compact?: boolean
  }>(),
  {
    hideCity: false,
    hideDistrict: false,
    hideRegion: false,
    moreFiltersMode: 'navigate',
    compact: false,
  },
)

const emit = defineEmits<{
  toggleMoreFilters: []
}>()

const { t } = useI18n()
const { requireAuth } = useRequireAuth()
const listings = useListingsStore()
const { code: currency } = storeToRefs(useCurrencyStore())

provideFilterOverlayGroup()

const saveDialogOpen = ref(false)
const saveSearchName = ref('')
const saveLoading = ref(false)
const saveError = ref<string | null>(null)
const saveSuccess = ref(false)

const activeFilterCount = computed(() => {
  let count = 0
  if (listings.listingType) count++
  if (listings.regionSlug) count++
  if (listings.cityId) count++
  if (listings.districtId) count++
  if (isRoomsFilterActive(listings.rooms)) count++
  if (listings.minArea) count++
  if (listings.maxPrice) count++
  return count
})

const maxPriceLabel = computed(() => {
  if (!listings.maxPrice) {
    return t('filters.priceUpToDefault')
  }
  return t('filters.priceUpTo', { price: formatListingPrice(listings.maxPrice, currency.value) })
})

const maxPriceDraft = computed({
  get() {
    if (!listings.maxPrice) {
      return undefined
    }
    return convertFromUsd(listings.maxPrice, currency.value)
  },
  set(value: number | undefined) {
    listings.maxPrice = value !== undefined ? convertToUsd(value, currency.value) : undefined
  },
})

const regionOptions = computed(() => [
  { value: '', label: t('filters.allRegions') },
  ...FILTER_REGION_SLUGS.map((slug) => ({
    value: slug,
    label: t(`map.regions.${slug}`),
  })),
])

const cityOptions = computed(() => [
  { value: '', label: t('filters.allCities') },
  ...filterCitiesByRegionSlug(listings.cities, listings.regionSlug).map((city) => ({
    value: city.id,
    label: city.name,
  })),
])

const districtOptions = computed(() => [
  { value: '', label: t('filters.allDistricts') },
  ...listings.districts.map((district) => ({
    value: district.id,
    label: district.name,
  })),
])

const roomSelectOptions = computed(() => buildRoomFilterOptions(t('filters.anyRooms'), t))

const objectTypeOptions = computed(() => [
  { value: '', label: t('filters.anyObjectType') },
  { value: 'apartment', label: t('listingType.apartments') },
  { value: 'house', label: t('listingType.house') },
  { value: 'room', label: t('listingType.room') },
])

const priceInputPlaceholder = computed(() =>
  currency.value === 'byn' ? t('filters.pricePlaceholderByn') : t('filters.pricePlaceholderUsd'),
)

async function onRegionSelect(value: string | number | undefined) {
  const next = typeof value === 'string' && value !== '' ? value : undefined
  listings.regionSlug = next
  listings.cityId = undefined
  listings.districtId = undefined
  await listings.loadDistricts(undefined)
  await listings.search()
}

async function onCitySelect(value: string | number | undefined) {
  const nextCityId = value !== undefined && value !== '' ? Number(value) : undefined
  listings.cityId = nextCityId
  listings.districtId = undefined

  if (nextCityId !== undefined) {
    const city = listings.cities.find((item) => item.id === nextCityId)
    if (city?.regionSlug) {
      listings.regionSlug = city.regionSlug
    }
  }

  await listings.loadDistricts(listings.cityId)
  await listings.search()
}

async function onDistrictSelect(value: string | number | undefined) {
  listings.districtId = value !== undefined && value !== '' ? Number(value) : undefined
  await listings.search()
}

async function onRoomsSelect(value: string | number | undefined) {
  listings.rooms = value !== undefined && value !== '' ? Number(value) : undefined
  await listings.search()
}

async function onObjectTypeSelect(value: string | number | undefined) {
  listings.listingType = typeof value === 'string' && value !== '' ? (value as ListingType) : undefined
  await listings.search()
}

async function onMaxPriceChange(value: number | undefined) {
  maxPriceDraft.value = value
  await listings.search()
}

async function onMinAreaChange(value: number | undefined) {
  listings.minArea = value
  await listings.search()
}

async function resetFilters() {
  listings.resetFilters()
  await listings.loadDistricts(listings.cityId)
  await listings.search()
}

function buildDefaultSearchName(): string {
  const parts: string[] = []
  const region = listings.regionSlug ? t(`map.regions.${listings.regionSlug}`) : undefined
  const city = listings.cities.find((item) => item.id === listings.cityId)?.name
  const district = listings.districts.find((item) => item.id === listings.districtId)?.name

  if (region) {
    parts.push(region)
  }
  if (city) {
    parts.push(city)
  }
  if (district) {
    parts.push(district)
  }
  if (isRoomsFilterActive(listings.rooms)) {
    parts.push(formatListingRoomsShort(listings.rooms, t))
  }
  if (listings.maxPrice) {
    parts.push(t('filters.priceUpTo', { price: formatListingPrice(listings.maxPrice, currency.value) }))
  }
  if (listings.minArea) {
    parts.push(t('filters.areaFromValue', { n: listings.minArea }))
  }

  return parts.join(', ') || t('filters.saveSearchDefaultName')
}

function openSaveDialog() {
  requireAuth(() => {
    saveError.value = null
    saveSuccess.value = false
    saveSearchName.value = buildDefaultSearchName()
    saveDialogOpen.value = true
  })
}

function closeSaveDialog() {
  if (saveLoading.value) {
    return
  }
  saveDialogOpen.value = false
  saveError.value = null
}

function openMoreFilters() {
  if (props.moreFiltersMode === 'none') {
    return
  }

  if (props.moreFiltersMode === 'toggle') {
    emit('toggleMoreFilters')
    return
  }

  navigateTo(buildSearchMapLocation({ panel: 'extended' }))
}

async function confirmSaveSearch() {
  const name = saveSearchName.value.trim()
  if (!name) {
    saveError.value = t('filters.saveSearchName')
    return
  }

  saveLoading.value = true
  saveError.value = null

  try {
    await listings.search()
    await createSavedSearch({
      name,
      filters: listings.getFilterSnapshot(),
    })
    saveSuccess.value = true
    saveDialogOpen.value = false
    window.setTimeout(() => {
      saveSuccess.value = false
    }, 2500)
  } catch (err) {
    saveError.value = resolveApiError(err, t, 'filters.saveSearchError').message
  } finally {
    saveLoading.value = false
  }
}
</script>

<template>
  <div class="filter-bar" :class="{ 'filter-bar--compact': props.compact }">
    <div class="page-shell filter-bar__inner">
      <div class="filter-bar__chips">
      <button
        type="button"
        class="filter-chip filter-chip--filters"
        @click="openMoreFilters"
      >
        <img data-theme-ink src="/figma/filter.svg" alt="" class="filter-chip__icon" width="18" height="18" />
        <span class="filter-chip__title">{{ t('filters.title') }}</span>
        <span v-if="activeFilterCount > 0" class="filter-chip__badge">{{ activeFilterCount }}</span>
      </button>

      <FilterSelect
        overlay-id="object-type"
        :label="t('filters.objectType')"
        modifier="object-type"
        :model-value="listings.listingType ?? ''"
        :options="objectTypeOptions"
        @change="onObjectTypeSelect"
      />

      <FilterSelect
        v-if="!props.hideRegion"
        overlay-id="region"
        :label="t('filters.region')"
        modifier="region"
        :model-value="listings.regionSlug ?? ''"
        :options="regionOptions"
        @change="onRegionSelect"
      />

      <FilterSelect
        v-if="!props.hideCity"
        overlay-id="city"
        :label="t('filters.city')"
        modifier="city"
        :model-value="listings.cityId ?? ''"
        :options="cityOptions"
        @change="onCitySelect"
      />

      <FilterSelect
        v-if="!props.hideDistrict"
        overlay-id="district"
        :label="t('filters.district')"
        modifier="district"
        :model-value="listings.districtId ?? ''"
        :options="districtOptions"
        @change="onDistrictSelect"
      />

      <FilterNumberChip
        overlay-id="price"
        :label="t('filters.price')"
        modifier="price"
        :model-value="maxPriceDraft"
        :display-value="maxPriceLabel"
        :input-placeholder="priceInputPlaceholder"
        :show-chevron="false"
        :min="1"
        @change="onMaxPriceChange"
      />

      <FilterSelect
        overlay-id="rooms"
        :label="t('filters.rooms')"
        modifier="rooms"
        :model-value="listings.rooms ?? ''"
        :options="roomSelectOptions"
        @change="onRoomsSelect"
      />

      <FilterNumberChip
        overlay-id="area"
        :label="t('filters.area')"
        modifier="area"
        :model-value="listings.minArea"
        :display-value="listings.minArea ? t('filters.areaFromValue', { n: listings.minArea }) : t('filters.areaFromDefault')"
        :input-placeholder="t('filters.areaPlaceholder')"
        :min="1"
        :step="0.1"
        @change="onMinAreaChange"
      />

      <button
        v-if="moreFiltersMode !== 'none'"
        type="button"
        class="filter-chip filter-chip--more"
        @click="openMoreFilters"
      >
        {{ t('filters.more') }}
      </button>
      </div>

      <div class="filter-bar__tail">
        <button type="button" class="filter-bar__reset" @click="resetFilters">{{ t('filters.reset') }}</button>
        <button type="button" class="filter-bar__save" @click="openSaveDialog">
          <span class="filter-bar__save-full">{{ saveSuccess ? t('filters.saveSearchSuccess') : t('filters.saveSearch') }}</span>
          <span class="filter-bar__save-short">{{ saveSuccess ? t('filters.saveSearchSuccess') : t('filters.saveSearchConfirm') }}</span>
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="saveDialogOpen" class="save-search-overlay" @click.self="closeSaveDialog">
        <form class="save-search-dialog" @submit.prevent="confirmSaveSearch">
          <h2 class="save-search-dialog__title">{{ t('filters.saveSearchTitle') }}</h2>
          <label class="save-search-dialog__field">
            <span>{{ t('filters.saveSearchName') }}</span>
            <input v-model="saveSearchName" type="text" maxlength="150" required />
          </label>
          <p v-if="saveError" class="save-search-dialog__error">{{ saveError }}</p>
          <div class="save-search-dialog__actions">
            <button type="button" class="save-search-dialog__cancel" @click="closeSaveDialog">
              {{ t('filters.saveSearchCancel') }}
            </button>
            <button type="submit" class="save-search-dialog__confirm" :disabled="saveLoading">
              {{ t('filters.saveSearchConfirm') }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.filter-bar {
  background: var(--figma-surface);
  padding-top: 8px;
  padding-bottom: 14px;
  overflow-x: hidden;
}

.filter-bar__inner {
  display: flex;
  align-items: center;
  gap: var(--figma-filter-chip-gap);
}

.filter-bar__chips {
  display: flex;
  align-items: center;
  flex: 1 1 auto;
  flex-wrap: nowrap;
  gap: var(--figma-filter-chip-gap);
  min-width: 0;
  overflow-x: auto;
  padding-bottom: 2px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.filter-bar__chips::-webkit-scrollbar {
  display: none;
}

.filter-chip--filters {
  gap: 7px;
  width: auto;
  min-width: 143px;
  padding: 0 14px;
  font-size: var(--figma-filter-value-size);
  font-weight: 600;
}

.filter-chip__icon {
  flex-shrink: 0;
}

.filter-chip__title {
  line-height: 1;
}

.filter-chip__badge {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 17px;
  height: 17px;
  border-radius: 50%;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 11px;
  font-weight: 600;
  margin-left: auto;
}

.filter-bar__tail {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  margin-left: auto;
  gap: 16px;
  height: var(--figma-filter-chip-height);
}

.filter-bar__reset {
  display: inline-flex;
  align-items: center;
  height: var(--figma-filter-chip-height);
  border: none;
  background: transparent;
  padding: 0 2px;
  font-size: var(--figma-filter-value-size);
  font-weight: 400;
  line-height: 1;
  color: var(--figma-ink);
  cursor: pointer;
  white-space: nowrap;
  transition: color 0.2s ease;
}

.filter-bar__reset:hover {
  color: var(--figma-accent);
}

.filter-bar__save {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 150px;
  height: var(--figma-filter-chip-height);
  padding: 0 16px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: var(--figma-filter-value-size);
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition:
    background-color 0.2s ease,
    transform 0.2s ease;
}

.filter-bar__save-short {
  display: none;
}

.filter-bar__save:hover {
  background: var(--figma-accent-hover);
}

.filter-bar__save:active {
  transform: scale(0.985);
}

.save-search-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: grid;
  place-items: center;
  padding: 24px;
  background: rgba(0, 0, 0, 0.35);
}

.save-search-dialog {
  width: 100%;
  max-width: 420px;
  padding: 24px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.save-search-dialog__title {
  margin: 0 0 16px;
  font-size: 18px;
  font-weight: 600;
}

.save-search-dialog__field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 12px;
  color: var(--color-text-muted);
}

.save-search-dialog__field input {
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  font-size: 14px;
  color: var(--figma-ink);
}

.save-search-dialog__error {
  margin: 12px 0 0;
  font-size: 13px;
  color: var(--figma-accent);
}

.save-search-dialog__actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

.save-search-dialog__cancel,
.save-search-dialog__confirm {
  height: 36px;
  padding: 0 16px;
  border-radius: var(--figma-radius-chip);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.save-search-dialog__cancel {
  border: 1px solid var(--figma-border);
  background: var(--figma-surface);
  color: var(--figma-ink);
}

.save-search-dialog__confirm {
  border: 1px solid var(--figma-border);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.save-search-dialog__confirm:disabled {
  opacity: 0.6;
  cursor: default;
}

@media (max-width: 1279px) {
  .filter-bar__inner {
    flex-wrap: wrap;
  }

  .filter-bar__chips {
    flex: 1 1 100%;
  }

  .filter-bar__tail {
    flex: 1 1 100%;
    margin-left: 0;
    justify-content: flex-end;
  }
}

@media (max-width: 767px) {
  .filter-bar {
    padding-top: 4px;
    padding-bottom: 6px;
  }

  .filter-bar__inner {
    flex-wrap: nowrap;
    align-items: center;
    gap: 8px;
  }

  .filter-bar__chips {
    flex: 1 1 auto;
    min-width: 0;
  }

  .filter-bar__tail {
    flex: 0 0 auto;
    flex-wrap: nowrap;
    gap: 6px;
    margin-left: 0;
    justify-content: flex-end;
  }

  .filter-bar__reset {
    min-width: 0;
    min-height: 36px;
    padding: 0 8px;
    font-size: 12px;
  }

  .filter-bar__save {
    min-width: 0;
    min-height: 36px;
    padding: 0 10px;
    font-size: 12px;
  }

  .filter-bar__save-full {
    display: none;
  }

  .filter-bar__save-short {
    display: inline;
  }

  .filter-bar--compact .filter-bar__save {
    min-width: 0;
  }

  .filter-bar--compact .filter-chip--filters {
    min-width: 0;
    min-height: 36px;
    padding: 0 10px;
  }

  .filter-bar--compact .filter-chip--more,
  .filter-bar--compact :deep(.filter-chip--object-type),
  .filter-bar--compact :deep(.filter-chip--region),
  .filter-bar--compact :deep(.filter-chip--city),
  .filter-bar--compact :deep(.filter-chip--rooms),
  .filter-bar--compact :deep(.filter-chip--district),
  .filter-bar--compact :deep(.filter-chip--price),
  .filter-bar--compact :deep(.filter-chip--area) {
    display: none;
  }

  .filter-chip--filters {
    flex-shrink: 0;
  }
}

.filter-bar--compact {
  --figma-filter-chip-height: 38px;
  --figma-filter-chip-gap: 12px;
  --figma-filter-value-size: 13px;
  --figma-filter-label-size: 9px;
  padding-top: 6px;
  padding-bottom: 10px;
}

.filter-bar--compact .filter-chip--filters {
  width: auto;
  min-width: 128px;
  padding: 0 12px;
}

.filter-bar--compact .filter-chip--more {
  width: 128px;
  padding: 0 12px;
}

.filter-bar--compact :deep(.filter-chip--object-type) {
  width: 108px;
}

.filter-bar--compact :deep(.filter-chip--region) {
  width: 140px;
}

.filter-bar--compact :deep(.filter-chip--city),
.filter-bar--compact :deep(.filter-chip--rooms) {
  width: 88px;
}

.filter-bar--compact :deep(.filter-chip--district),
.filter-bar--compact :deep(.filter-chip--price) {
  width: 118px;
}

.filter-bar--compact :deep(.filter-chip--area) {
  width: 100px;
}

.filter-bar--compact .filter-bar__tail {
  gap: 12px;
}

.filter-bar--compact .filter-bar__save {
  min-width: 136px;
  padding: 0 14px;
}
</style>
