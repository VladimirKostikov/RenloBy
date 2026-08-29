import { createPinia, setActivePinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import MapAddressSearch from '@/components/MapAddressSearch.vue'
import ru from '@/locales/ru.json'

vi.mock('@/api/listings', () => ({
  fetchAddressSuggestions: vi.fn().mockResolvedValue([]),
}))

vi.mock('@/lib/forwardGeocode', () => ({
  forwardGeocode: vi.fn().mockResolvedValue(null),
}))

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: { ru },
})

describe('MapAddressSearch', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders address search field on map', () => {
    const wrapper = mount(MapAddressSearch, {
      global: { plugins: [i18n] },
    })

    const input = wrapper.find('input[type="search"]')
    expect(input.exists()).toBe(true)
    expect(input.attributes('placeholder')).toBe('Область, город, район или адрес')
  })
})
