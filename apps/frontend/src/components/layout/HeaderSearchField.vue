<script lang="ts">
export const SUGGEST_DEBOUNCE_MS = 1000
</script>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { fetchListings } from '@/api/listings'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import { applyListingsSearchLocation } from '@/lib/applyListingsSearchLocation'
import { listingDetailPath } from '@/lib/fullPageNav'
import { resolveSearchLocation } from '@/lib/resolveSearchLocation'
import { useListingsStore } from '@/stores/listings'
import type { ListingDto } from '@/types'

const { t } = useI18n()
const router = useRouter()
const listings = useListingsStore()

const draftQuery = ref(listings.searchQuery)
const open = ref(false)
const loading = ref(false)
const suggestions = ref<ListingDto[]>([])
const root = ref<HTMLElement | null>(null)
const inputEl = ref<HTMLInputElement | null>(null)
const menuStyle = ref<{ top: string; left: string; width: string } | null>(null)

let suggestTimer: ReturnType<typeof setTimeout> | undefined
let suggestRequestId = 0
let suppressSuggest = false

const showMenu = computed(
  () => open.value && (loading.value || suggestions.value.length > 0 || draftQuery.value.trim().length >= 2),
)

watch(
  () => listings.searchQuery,
  (query) => {
    if (query !== draftQuery.value) {
      draftQuery.value = query
    }
  },
)

watch(draftQuery, (query) => {
  if (suppressSuggest) {
    return
  }

  if (suggestTimer) {
    clearTimeout(suggestTimer)
  }

  const trimmed = query.trim()
  if (trimmed.length < 2) {
    loading.value = false
    suggestions.value = []
    open.value = false
    menuStyle.value = null
    return
  }

  loading.value = true
  open.value = true
  void updateMenuPosition()

  suggestTimer = setTimeout(() => {
    void refreshSuggestions(trimmed)
  }, SUGGEST_DEBOUNCE_MS)
})

async function refreshSuggestions(query: string) {
  const requestId = ++suggestRequestId
  try {
    const response = await fetchListings({
      query,
      dealType: listings.dealType,
      cityId: listings.cityId,
      regionSlug: listings.regionSlug,
      page: 1,
      limit: 8,
      sort: 'newest',
    })
    if (requestId !== suggestRequestId || suppressSuggest) {
      return
    }
    suggestions.value = response.items
  } catch {
    if (requestId !== suggestRequestId || suppressSuggest) {
      return
    }
    suggestions.value = []
  } finally {
    if (requestId === suggestRequestId && !suppressSuggest) {
      loading.value = false
      open.value = draftQuery.value.trim().length >= 2
      await updateMenuPosition()
    }
  }
}

async function updateMenuPosition() {
  await nextTick()
  const anchor = inputEl.value ?? root.value
  if (!anchor) {
    return
  }

  const rect = anchor.getBoundingClientRect()
  menuStyle.value = {
    top: `${rect.bottom + 6}px`,
    left: `${rect.left}px`,
    width: `${Math.max(rect.width, 320)}px`,
  }
}

function listingMeta(listing: ListingDto): string {
  return [
    t('listing.roomsShort', { n: listing.rooms }),
    t('listing.areaShort', { n: listing.area }),
  ].join(', ')
}

function closeSuggestMenu() {
  if (suggestTimer) {
    clearTimeout(suggestTimer)
    suggestTimer = undefined
  }
  suggestRequestId += 1
  loading.value = false
  open.value = false
  suggestions.value = []
  menuStyle.value = null
}

async function applySuggestion(listing: ListingDto) {
  suppressSuggest = true
  closeSuggestMenu()
  draftQuery.value = listing.address
  inputEl.value?.blur()

  void listings.openDetailListing(listing.id)
  listings.focusListingOnMap(listing.id)
  const href = listingDetailPath(listing.id)
  if (router.currentRoute.value.path !== href) {
    await router.push(href)
  }

  await nextTick()
  suppressSuggest = false
}

async function submitSearch() {
  suppressSuggest = true
  closeSuggestMenu()
  const trimmed = draftQuery.value.trim()
  inputEl.value?.blur()

  if (!trimmed) {
    listings.searchQuery = ''
    await listings.search()
    await nextTick()
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
    draftQuery.value = location.label
    await applyListingsSearchLocation(listings, location)
    await nextTick()
    suppressSuggest = false
    return
  }

  listings.searchQuery = trimmed
  await listings.search()
  await nextTick()
  suppressSuggest = false
}

function onFocus() {
  if (draftQuery.value.trim().length >= 2) {
    open.value = true
    void updateMenuPosition()
    if (suggestions.value.length === 0) {
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
  if (target instanceof Element && target.closest('.header-search__suggest')) {
    return
  }
  open.value = false
}

function onViewportChange() {
  if (open.value) {
    void updateMenuPosition()
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  window.addEventListener('resize', onViewportChange)
  window.addEventListener('scroll', onViewportChange, true)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick)
  window.removeEventListener('resize', onViewportChange)
  window.removeEventListener('scroll', onViewportChange, true)
  if (suggestTimer) {
    clearTimeout(suggestTimer)
  }
})
</script>

<template>
  <label ref="root" class="header-search">
    <input
      ref="inputEl"
      v-model="draftQuery"
      type="search"
      class="header-search__input"
      :placeholder="t('search.placeholder')"
      autocomplete="off"
      @focus="onFocus"
      @keydown.enter.prevent="submitSearch"
      @keydown.escape="open = false"
    />
    <span v-if="loading" class="header-search__spinner" aria-hidden="true" />
    <img data-theme-ink
      v-else
      src="/figma/search.svg"
      alt=""
      class="header-search__icon"
      width="15"
      height="15"
    />

    <Teleport to="body">
      <Transition name="header-search-suggest">
        <div
          v-if="showMenu && menuStyle"
          class="header-search__suggest"
          role="listbox"
          :style="menuStyle"
        >
          <div v-if="loading" class="header-search__suggest-loading">
            {{ t('search.suggestLoading') }}
          </div>
          <template v-else>
            <button
              v-for="listing in suggestions"
              :key="listing.id"
              type="button"
              class="header-search__suggest-item"
              role="option"
              @mousedown.prevent="applySuggestion(listing)"
            >
              <img
                v-if="listing.images[0]"
                :src="listing.images[0]"
                :alt="listing.address"
                class="header-search__suggest-photo"
              />
              <div
                v-else
                class="header-search__suggest-photo header-search__suggest-photo--empty"
                aria-hidden="true"
              />
              <span class="header-search__suggest-label">{{ listing.address }}</span>
              <span class="header-search__suggest-meta">
                <CurrencyAmount :amount-usd="listing.price" />
                <span aria-hidden="true">·</span>
                <span>{{ listingMeta(listing) }}</span>
              </span>
            </button>
            <div v-if="suggestions.length === 0" class="header-search__suggest-empty">
              {{ t('search.suggestEmpty') }}
            </div>
          </template>
        </div>
      </Transition>
    </Teleport>
  </label>
</template>

<style scoped>
.header-search {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  height: 34px;
  max-width: none;
}

.header-search__input {
  width: 100%;
  height: 100%;
  padding: 0 36px 0 14px;
  border: 1px solid var(--figma-search-border);
  border-radius: 50px;
  background: var(--figma-surface);
  font-size: 11px;
  font-weight: 400;
  color: var(--figma-ink);
  text-align: left;
  transition: border-color 0.2s ease;
}

.header-search__input:hover,
.header-search__input:focus {
  outline: none;
  border-color: var(--figma-gray-mid);
}

.header-search__input::placeholder {
  color: var(--figma-ink);
}

.header-search__icon {
  position: absolute;
  right: 14px;
  pointer-events: none;
}

.header-search__spinner {
  position: absolute;
  right: 12px;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(225, 69, 84, 0.2);
  border-top-color: var(--figma-accent);
  border-radius: 50%;
  animation: header-search-spin 0.7s linear infinite;
}

@keyframes header-search-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<style>
.header-search__suggest {
  position: fixed;
  z-index: 4200;
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-height: min(360px, 55vh);
  overflow-y: auto;
  padding: 8px;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: var(--figma-surface);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.14);
}

.header-search__suggest-loading,
.header-search__suggest-empty {
  padding: 10px 12px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.header-search__suggest-item {
  display: grid;
  grid-template-columns: 48px minmax(0, 1fr);
  grid-template-rows: auto auto;
  column-gap: 10px;
  row-gap: 2px;
  width: 100%;
  padding: 8px 10px;
  border: none;
  border-radius: 10px;
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.header-search__suggest-item:hover {
  background: rgba(225, 69, 84, 0.06);
}

.header-search__suggest-photo {
  grid-row: 1 / span 2;
  width: 48px;
  height: 48px;
  border-radius: 10px;
  object-fit: cover;
  background: var(--figma-page-bg);
}

.header-search__suggest-photo--empty {
  background: rgba(146, 146, 146, 0.16);
}

.header-search__suggest-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--figma-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-search__suggest-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  font-size: 11px;
  color: var(--figma-text-muted);
  white-space: nowrap;
  overflow: hidden;
}

.header-search-suggest-enter-active,
.header-search-suggest-leave-active {
  transition:
    opacity 0.16s ease,
    transform 0.16s ease;
}

.header-search-suggest-enter-from,
.header-search-suggest-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (prefers-reduced-motion: reduce) {
  .header-search-suggest-enter-active,
  .header-search-suggest-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
