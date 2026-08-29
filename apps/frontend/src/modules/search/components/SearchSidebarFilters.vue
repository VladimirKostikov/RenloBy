<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import FilterSelect from '@/components/FilterSelect.vue'
import RangeSlider from '@/components/RangeSlider.vue'
import ToggleSwitch from '@/components/ToggleSwitch.vue'
import { provideFilterOverlayGroup } from '@/lib/filterOverlayGroup'
import CurrencyText from '@/components/CurrencyText.vue'
import { convertFromUsd, convertToUsd, formatFoundCount, formatListingPrice } from '@/lib/formatPrice'
import type { PublicCatalogKind } from '@/lib/fullPageNav'
import { buildRoomFilterOptions } from '@/lib/listingRooms'
import { useCurrencyStore } from '@/stores/currency'
import { useListingsStore } from '@/stores/listings'

const { t } = useI18n()
const listings = useListingsStore()
const { code: currency } = storeToRefs(useCurrencyStore())

provideFilterOverlayGroup()

const dealTypeOptions = computed(() => [
  { key: 'sale' as PublicCatalogKind, label: t('nav.sale') },
  { key: 'rent' as PublicCatalogKind, label: t('nav.rent') },
  { key: 'commercial' as PublicCatalogKind, label: t('nav.commercial') },
])

const selectedDealTypeOption = computed<PublicCatalogKind>(() =>
  listings.listingType === 'commercial' ? 'commercial' : listings.dealType,
)

const isSaleLike = computed(
  () => selectedDealTypeOption.value === 'sale' || selectedDealTypeOption.value === 'commercial',
)

const rentPriceBounds = { min: 200, max: 5000 }
const salePriceBoundsUsd = { min: 20_000, max: 500_000 }

const priceBounds = computed(() => {
  if (isSaleLike.value) {
    return {
      min: convertFromUsd(salePriceBoundsUsd.min, currency.value),
      max: convertFromUsd(salePriceBoundsUsd.max, currency.value),
    }
  }
  return rentPriceBounds
})

const priceStep = computed(() => (isSaleLike.value ? 5000 : 50))

const areaBounds = { min: 20, max: 200 }

const priceMinDraft = ref(priceBounds.value.min)
const priceMaxDraft = ref(priceBounds.value.max)
const areaMinDraft = ref(areaBounds.min)
const areaMaxDraft = ref(areaBounds.max)

const floorOptions = computed(() => [
  { value: '', label: t('searchMap.floorAny') },
  ...Array.from({ length: 25 }, (_, index) => ({
    value: index + 1,
    label: String(index + 1),
  })),
])

const roomOptions = computed(() => buildRoomFilterOptions(t('searchMap.roomsAny'), t))

const showCountLabel = computed(() =>
  t('searchMap.showCount', { n: formatFoundCount(listings.total) }),
)

const priceLabel = computed(() =>
  listings.dealType === 'rent' ? t('searchMap.priceMonthly') : t('filters.price'),
)

const priceFromLabel = computed(() =>
  formatListingPrice(convertToUsd(priceMinDraft.value, currency.value), currency.value),
)

const priceToLabel = computed(() =>
  formatListingPrice(convertToUsd(priceMaxDraft.value, currency.value), currency.value),
)

function syncDraftFromStore() {
  priceMinDraft.value = listings.minPrice
    ? convertFromUsd(listings.minPrice, currency.value)
    : priceBounds.value.min
  priceMaxDraft.value = listings.maxPrice
    ? convertFromUsd(listings.maxPrice, currency.value)
    : priceBounds.value.max
  areaMinDraft.value = listings.minArea ?? areaBounds.min
  areaMaxDraft.value = listings.maxArea ?? areaBounds.max
}

watch([currency, () => listings.dealType], syncDraftFromStore, { immediate: true })

async function selectDealType(option: PublicCatalogKind) {
  if (selectedDealTypeOption.value === option) {
    return
  }

  if (option === 'commercial') {
    listings.setDealType('sale')
    listings.listingType = 'commercial'
  } else {
    listings.setDealType(option)
    listings.listingType = undefined
  }
  listings.minPrice = undefined
  listings.maxPrice = undefined
  if (option !== 'rent') {
    listings.rentTerm = 'long'
  }
  syncDraftFromStore()
  await listings.search()
}

async function applyPriceRange() {
  listings.minPrice = convertToUsd(priceMinDraft.value, currency.value)
  listings.maxPrice = convertToUsd(priceMaxDraft.value, currency.value)
  await listings.search()
}

async function applyAreaRange() {
  listings.minArea = areaMinDraft.value
  listings.maxArea = areaMaxDraft.value
  await listings.search()
}

async function onFloorSelect(value: string | number | undefined) {
  listings.floor = value !== undefined && value !== '' ? Number(value) : undefined
  await listings.search()
}

async function onRoomsSelect(value: string | number | undefined) {
  listings.rooms = value !== undefined && value !== '' ? Number(value) : undefined
  await listings.search()
}

async function resetSidebar() {
  listings.resetFilters()
  syncDraftFromStore()
  await listings.loadDistricts(listings.cityId)
  await listings.search()
}

async function applyFilters() {
  await applyPriceRange()
  await applyAreaRange()
}
</script>

<template>
  <aside class="search-sidebar">
    <div class="search-sidebar__header">
      <h2 class="search-sidebar__title">{{ t('filters.title') }}</h2>
      <button type="button" class="search-sidebar__reset" @click="resetSidebar">
        {{ t('filters.reset') }}
      </button>
    </div>

    <div class="search-sidebar__divider" />

    <section class="search-sidebar__section">
      <h3 class="search-sidebar__label">{{ t('searchMap.dealType') }}</h3>
      <div class="search-sidebar__deal-types" role="tablist" :aria-label="t('searchMap.dealType')">
        <button
          v-for="option in dealTypeOptions"
          :key="option.key"
          type="button"
          role="tab"
          class="search-sidebar__deal-type"
          :class="{ 'search-sidebar__deal-type--active': selectedDealTypeOption === option.key }"
          :aria-selected="selectedDealTypeOption === option.key"
          @click="selectDealType(option.key)"
        >
          {{ option.label }}
        </button>
      </div>
    </section>

    <div class="search-sidebar__divider" />

    <section class="search-sidebar__section">
      <h3 class="search-sidebar__label">{{ priceLabel }}</h3>
      <RangeSlider
        v-model:min-value="priceMinDraft"
        v-model:max-value="priceMaxDraft"
        :min="priceBounds.min"
        :max="priceBounds.max"
        :step="priceStep"
        @mouseup="applyPriceRange"
        @touchend="applyPriceRange"
      />
      <div class="search-sidebar__inputs">
        <div class="search-sidebar__input-box">
          <span class="search-sidebar__input-caption">{{ t('searchMap.from') }}</span>
          <CurrencyText class="search-sidebar__input-value" :text="priceFromLabel" />
        </div>
        <div class="search-sidebar__input-box">
          <span class="search-sidebar__input-caption">{{ t('searchMap.to') }}</span>
          <CurrencyText class="search-sidebar__input-value" :text="priceToLabel" />
        </div>
      </div>
    </section>

    <div class="search-sidebar__divider" />

    <section class="search-sidebar__section">
      <h3 class="search-sidebar__label">{{ t('searchMap.floor') }}</h3>
      <FilterSelect
        overlay-id="search-floor"
        :model-value="listings.floor ?? ''"
        :options="floorOptions"
        :placeholder="t('searchMap.floorAny')"
        select-class="search-sidebar__select"
        @change="onFloorSelect"
      />
    </section>

    <div class="search-sidebar__divider" />

    <section class="search-sidebar__section">
      <h3 class="search-sidebar__label">{{ t('filters.rooms') }}</h3>
      <FilterSelect
        overlay-id="search-rooms"
        :model-value="listings.rooms ?? ''"
        :options="roomOptions"
        :placeholder="t('searchMap.roomsAny')"
        select-class="search-sidebar__select"
        @change="onRoomsSelect"
      />
    </section>

    <div class="search-sidebar__divider" />

    <section class="search-sidebar__section">
      <h3 class="search-sidebar__label">{{ t('filters.area') }}</h3>
      <RangeSlider
        v-model:min-value="areaMinDraft"
        v-model:max-value="areaMaxDraft"
        :min="areaBounds.min"
        :max="areaBounds.max"
        @mouseup="applyAreaRange"
        @touchend="applyAreaRange"
      />
      <div class="search-sidebar__inputs">
        <div class="search-sidebar__input-box">
          <span class="search-sidebar__input-caption">{{ t('searchMap.from') }}</span>
          <span class="search-sidebar__input-value">{{ areaMinDraft }}</span>
        </div>
        <div class="search-sidebar__input-box">
          <span class="search-sidebar__input-caption">{{ t('searchMap.to') }}</span>
          <span class="search-sidebar__input-value">{{ areaMaxDraft }}</span>
        </div>
      </div>
    </section>

    <div class="search-sidebar__divider" />

    <section class="search-sidebar__toggles">
      <ToggleSwitch v-model="listings.verifiedOnly" :label="t('searchMap.verifiedOnly')" />
    </section>

    <button type="button" class="search-sidebar__apply" @click="applyFilters">
      {{ showCountLabel }}
    </button>
  </aside>
</template>

<style scoped>
.search-sidebar {
  display: flex;
  flex-direction: column;
  gap: 0;
  width: 100%;
  max-width: var(--figma-search-sidebar-width);
  padding: 21px 24px 24px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
}

.search-sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 16px;
}

.search-sidebar__title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--figma-ink);
}

.search-sidebar__reset {
  border: none;
  background: transparent;
  padding: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--figma-accent);
  cursor: pointer;
}

.search-sidebar__divider {
  height: 1px;
  background: var(--figma-border);
  margin: 0 0 18px;
}

.search-sidebar__section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 18px;
}

.search-sidebar__label {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--figma-ink);
}

.search-sidebar__deal-types {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.search-sidebar__deal-type {
  min-height: 40px;
  padding: 0 8px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface);
  font-size: 13px;
  font-weight: 600;
  color: var(--figma-ink);
  cursor: pointer;
  transition:
    background-color 0.16s ease,
    border-color 0.16s ease,
    color 0.16s ease;
}

.search-sidebar__deal-type:hover {
  border-color: var(--figma-accent);
}

.search-sidebar__deal-type--active {
  border-color: var(--figma-accent);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.search-sidebar__inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.search-sidebar__input-box {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 29px;
  padding: 4px 12px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
}

.search-sidebar__input-caption {
  font-size: 10px;
  color: var(--figma-ink);
}

.search-sidebar__input-value {
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.search-sidebar__select :deep(.filter-select__trigger) {
  width: 100%;
  height: 39px;
  padding: 0 16px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface);
}

.search-sidebar__select :deep(.filter-chip__value) {
  font-size: 14px;
  font-weight: 600;
}

.search-sidebar__toggles {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-bottom: 20px;
}

.search-sidebar__apply {
  width: 100%;
  margin-top: auto;
  height: 44px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.search-sidebar__apply:hover {
  background: var(--figma-accent-hover);
}

@media (max-width: 1279px) {
  .search-sidebar {
    max-width: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .search-sidebar__deal-type,
  .search-sidebar__apply {
    transition: none;
  }
}
</style>
