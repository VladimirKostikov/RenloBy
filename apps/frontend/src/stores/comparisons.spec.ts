import { createPinia, setActivePinia } from 'pinia'
import { AxiosError } from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as comparisonsApi from '@/api/comparisons'
import { COMPARISON_LIMIT, useComparisonsStore } from '@/stores/comparisons'
import { useToastStore } from '@/stores/toast'
import type { ListingDto } from '@/types'

vi.mock('@/api/comparisons', () => ({
  fetchComparisons: vi.fn(),
  toggleComparison: vi.fn(),
  removeComparison: vi.fn(),
}))

function listingStub(id: number): ListingDto {
  return {
    id,
    dealType: 'sale',
    listingType: 'apartment',
    status: 'published',
    price: 100000,
    pricePerSqm: 2000,
    rooms: 2,
    area: 50,
    floor: 3,
    totalFloors: 9,
    address: `Address ${id}`,
    latitude: 0,
    longitude: 0,
    metroMinutes: null,
    verified: false,
    aiGoodPrice: false,
    rentTerm: null,
    hasDeposit: false,
    utilitiesIncluded: false,
    noCommission: false,
    fromOwner: false,
    hasRenovation: false,
    priceNegotiable: false,
    views: 0,
    images: [],
    publishedAt: '2026-01-01',
    userId: 1,
    cityId: 1,
    districtId: 1,
    metroStationId: null,
  }
}

async function fillComparison(store: ReturnType<typeof useComparisonsStore>) {
  for (let id = 1; id <= COMPARISON_LIMIT; id += 1) {
    vi.mocked(comparisonsApi.toggleComparison).mockResolvedValueOnce({
      active: true,
      item: { id, userId: null, listingId: id },
    })
    await store.toggle(id, listingStub(id))
  }
}

describe('useComparisonsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('notifies with toast when comparison limit is already full', async () => {
    const store = useComparisonsStore()
    const toast = useToastStore()
    const showSpy = vi.spyOn(toast, 'show')

    await fillComparison(store)
    showSpy.mockClear()
    vi.mocked(comparisonsApi.toggleComparison).mockClear()

    await store.toggle(99, listingStub(99))

    expect(comparisonsApi.toggleComparison).not.toHaveBeenCalled()
    expect(store.limitReached).toBe(true)
    expect(showSpy).toHaveBeenCalledWith(
      expect.stringContaining(String(COMPARISON_LIMIT)),
    )
  })

  it('notifies with toast when API returns 422 limit error', async () => {
    const store = useComparisonsStore()
    const toast = useToastStore()
    const showSpy = vi.spyOn(toast, 'show')

    vi.mocked(comparisonsApi.toggleComparison).mockRejectedValue(
      new AxiosError('limit', 'ERR', undefined, undefined, {
        status: 422,
        statusText: 'Unprocessable Entity',
        headers: {},
        config: {} as never,
        data: { error: 'comparison.limit_reached' },
      }),
    )

    await store.toggle(5, listingStub(5))

    expect(store.limitReached).toBe(true)
    expect(showSpy).toHaveBeenCalledWith(
      expect.stringContaining(String(COMPARISON_LIMIT)),
    )
  })

  it('shows toast when listing is added to comparison', async () => {
    const store = useComparisonsStore()
    const toast = useToastStore()
    const showSpy = vi.spyOn(toast, 'show')

    vi.mocked(comparisonsApi.toggleComparison).mockResolvedValue({
      active: true,
      item: { id: 10, userId: null, listingId: 5 },
    })

    await store.toggle(5, listingStub(5))

    expect(store.isCompared(5)).toBe(true)
    expect(showSpy).toHaveBeenCalledWith('Добавлено к сравнению')
  })

  it('shows toast when listing is removed from comparison via toggle', async () => {
    const store = useComparisonsStore()
    const toast = useToastStore()
    const showSpy = vi.spyOn(toast, 'show')

    vi.mocked(comparisonsApi.toggleComparison).mockResolvedValueOnce({
      active: true,
      item: { id: 10, userId: null, listingId: 5 },
    })
    await store.toggle(5, listingStub(5))
    showSpy.mockClear()

    vi.mocked(comparisonsApi.toggleComparison).mockResolvedValueOnce({
      active: false,
      item: null,
    })
    await store.toggle(5, listingStub(5))

    expect(store.isCompared(5)).toBe(false)
    expect(showSpy).toHaveBeenCalledWith('Удалено из сравнения')
  })

  it('shows toast when listing is removed via removeByListingId', async () => {
    const store = useComparisonsStore()
    const toast = useToastStore()
    const showSpy = vi.spyOn(toast, 'show')

    vi.mocked(comparisonsApi.toggleComparison).mockResolvedValueOnce({
      active: true,
      item: { id: 10, userId: null, listingId: 5 },
    })
    await store.toggle(5, listingStub(5))
    showSpy.mockClear()

    vi.mocked(comparisonsApi.removeComparison).mockResolvedValueOnce(undefined)
    await store.removeByListingId(5)

    expect(store.isCompared(5)).toBe(false)
    expect(showSpy).toHaveBeenCalledWith('Удалено из сравнения')
  })
})
