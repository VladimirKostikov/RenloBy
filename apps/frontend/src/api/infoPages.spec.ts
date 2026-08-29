import { describe, expect, it, vi, beforeEach } from 'vitest'
import * as infoPagesApi from '@/api/infoPages'

vi.mock('@/api/infoPages', () => ({
  fetchInfoPages: vi.fn(),
  fetchInfoPage: vi.fn(),
}))

describe('infoPages api', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('fetches info pages list', async () => {
    vi.mocked(infoPagesApi.fetchInfoPages).mockResolvedValue([
      {
        id: 1,
        slug: 'deal-safety',
        title: 'Deal safety',
        body: 'Body',
        category: 'deal_safety',
        importantNote: null,
        faqItems: [],
        sortOrder: 40,
        updatedAt: '2025-05-20',
      },
    ])

    const pages = await infoPagesApi.fetchInfoPages()
    expect(pages).toHaveLength(1)
    expect(pages[0].slug).toBe('deal-safety')
  })
})
