import { beforeEach, describe, expect, it, vi } from 'vitest'
import { fetchSellerListings, fetchSellerProfile } from '@/api/sellers'

vi.mock('./client', () => ({
  default: {
    get: vi.fn(),
  },
}))

import apiClient from './client'

describe('sellers api', () => {
  beforeEach(() => {
    vi.mocked(apiClient.get).mockReset()
  })

  it('fetches seller profile', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: {
        id: 7,
        name: 'Иван',
        photo: null,
        phone: null,
        instagram: null,
        telegram: null,
        whatsapp: null,
        viber: null,
        lastSeenAt: null,
        registeredAt: '2025-03-12T10:00:00.000Z',
        listingsCount: 2,
      },
    })

    await fetchSellerProfile(7)
    expect(apiClient.get).toHaveBeenCalledWith('/api/sellers/7')
  })

  it('fetches seller listings', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { items: [], total: 0, page: 1, limit: 12 },
    })

    await fetchSellerListings(7, { page: 1, limit: 12 })
    expect(apiClient.get).toHaveBeenCalledWith('/api/sellers/7/listings', {
      params: { page: 1, limit: 12 },
    })
  })
})
