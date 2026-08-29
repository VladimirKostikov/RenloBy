import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAiAssistantStore } from '@/stores/aiAssistant'

vi.mock('@/api/aiPreferences', () => ({
  createAiPreference: vi.fn().mockResolvedValue({
    id: 10,
    userId: null,
    guestSessionHash: 'abc',
    answers: { dealType: 'rent' },
    filters: { dealType: 'rent' },
    recommendedListingIds: [1, 2, 3, 4, 5],
    summary: 'Test summary',
    highlights: ['A', 'B'],
    listings: [{ id: 1 }, { id: 2 }, { id: 3 }, { id: 4 }, { id: 5 }],
    isTest: false,
    createdAt: '2026-07-16T00:00:00+00:00',
    updatedAt: '2026-07-16T00:00:00+00:00',
  }),
  fetchLatestAiPreference: vi.fn().mockResolvedValue(null),
}))

describe('useAiAssistantStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('limits visible recommended ids', async () => {
    const store = useAiAssistantStore()
    await store.submitAnswers({ dealType: 'rent', budgetMax: 500, priorities: [] })

    expect(store.isRecommended(1)).toBe(true)
    expect(store.isRecommended(4)).toBe(true)
    expect(store.isRecommended(5)).toBe(false)
    expect(store.recommendedListings).toHaveLength(4)
  })
})
