import { afterEach, describe, expect, it, vi } from 'vitest'
import { reverseGeocode } from '@/lib/reverseGeocode'

describe('reverseGeocode', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('returns null for invalid coordinates', async () => {
    await expect(reverseGeocode(120, 10, 'ru')).resolves.toBeNull()
  })

  it('maps city, district and street from reverse geocode response', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({
        address: {
          state: 'Минская область',
          city: 'Минск',
          city_district: 'Центральный район',
          road: 'улица Немига',
          house_number: '5',
        },
      }),
    }))

    await expect(reverseGeocode(53.9, 27.56, 'ru')).resolves.toEqual({
      label: 'Минск',
      region: 'Минская область',
      city: 'Минск',
      district: 'Центральный район',
      street: 'улица Немига',
      house: '5',
      address: 'улица Немига, 5',
    })
  })

  it('returns null when request fails', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: false,
    }))

    await expect(reverseGeocode(53.9, 27.56, 'ru')).resolves.toBeNull()
  })
})
