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
import { buildRoomFilterOptions } from '@/lib/listingRooms'
import { useCurrencyStore } from '@/stores/currency'
import { useListingsStore } from '@/stores/listings'

const props = defineProps<{
  dealType: 'sale' | 'rent' | 'commercial'
}>()

const { t } = useI18n()
const listings = useListingsStore()
const { code: currency } = storeToRefs(useCurrencyStore())

provideFilterOverlayGroup()

const localePrefix = computed(() => {
  if (props.dealType === 'sale') {
    return 'saleCatalog'
  }
  if (props.dealType === 'commercial') {
    return 'commercialCatalog'
  }
  return 'rentCatalog'
})

const isSaleLikeCatalog = computed(() => props.dealType === 'sale' || props.dealType === 'commercial')

const rentPriceBounds = { min: 200, max: 5000 }
const salePriceBoundsUsd = { min: 30_000, max: 500_000 }

const priceBounds = computed(() => {
  if (isSaleLikeCatalog.value) {
    return {
      min: convertFromUsd(salePriceBoundsUsd.min, currency.value),
      max: convertFromUsd(salePriceBoundsUsd.max, currency.value),
    }
  }
  return rentPriceBounds
})

const areaBounds = { min: 20, max: 150 }

const priceMinDraft = ref(priceBounds.value.min)
const priceMaxDraft = ref(priceBounds.value.max)
const areaMinDraft = ref(areaBounds.min)
const areaMaxDraft = ref(areaBounds.max)

const floorOptions = computed(() => [
  { value: '', label: t(`${localePrefix.value}.floorAny`) },
  ...Array.from({ length: 25 }, (_, index) => ({
    value: index + 1,
    label: String(index + 1),
  })),
])

const roomOptions = computed(() =>
  buildRoomFilterOptions(t(`${localePrefix.value}.roomsAny`), t),
)

const showCountLabel = computed(() =>
  t(`${localePrefix.value}.showCount`, { n: formatFoundCount(listings.total) }),
)

const priceFromLabel = computed(() =>
  formatListingPrice(convertToUsd(priceMinDraft.value, currency.value), currency.value),
)

const priceToLabel = computed(() =>
  formatListingPrice(convertToUsd(priceMaxDraft.value, currency.value), currency.value),
)

const priceLabel = computed(() => {
  if (props.dealType === 'sale') {
    return t('saleCatalog.price')
  }
  if (props.dealType === 'commercial') {
    return t('commercialCatalog.price')
  }
  return t('rentCatalog.priceMonthly')
})

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

watch([currency, () => props.dealType], syncDraftFromStore, { immediate: true })

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

watch(
  () => [listings.rentDeposit, listings.rentUtilitiesIncluded, listings.rentNoCommission],
  () => {
    if (props.dealType !== 'rent') {
      return
    }
    void listings.search()
  },
)

watch(
  () => [listings.saleNoAgents, listings.saleFromOwner, listings.saleWithRenovation],
  () => {
    if (!isSaleLikeCatalog.value) {
      return
    }
    void listings.search()
  },
)

watch(
  () => listings.verifiedOnly,
  () => {
    void listings.search()
  },
)

async function applyFilters() {
  await applyPriceRange()
  await applyAreaRange()
}
</script>

<template>
  <aside class="catalog-sidebar catalog-sidebar--compact">
    <input
      :id="`catalog-sidebar-toggle-${dealType}`"
      class="catalog-sidebar__toggle-input"
      type="checkbox"
    />
    <div class="catalog-sidebar__header">
      <label class="catalog-sidebar__toggle" :for="`catalog-sidebar-toggle-${dealType}`">
        <h2 class="catalog-sidebar__title">{{ t('filters.title') }}</h2>
        <span class="catalog-sidebar__toggle-hint" aria-hidden="true" />
      </label>
      <button type="button" class="catalog-sidebar__reset" @click="resetSidebar">
        {{ t('filters.reset') }}
      </button>
    </div>

    <div class="catalog-sidebar__body">
    <div class="catalog-sidebar__divider" />

    <section class="catalog-sidebar__section">
      <h3 class="catalog-sidebar__label">{{ priceLabel }}</h3>
      <RangeSlider
        v-model:min-value="priceMinDraft"
        v-model:max-value="priceMaxDraft"
        :min="priceBounds.min"
        :max="priceBounds.max"
        :step="isSaleLikeCatalog ? 1000 : 50"
        @mouseup="applyPriceRange"
        @touchend="applyPriceRange"
      />
      <div class="catalog-sidebar__inputs">
        <div class="catalog-sidebar__input-box">
          <span class="catalog-sidebar__input-caption">{{ t(`${localePrefix}.from`) }}</span>
          <CurrencyText class="catalog-sidebar__input-value" :text="priceFromLabel" />
        </div>
        <div class="catalog-sidebar__input-box">
          <span class="catalog-sidebar__input-caption">{{ t(`${localePrefix}.to`) }}</span>
          <CurrencyText class="catalog-sidebar__input-value" :text="priceToLabel" />
        </div>
      </div>
    </section>

    <div class="catalog-sidebar__divider" />

    <section class="catalog-sidebar__section">
      <h3 class="catalog-sidebar__label">{{ t(`${localePrefix}.floor`) }}</h3>
      <FilterSelect
        :overlay-id="`${dealType}-catalog-floor`"
        :model-value="listings.floor ?? ''"
        :options="floorOptions"
        :placeholder="t(`${localePrefix}.floorAny`)"
        :select-class="
          listings.floor != null
            ? 'catalog-sidebar__select'
            : 'catalog-sidebar__select catalog-sidebar__select--placeholder'
        "
        @change="onFloorSelect"
      />
    </section>

    <div class="catalog-sidebar__divider" />

    <section class="catalog-sidebar__section">
      <h3 class="catalog-sidebar__label">{{ t(`${localePrefix}.roomsCount`) }}</h3>
      <FilterSelect
        :overlay-id="`${dealType}-catalog-rooms`"
        :model-value="listings.rooms ?? ''"
        :options="roomOptions"
        :placeholder="t(`${localePrefix}.roomsAny`)"
        :select-class="
          listings.rooms != null
            ? 'catalog-sidebar__select'
            : 'catalog-sidebar__select catalog-sidebar__select--placeholder'
        "
        @change="onRoomsSelect"
      />
    </section>

    <div class="catalog-sidebar__divider" />

    <section class="catalog-sidebar__section">
      <h3 class="catalog-sidebar__label">{{ t('filters.area') }}</h3>
      <RangeSlider
        v-model:min-value="areaMinDraft"
        v-model:max-value="areaMaxDraft"
        :min="areaBounds.min"
        :max="areaBounds.max"
        @mouseup="applyAreaRange"
        @touchend="applyAreaRange"
      />
      <div class="catalog-sidebar__inputs">
        <div class="catalog-sidebar__input-box">
          <span class="catalog-sidebar__input-caption">{{ t(`${localePrefix}.from`) }}</span>
          <span class="catalog-sidebar__input-value">{{ areaMinDraft }}</span>
        </div>
        <div class="catalog-sidebar__input-box">
          <span class="catalog-sidebar__input-caption">{{ t(`${localePrefix}.to`) }}</span>
          <span class="catalog-sidebar__input-value">{{ areaMaxDraft }}</span>
        </div>
      </div>
    </section>

    <div class="catalog-sidebar__divider" />

    <section class="catalog-sidebar__toggles">
      <template v-if="isSaleLikeCatalog">
        <ToggleSwitch v-model="listings.saleNoAgents" :label="t('saleCatalog.noAgents')" />
        <ToggleSwitch v-model="listings.saleFromOwner" :label="t('saleCatalog.fromOwner')" />
        <ToggleSwitch v-model="listings.saleWithRenovation" :label="t('saleCatalog.withRenovation')" />
        <ToggleSwitch
          v-model="listings.verifiedOnly"
          :label="t(`${localePrefix}.verifiedOnly`)"
        />
      </template>
      <template v-else>
        <ToggleSwitch v-model="listings.rentDeposit" :label="t('rentCatalog.deposit')" />
        <ToggleSwitch v-model="listings.rentUtilitiesIncluded" :label="t('rentCatalog.utilitiesIncluded')" />
        <ToggleSwitch v-model="listings.rentNoCommission" :label="t('rentCatalog.noCommission')" />
        <ToggleSwitch v-model="listings.verifiedOnly" :label="t('rentCatalog.verifiedOnly')" />
      </template>
    </section>

    <button type="button" class="catalog-sidebar__apply" @click="applyFilters">
      {{ showCountLabel }}
    </button>
    </div>
  </aside>
</template>

<style scoped>
.catalog-sidebar {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0;
  width: 100%;
  max-width: var(--figma-catalog-sidebar-width);
  padding: 21px 24px 24px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
}

.catalog-sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 16px;
}

.catalog-sidebar__toggle-input {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.catalog-sidebar__toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  cursor: default;
}

.catalog-sidebar__toggle-hint {
  display: none;
  width: 10px;
  height: 10px;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 0.2s ease;
  color: var(--figma-text-muted);
}

.catalog-sidebar__title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--figma-ink);
}

.catalog-sidebar__reset {
  border: none;
  background: transparent;
  padding: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--figma-accent);
  cursor: pointer;
}

.catalog-sidebar__divider {
  height: 1px;
  background: var(--figma-border);
  margin: 0 0 18px;
}

.catalog-sidebar__section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 18px;
}

.catalog-sidebar__label {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--figma-ink);
}

.catalog-sidebar__inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.catalog-sidebar__input-box {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 29px;
  padding: 4px 12px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
}

.catalog-sidebar__input-caption {
  font-size: 10px;
  color: var(--figma-ink);
}

.catalog-sidebar__input-value {
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.catalog-sidebar__select :deep(.filter-select__trigger) {
  width: 100%;
  height: 39px;
  padding: 0 16px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface);
}

.catalog-sidebar__select :deep(.filter-chip__value) {
  font-size: 14px;
  font-weight: 600;
}

.catalog-sidebar__toggles {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-bottom: 20px;
}

.catalog-sidebar__apply {
  width: 100%;
  height: 44px;
  margin-bottom: 16px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.catalog-sidebar__apply:hover {
  background: var(--figma-accent-hover);
}

@media (min-width: 768px) {
  .catalog-sidebar {
    max-height: calc(100vh - var(--figma-catalog-filter-sticky-top, 16px) - 16px);
    max-height: calc(100dvh - var(--figma-catalog-filter-sticky-top, 16px) - 16px);
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
  }
}

@media (max-width: 1279px) {
  .catalog-sidebar {
    max-width: none;
  }
}

.catalog-sidebar--compact {
  padding: 16px 18px 18px;
}

.catalog-sidebar--compact .catalog-sidebar__header {
  padding-bottom: 12px;
}

.catalog-sidebar--compact .catalog-sidebar__title,
.catalog-sidebar--compact .catalog-sidebar__reset {
  font-size: 14px;
}

.catalog-sidebar--compact .catalog-sidebar__divider {
  margin-bottom: 12px;
}

.catalog-sidebar--compact .catalog-sidebar__section {
  gap: 8px;
  padding-bottom: 12px;
}

.catalog-sidebar--compact .catalog-sidebar__label {
  font-size: 14px;
  line-height: 1.25;
}

.catalog-sidebar--compact .catalog-sidebar__inputs {
  gap: 10px;
}

.catalog-sidebar--compact .catalog-sidebar__input-box {
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  min-height: 30px;
  padding: 4px 12px;
  border-radius: 10px;
}

.catalog-sidebar--compact .catalog-sidebar__input-caption {
  font-size: 10px;
  color: var(--figma-text-muted);
  text-transform: lowercase;
}

.catalog-sidebar--compact .catalog-sidebar__input-value {
  font-size: 13px;
  line-height: 1.25;
  text-align: right;
}

.catalog-sidebar--compact .catalog-sidebar__select :deep(.filter-select__trigger) {
  height: 36px;
  padding: 0 14px;
  border-radius: 10px;
}

.catalog-sidebar--compact .catalog-sidebar__select :deep(.filter-chip__value) {
  font-size: 13px;
  line-height: 1.25;
}

.catalog-sidebar--compact .catalog-sidebar__select--placeholder :deep(.filter-chip__value) {
  font-weight: 400;
  color: var(--figma-text-muted);
}

.catalog-sidebar--compact .catalog-sidebar__select :deep(.filter-chip__chevron) {
  right: 10px;
  bottom: 5px;
  width: 8px;
  height: 16px;
}

.catalog-sidebar--compact :deep(.range-slider__track) {
  height: 14px;
}

.catalog-sidebar--compact :deep(.range-slider__input) {
  height: 14px;
}

.catalog-sidebar--compact :deep(.range-slider__fill),
.catalog-sidebar--compact :deep(.range-slider__track::before) {
  top: 5px;
  height: 3px;
}

.catalog-sidebar--compact :deep(.range-slider__input::-webkit-slider-thumb) {
  width: 14px;
  height: 14px;
}

.catalog-sidebar--compact :deep(.range-slider__input::-moz-range-thumb) {
  width: 14px;
  height: 14px;
}

.catalog-sidebar--compact .catalog-sidebar__toggles {
  gap: 4px;
  padding-bottom: 14px;
}

.catalog-sidebar--compact .catalog-sidebar__toggles :deep(.toggle-switch) {
  min-height: 32px;
  gap: 10px;
}

.catalog-sidebar--compact .catalog-sidebar__toggles :deep(.toggle-switch__label) {
  font-size: 14px;
  line-height: 1.25;
}

.catalog-sidebar--compact .catalog-sidebar__apply {
  height: 40px;
  margin-bottom: 12px;
  font-size: 13px;
  border-radius: 10px;
}

@media (max-width: 767px) {
  .catalog-sidebar {
    max-width: none;
    padding: 12px 14px 14px;
  }

  .catalog-sidebar__header {
    padding-bottom: 0;
  }

  .catalog-sidebar__toggle {
    cursor: pointer;
    min-height: var(--touch-target-min);
  }

  .catalog-sidebar__toggle-hint {
    display: inline-block;
  }

  .catalog-sidebar__toggle-input:checked ~ .catalog-sidebar__header .catalog-sidebar__toggle-hint {
    transform: rotate(-135deg);
  }

  .catalog-sidebar__body {
    display: none;
  }

  .catalog-sidebar__toggle-input:checked ~ .catalog-sidebar__body {
    display: block;
    padding-top: 12px;
  }

  .catalog-sidebar__reset {
    min-height: var(--touch-target-min);
  }
}
</style>
