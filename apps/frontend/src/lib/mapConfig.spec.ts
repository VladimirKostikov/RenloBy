import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  getBelarusBoundsPoints,
  getCityCenter,
  getDefaultZoom,
  getFitZoomMargin,
  getMapCenter,
  getYandexMapsApiKey,
} from '@/lib/mapConfig'

describe('mapConfig', () => {
  afterEach(() => {
    vi.unstubAllEnvs()
  })

  it('requires Yandex API key', () => {
    vi.stubEnv('VITE_YANDEX_MAPS_API_KEY', '')
    expect(() => getYandexMapsApiKey()).toThrow('yandex_maps_api_key_missing')
  })

  it('returns trimmed Yandex API key', () => {
    vi.stubEnv('VITE_YANDEX_MAPS_API_KEY', '  test-key  ')
    expect(getYandexMapsApiKey()).toBe('test-key')
  })

  it('returns default map center for Belarus overview', () => {
    expect(getMapCenter()).toEqual([53.75, 27.9])
    expect(getDefaultZoom()).toBe(6)
  })

  it('returns Belarus bounds corners', () => {
    expect(getBelarusBoundsPoints()).toHaveLength(4)
    expect(getBelarusBoundsPoints()[0]).toEqual([51.2, 23.0])
    expect(getBelarusBoundsPoints()[2]).toEqual([56.3, 32.8])
  })

  it('returns city center by slug', () => {
    expect(getCityCenter('mogilev-city')).toEqual([53.8945, 30.3307])
    expect(getCityCenter('unknown')).toBeNull()
  })

  it('uses tighter zoom margin on narrow map containers', () => {
    expect(getFitZoomMargin(360)).toBe(18)
    expect(getFitZoomMargin(1280)).toBe(30)
  })
})
