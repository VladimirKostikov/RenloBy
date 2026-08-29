import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { isAxiosError } from 'axios'
import * as comparisonsApi from '@/api/comparisons'
import { resolveApiError } from '@/lib/resolveApiError'
import { i18n } from '@/modules/locale'
import type { CollectionToggleResponse, ComparisonDto, ComparisonItemDto, ListingDto } from '@/types'
import { useToastStore } from '@/stores/toast'

export const COMPARISON_LIMIT = 4

export const useComparisonsStore = defineStore('comparisons', () => {
  const byListingId = ref<Map<number, ComparisonDto>>(new Map())
  const pageItems = ref<ComparisonItemDto[]>([])
  const loading = ref(false)
  const loaded = ref(false)
  const pageLoaded = ref(false)
  const error = ref<string | null>(null)
  const limitReached = ref(false)

  const count = computed(() => byListingId.value.size)
  const comparedListingIds = computed(() => Array.from(byListingId.value.keys()))
  const listings = computed(() => pageItems.value.map((item) => item.listing))

  function syncMap(items: ComparisonItemDto[]) {
    const next = new Map<number, ComparisonDto>()
    for (const item of items) {
      next.set(item.listingId, {
        id: item.id,
        userId: item.userId,
        listingId: item.listingId,
      })
    }
    byListingId.value = next
  }

  function isCompared(listingId: number): boolean {
    return byListingId.value.has(listingId)
  }

  function applyToggleResult(listingId: number, result: CollectionToggleResponse, listing?: ListingDto) {
    const next = new Map(byListingId.value)

    if (result.active && result.item) {
      next.set(listingId, result.item)
      limitReached.value = false
      if (listing) {
        const withoutCurrent = pageItems.value.filter((item) => item.listingId !== listingId)
        pageItems.value = [
          ...withoutCurrent,
          {
            id: result.item.id,
            userId: result.item.userId,
            listingId: result.item.listingId,
            listing,
          },
        ]
      }
    } else {
      next.delete(listingId)
      pageItems.value = pageItems.value.filter((item) => item.listingId !== listingId)
      limitReached.value = false
    }

    byListingId.value = next
    loaded.value = true
  }

  async function load() {
    loading.value = true
    error.value = null
    limitReached.value = false
    try {
      const items = await comparisonsApi.fetchComparisons()
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
    if (!isCompared(listingId) && byListingId.value.size >= COMPARISON_LIMIT) {
      const message = i18n.global.t('collections.compare.limitReached', {
        limit: COMPARISON_LIMIT,
      })
      limitReached.value = true
      error.value = message
      useToastStore().show(message)
      return
    }

    loading.value = true
    error.value = null
    try {
      const result = await comparisonsApi.toggleComparison(listingId)
      applyToggleResult(listingId, result, listing)
      useToastStore().show(
        i18n.global.t(result.active ? 'collections.compare.added' : 'collections.compare.removed'),
      )
    } catch (err: unknown) {
      if (isAxiosError(err) && err.response?.status === 422) {
        const message = i18n.global.t('collections.compare.limitReached', {
          limit: COMPARISON_LIMIT,
        })
        limitReached.value = true
        error.value = message
        useToastStore().show(message)
      } else {
        const message = resolveApiError(err, i18n.global.t, 'errors.generic').message
        error.value = message
        useToastStore().show(message)
      }
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
      await comparisonsApi.removeComparison(record.id)
      const next = new Map(byListingId.value)
      next.delete(listingId)
      byListingId.value = next
      pageItems.value = pageItems.value.filter((item) => item.listingId !== listingId)
      limitReached.value = false
      useToastStore().show(i18n.global.t('collections.compare.removed'))
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
    limitReached.value = false
    loading.value = false
  }

  return {
    pageItems,
    listings,
    count,
    comparedListingIds,
    loading,
    loaded,
    pageLoaded,
    error,
    limitReached,
    isCompared,
    load,
    loadPage,
    toggle,
    removeByListingId,
    reset,
  }
})
