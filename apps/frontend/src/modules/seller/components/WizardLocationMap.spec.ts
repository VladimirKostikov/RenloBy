import { mount, flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import WizardLocationMap from '@/modules/seller/components/WizardLocationMap.vue'
import { getDefaultZoom, getMapCenter } from '@/lib/mapConfig'
import ru from '@/locales/ru.json'

const createYandexMap = vi.fn().mockRejectedValue(new Error('no-map'))
const fitMapToBounds = vi.fn()
const getBelarusBoundsPoints = vi.fn().mockReturnValue([
  [51.2, 23.0],
  [51.2, 32.8],
  [56.3, 32.8],
  [56.3, 23.0],
])
const forwardGeocode = vi.fn()

vi.mock('@/lib/mapConfig', async () => {
  const actual = await vi.importActual<typeof import('@/lib/mapConfig')>('@/lib/mapConfig')
  return {
    ...actual,
    createYandexMap: (...args: unknown[]) => createYandexMap(...args),
    fitMapToBounds: (...args: unknown[]) => fitMapToBounds(...args),
    getBelarusBoundsPoints: (...args: unknown[]) => getBelarusBoundsPoints(...args),
  }
})

vi.mock('@/lib/forwardGeocode', () => ({
  forwardGeocode: (...args: unknown[]) => forwardGeocode(...args),
}))

describe('WizardLocationMap', () => {
  beforeEach(() => {
    createYandexMap.mockReset()
    createYandexMap.mockRejectedValue(new Error('no-map'))
    fitMapToBounds.mockReset()
    forwardGeocode.mockReset()
  })

  it('shows fallback when map cannot be created', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardLocationMap, {
      props: { latitude: 53.9, longitude: 27.5 },
      global: { plugins: [i18n] },
    })

    await vi.waitFor(() => {
      expect(wrapper.find('.wizard-location-map__fallback').exists()).toBe(true)
    })
    expect(wrapper.find('.wizard-location-map__search-input').exists()).toBe(true)
    expect(wrapper.text()).toContain('Моё местоположение')
  })

  it('opens map on Belarus overview instead of city zoom', async () => {
    const map = {
      events: { add: vi.fn(), remove: vi.fn() },
      geoObjects: { add: vi.fn() },
      destroy: vi.fn(),
      setCenter: vi.fn(),
      getZoom: vi.fn().mockReturnValue(6),
      setZoom: vi.fn(),
      setBounds: vi.fn(),
    }
    createYandexMap.mockResolvedValueOnce(map)
    ;(globalThis as { ymaps?: unknown }).ymaps = {
      Placemark: vi.fn().mockImplementation(() => ({
        events: { add: vi.fn() },
        geometry: { setCoordinates: vi.fn(), getCoordinates: vi.fn() },
      })),
    }

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    mount(WizardLocationMap, {
      props: { latitude: 53.9, longitude: 27.5 },
      global: { plugins: [i18n] },
    })

    await vi.waitFor(() => {
      expect(createYandexMap).toHaveBeenCalled()
    })

    expect(createYandexMap.mock.calls[0]?.[1]).toBeUndefined()
    expect(createYandexMap.mock.calls[0]?.[2]).toBeUndefined()
    expect(fitMapToBounds).toHaveBeenCalledWith(map, getBelarusBoundsPoints(), 7, 0)
    expect(getMapCenter()).toEqual([53.75, 27.9])
    expect(getDefaultZoom()).toBe(6)
  })

  it('places marker from typed address even without map canvas', async () => {
    forwardGeocode.mockResolvedValue({
      latitude: 53.9023,
      longitude: 27.5619,
      label: 'ул. Ленина, 10, Минск',
    })

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardLocationMap, {
      props: { latitude: 53.75, longitude: 27.9 },
      global: { plugins: [i18n] },
    })

    await flushPromises()
    await wrapper.get('.wizard-location-map__search-input').setValue('Минск ул Ленина 10')
    await wrapper.get('.wizard-location-map__search').trigger('submit')
    await flushPromises()

    expect(forwardGeocode).toHaveBeenCalledWith('Минск ул Ленина 10', 'ru')
    expect(wrapper.emitted('update:coords')?.[0]).toEqual([53.9023, 27.5619])
    expect((wrapper.get('.wizard-location-map__search-input').element as HTMLInputElement).value)
      .toContain('Ленина')
  })

  it('shows error when address is not found', async () => {
    forwardGeocode.mockResolvedValue(null)

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardLocationMap, {
      props: { latitude: 53.75, longitude: 27.9 },
      global: { plugins: [i18n] },
    })

    await flushPromises()
    await wrapper.get('.wizard-location-map__search-input').setValue('несуществующий адрес 999')
    await wrapper.get('.wizard-location-map__search').trigger('submit')
    await flushPromises()

    expect(wrapper.find('.wizard-location-map__search-error').exists()).toBe(true)
    expect(wrapper.emitted('update:coords')).toBeUndefined()
  })
})
