import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as favoritesApi from '@/api/favorites'
import { i18n } from '@/modules/locale'
import type { CollectionToggleResponse, FavoriteDto, FavoriteItemDto, ListingDto } from '@/types'
import { useToastStore } from '@/stores/toast'

export const useFavoritesStore = defineStore('favorites', () => {
  const byListingId = ref<Map<number, FavoriteDto>>(new Map())
  const pageItems = ref<FavoriteItemDto[]>([])
  const loading = ref(false)
  const loaded = ref(false)
  const pageLoaded = ref(false)
  const error = ref<string | null>(null)

  const count = computed(() => byListingId.value.size)
  const favoriteListingIds = computed(() => Array.from(byListingId.value.keys()))
  const listings = computed(() => pageItems.value.map((item) => item.listing))

  function syncMap(items: FavoriteItemDto[]) {
    const next = new Map<number, FavoriteDto>()
    for (const item of items) {
      next.set(item.listingId, {
        id: item.id,
        userId: item.userId,
        listingId: item.listingId,
      })
    }
    byListingId.value = next
  }

  function isFavorite(listingId: number): boolean {
    return favoriteListingIds.value.includes(listingId)
  }

  function applyToggleResult(listingId: number, result: CollectionToggleResponse, listing?: ListingDto) {
    const next = new Map(byListingId.value)

    if (result.active && result.item) {
      next.set(listingId, result.item)
      if (listing) {
        const withoutCurrent = pageItems.value.filter((item) => item.listingId !== listingId)
        pageItems.value = [
          {
            id: result.item.id,
            userId: result.item.userId,
            listingId: result.item.listingId,
            listing,
          },
          ...withoutCurrent,
        ]
      }
    } else {
      next.delete(listingId)
      pageItems.value = pageItems.value.filter((item) => item.listingId !== listingId)
    }

    byListingId.value = next
    loaded.value = true
  }

  async function load() {
    loading.value = true
    error.value = null
    try {
      const items = await favoritesApi.fetchFavorites()
      pageItems.value = items
      syncMap(items)
      loaded.value = true
      pageLoaded.value = true
    } catch {
      reset()
      error.value = 'load_failed'
    } finally {
      loading.value = false
    }
  }

  async function loadPage() {
    pageLoaded.value = false
    await load()
  }

  async function toggle(listingId: number, listing?: ListingDto) {
    loading.value = true
    error.value = null
    try {
      const result = await favoritesApi.toggleFavorite(listingId)
      applyToggleResult(listingId, result, listing)
      if (result.active) {
        useToastStore().show(i18n.global.t('collections.favorites.added'))
      }
    } catch {
      error.value = 'toggle_failed'
    } finally {
      loading.value = false
    }
  }

  async function removeByListingId(listingId: number) {
    const record = byListingId.value.get(listingId)
    if (!record) {
      return
    }
    loading.value = true
    try {
      await favoritesApi.removeFavorite(record.id)
      const next = new Map(byListingId.value)
      next.delete(listingId)
      byListingId.value = next
      pageItems.value = pageItems.value.filter((item) => item.listingId !== listingId)
    } finally {
      loading.value = false
    }
  }

  function reset() {
    byListingId.value = new Map()
    pageItems.value = []
    loaded.value = false
    pageLoaded.value = false
    error.value = null
    loading.value = false
  }

  return {
    pageItems,
    listings,
    count,
    favoriteListingIds,
    loading,
    loaded,
    pageLoaded,
    error,
    isFavorite,
    load,
    loadPage,
    toggle,
    removeByListingId,
    reset,
  }
})
