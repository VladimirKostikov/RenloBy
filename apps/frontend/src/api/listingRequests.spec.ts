import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createListingRequest } from '@/api/listingRequests'

vi.mock('./client', () => ({
  default: {
    post: vi.fn(),
  },
}))

import apiClient from './client'

describe('createListingRequest', () => {
  beforeEach(() => {
    vi.mocked(apiClient.post).mockReset()
    vi.mocked(apiClient.post).mockResolvedValue({
      data: {
        id: 1,
        listingId: 5,
        name: null,
        phone: '+375291112233',
        message: 'Хочу посмотреть',
        status: 'new',
        createdAt: '2026-07-16T00:00:00+00:00',
        isTest: false,
      },
    })
  })

  it('posts trimmed payload', async () => {
    await createListingRequest(5, {
      phone: '  +375291112233  ',
      message: '  Хочу посмотреть  ',
      name: '  Анна  ',
    })

    expect(apiClient.post).toHaveBeenCalledWith('/api/listings/5/requests', {
      phone: '+375291112233',
      message: 'Хочу посмотреть',
      name: 'Анна',
    })
  })
})
