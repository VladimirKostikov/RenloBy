import { afterEach, describe, expect, it, vi } from 'vitest'
import { forwardGeocode } from '@/lib/forwardGeocode'

describe('forwardGeocode', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('rejects short queries', async () => {
    await expect(forwardGeocode('аб', 'ru')).resolves.toBeNull()
  })

  it('maps nominatim search hit to coordinates', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true,
      json: async () => [{
        lat: '53.9023',
        lon: '27.5619',
        display_name: 'ул. Ленина, 10, Минск, Беларусь',
      }],
    }))

    await expect(forwardGeocode('Минск ул Ленина 10', 'ru')).resolves.toEqual({
      latitude: 53.9023,
      longitude: 27.5619,
      label: 'ул. Ленина, 10, Минск',
    })
  })

  it('rejects points outside Belarus', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true,
      json: async () => [{
        lat: '55.7558',
        lon: '37.6173',
        display_name: 'Москва',
      }],
    }))

    await expect(forwardGeocode('Москва', 'ru')).resolves.toBeNull()
  })
})
