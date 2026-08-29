import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createListingReport } from '@/api/listingReports'

vi.mock('@/api/client', () => ({
  default: {
    post: vi.fn(),
  },
}))

import apiClient from '@/api/client'

describe('createListingReport', () => {
  beforeEach(() => {
    vi.mocked(apiClient.post).mockReset()
  })

  it('posts reason and trimmed comment', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({
      data: { id: 1, listingId: 5, reason: 'spam', status: 'new' },
    })

    await createListingReport(5, { reason: 'spam', comment: '  hello  ' })

    expect(apiClient.post).toHaveBeenCalledWith('/api/listings/5/reports', {
      reason: 'spam',
      comment: 'hello',
    })
  })
})
