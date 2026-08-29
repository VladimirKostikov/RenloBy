<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchAddressSuggestions } from '@/api/listings'
import { applyListingsSearchLocation } from '@/lib/applyListingsSearchLocation'
import { forwardGeocode } from '@/lib/forwardGeocode'
import { mapAddressSuggestItem } from '@/lib/mapAddressSuggest'
import {
  resolveSearchLocation,
  type ResolvedSearchLocation,
} from '@/lib/resolveSearchLocation'
import type { HeaderSuggestItem } from '@/lib/headerSearchSuggest'
import { useListingsStore } from '@/stores/listings'

const emit = defineEmits<{
  focusCoords: [payload: { latitude: number; longitude: number; zoom: number }]
}>()

const SUGGEST_DEBOUNCE_MS = 400
const STREET_ZOOM = 16

const { t, locale } = useI18n()
const listings = useListingsStore()

const root = ref<HTMLElement | null>(null)
const inputEl = ref<HTMLInputElement | null>(null)
const draftQuery = ref('')
const open = ref(false)
const loading = ref(false)
const locationSuggestions = ref<ResolvedSearchLocation[]>([])
const addressSuggestions = ref<HeaderSuggestItem[]>([])
let suggestTimer: ReturnType<typeof setTimeout> | undefined
let suggestRequestId = 0
let suppressSuggest = false

const showMenu = computed(() =>
  open.value
  && draftQuery.value.trim().length >= 2
  && (loading.value
    || locationSuggestions.value.length > 0
    || addressSuggestions.value.length > 0),
)

watch(draftQuery, (value) => {
  if (suppressSuggest) {
    return
  }
  if (suggestTimer) {
    clearTimeout(suggestTimer)
  }
  const trimmed = value.trim()
  if (trimmed.length < 2) {
    closeSuggestMenu()
    return
  }
  loading.value = true
  open.value = true
  suggestTimer = setTimeout(() => {
    suggestTimer = undefined
    void refreshSuggestions(trimmed)
  }, SUGGEST_DEBOUNCE_MS)
})

function buildLocalLocations(query: string): ResolvedSearchLocation[] {
  const resolved = resolveSearchLocation({
    query,
    cities: listings.cities,
    districts: listings.districts,
    regionLabel: (slug) => t(`map.regions.${slug}`),
  })
  return resolved ? [resolved] : []
}

async function refreshSuggestions(query: string) {
  const requestId = ++suggestRequestId
  const localLocations = buildLocalLocations(query)

  try {
    const addressResponse = await fetchAddressSuggestions(query, 8)
    if (requestId !== suggestRequestId || suppressSuggest) {
      return
    }
    locationSuggestions.value = localLocations
    addressSuggestions.value = addressResponse
      .map(mapAddressSuggestItem)
      .filter((item) => {
        if (item.kind === 'city' || item.kind === 'district') {
          return !localLocations.some((local) => local.label === item.label)
        }
        return item.kind === 'street' || item.kind === 'metro'
      })
  } catch {
    if (requestId !== suggestRequestId || suppressSuggest) {
      return
    }
    locationSuggestions.value = localLocations
    addressSuggestions.value = []
  } finally {
    if (requestId === suggestRequestId && !suppressSuggest) {
      loading.value = false
      open.value = draftQuery.value.trim().length >= 2
    }
  }
}

function locationKindLabel(kind: ResolvedSearchLocation['kind'] | HeaderSuggestItem['kind']): string {
  if (kind === 'region') {
    return t('search.suggestRegion')
  }
  if (kind === 'city') {
    return t('search.suggestCity')
  }
  if (kind === 'district') {
    return t('search.suggestDistrict')
  }
  if (kind === 'metro') {
    return t('search.suggestMetro')
  }
  return t('search.suggestStreet')
}

function closeSuggestMenu() {
  if (suggestTimer) {
    clearTimeout(suggestTimer)
    suggestTimer = undefined
  }
  suggestRequestId += 1
  loading.value = false
  open.value = false
  locationSuggestions.value = []
  addressSuggestions.value = []
}

async function applyResolvedLocation(location: ResolvedSearchLocation) {
  suppressSuggest = true
  closeSuggestMenu()
  draftQuery.value = location.label
  inputEl.value?.blur()
  await applyListingsSearchLocation(listings, location)
  await nextTick()
  suppressSuggest = false
}

async function focusGeocodedQuery(query: string, label?: string) {
  const geocoded = await forwardGeocode(query, locale.value === 'en' ? 'en' : 'ru')
  if (!geocoded) {
    listings.searchQuery = query
    await listings.search()
    return
  }

  draftQuery.value = label ?? geocoded.label
  listings.searchQuery = query
  emit('focusCoords', {
    latitude: geocoded.latitude,
    longitude: geocoded.longitude,
    zoom: STREET_ZOOM,
  })
  await listings.search()
}

async function applyAddressSuggestion(item: HeaderSuggestItem) {
  if (item.kind === 'city' && item.cityId) {
    const city = listings.cities.find((entry) => entry.id === item.cityId)
    await applyResolvedLocation({
      kind: 'city',
      cityId: item.cityId,
      regionSlug: city?.regionSlug ?? listings.regionSlug ?? '',
      label: item.label,
    })
    return
  }

  if (item.kind === 'district' && item.cityId && item.districtId) {
    const city = listings.cities.find((entry) => entry.id === item.cityId)
    await applyResolvedLocation({
      kind: 'district',
      cityId: item.cityId,
      districtId: item.districtId,
      regionSlug: city?.regionSlug ?? 'minsk-city',
      label: item.label,
    })
    return
  }

  suppressSuggest = true
  closeSuggestMenu()
  inputEl.value?.blur()
  await focusGeocodedQuery(item.query, item.label)
  await nextTick()
  suppressSuggest = false
}

async function submitSearch() {
  suppressSuggest = true
  closeSuggestMenu()
  const trimmed = draftQuery.value.trim()
  inputEl.value?.blur()

  if (!trimmed) {
    suppressSuggest = false
    return
  }

  const location = resolveSearchLocation({
    query: trimmed,
    cities: listings.cities,
    districts: listings.districts,
    regionLabel: (slug) => t(`map.regions.${slug}`),
  })

  if (location) {
    suppressSuggest = false
    await applyResolvedLocation(location)
    return
  }

  await focusGeocodedQuery(trimmed)
  await nextTick()
  suppressSuggest = false
}

function onFocus() {
  if (draftQuery.value.trim().length >= 2) {
    open.value = true
    if (locationSuggestions.value.length === 0 && addressSuggestions.value.length === 0) {
      loading.value = true
      void refreshSuggestions(draftQuery.value.trim())
    }
  }
}

function onDocumentClick(event: MouseEvent) {
  const target = event.target as Node
  if (root.value?.contains(target)) {
    return
  }
  open.value = false
}

function clearQuery() {
  draftQuery.value = ''
  closeSuggestMenu()
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick)
  if (suggestTimer) {
    clearTimeout(suggestTimer)
  }
})
</script>

<template>
  <div ref="root" class="map-address-search">
    <label class="map-address-search__field">
      <span class="visually-hidden">{{ t('map.addressSearch') }}</span>
      <input
        ref="inputEl"
        v-model="draftQuery"
        type="search"
        class="map-address-search__input"
        :placeholder="t('map.addressPlaceholder')"
        autocomplete="off"
        @focus="onFocus"
        @keydown.enter.prevent="submitSearch"
        @keydown.escape="open = false"
      />
      <button
        v-if="draftQuery"
        type="button"
        class="map-address-search__clear"
        :aria-label="t('map.addressClear')"
        @click="clearQuery"
      >
        ×
      </button>
      <span v-if="loading" class="map-address-search__spinner" aria-hidden="true" />
      <img data-theme-ink
        v-else
        src="/figma/search.svg"
        alt=""
        class="map-address-search__icon"
        width="15"
        height="15"
      />
    </label>

    <Transition name="map-address-suggest">
      <div
        v-if="showMenu"
        class="map-address-search__suggest"
        role="listbox"
      >
        <div v-if="loading" class="map-address-search__suggest-loading">
          {{ t('search.suggestLoading') }}
        </div>
        <template v-else>
          <button
            v-for="location in locationSuggestions"
            :key="`local-${location.kind}-${location.label}`"
            type="button"
            class="map-address-search__suggest-item"
            role="option"
            @mousedown.prevent="applyResolvedLocation(location)"
          >
            <span class="map-address-search__suggest-label">{{ location.label }}</span>
            <span class="map-address-search__suggest-meta">{{ locationKindLabel(location.kind) }}</span>
          </button>
          <button
            v-for="item in addressSuggestions"
            :key="item.id"
            type="button"
            class="map-address-search__suggest-item"
            role="option"
            @mousedown.prevent="applyAddressSuggestion(item)"
          >
            <span class="map-address-search__suggest-label">{{ item.label }}</span>
            <span class="map-address-search__suggest-meta">
              {{ item.subtitle || locationKindLabel(item.kind) }}
            </span>
          </button>
          <div
            v-if="locationSuggestions.length === 0 && addressSuggestions.length === 0"
            class="map-address-search__suggest-empty"
          >
            {{ t('search.suggestEmpty') }}
          </div>
        </template>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.map-address-search {
  position: relative;
  width: min(320px, calc(100vw - 48px));
}

.map-address-search__field {
  position: relative;
  display: block;
}

.map-address-search__input {
  width: 100%;
  height: 36px;
  padding: 0 36px 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: 18px;
  background: var(--figma-surface);
  font-family: inherit;
  font-size: 13px;
  font-weight: 500;
  color: var(--figma-ink);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  box-sizing: border-box;
}

.map-address-search__input::placeholder {
  color: var(--figma-text-muted);
  font-weight: 500;
}

.map-address-search__input:focus {
  outline: none;
  border-color: rgba(225, 69, 84, 0.45);
}

.map-address-search__icon,
.map-address-search__spinner {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  pointer-events: none;
}

.map-address-search__clear {
  position: absolute;
  top: 50%;
  right: 30px;
  transform: translateY(-50%);
  width: 22px;
  height: 22px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: transparent;
  font-size: 16px;
  line-height: 1;
  color: var(--figma-text-muted);
  cursor: pointer;
}

.map-address-search__clear:hover {
  color: var(--figma-ink);
}

.map-address-search__spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(225, 69, 84, 0.2);
  border-top-color: var(--figma-accent);
  border-radius: 50%;
  animation: map-address-spin 0.7s linear infinite;
}

@keyframes map-address-spin {
  to {
    transform: translateY(-50%) rotate(360deg);
  }
}

.map-address-search__suggest {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 700;
  max-height: 280px;
  overflow: auto;
  padding: 6px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: var(--figma-surface);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.map-address-search__suggest-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  width: 100%;
  min-height: 44px;
  padding: 8px 10px;
  border: none;
  border-radius: 8px;
  background: transparent;
  text-align: left;
  cursor: pointer;
}

.map-address-search__suggest-item:hover {
  background: rgba(225, 69, 84, 0.06);
}

.map-address-search__suggest-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--figma-ink);
}

.map-address-search__suggest-meta {
  font-size: 11px;
  color: var(--figma-text-muted);
}

.map-address-search__suggest-loading,
.map-address-search__suggest-empty {
  padding: 12px 10px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.map-address-suggest-enter-active,
.map-address-suggest-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.map-address-suggest-enter-from,
.map-address-suggest-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.visually-hidden {
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
</style>
