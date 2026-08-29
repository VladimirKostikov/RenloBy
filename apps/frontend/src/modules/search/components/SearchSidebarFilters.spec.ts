import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import SearchSidebarFilters from '@/modules/search/components/SearchSidebarFilters.vue'
import ru from '@/locales/ru.json'
import { useListingsStore } from '@/stores/listings'

vi.mock('@/api/listings', () => ({
  fetchListings: vi.fn().mockResolvedValue({ items: [], total: 0, page: 1, limit: 20 }),
  fetchListing: vi.fn(),
}))

vi.mock('@/api/reference', () => ({
  fetchCities: vi.fn().mockResolvedValue([]),
  fetchDistricts: vi.fn().mockResolvedValue([]),
  fetchMetroStations: vi.fn().mockResolvedValue([]),
}))

describe('SearchSidebarFilters', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('lets user switch between sale and rent', async () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })
    const listings = useListingsStore()
    listings.dealType = 'sale'
    const searchSpy = vi.spyOn(listings, 'search').mockResolvedValue()

    const wrapper = mount(SearchSidebarFilters, {
      global: { plugins: [i18n] },
    })

    const tabs = wrapper.findAll('.search-sidebar__deal-type')
    expect(tabs.length).toBeGreaterThanOrEqual(2)
    expect(tabs[0].text()).toBe('Продажа')
    expect(tabs[1].text()).toBe('Аренда')
    expect(tabs[0].classes()).toContain('search-sidebar__deal-type--active')

    await tabs[1].trigger('click')
    await flushPromises()

    expect(listings.dealType).toBe('rent')
    expect(searchSpy).toHaveBeenCalled()
    expect(wrapper.find('.search-sidebar__label').text()).toContain('Тип сделки')
    expect(wrapper.text()).toContain('Цена в месяц')
    expect(wrapper.text()).toContain('Бизнес')
  })
})
