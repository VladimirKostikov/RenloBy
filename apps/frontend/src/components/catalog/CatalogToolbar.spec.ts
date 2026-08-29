import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it, beforeEach, vi } from 'vitest'
import CatalogToolbar from '@/components/catalog/CatalogToolbar.vue'
import { i18n } from '@/modules/locale'
import { useListingsStore } from '@/stores/listings'

describe('CatalogToolbar', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders rent term buttons with icons', () => {
    const wrapper = mount(CatalogToolbar, {
      props: { dealType: 'rent' },
      global: { plugins: [i18n] },
    })

    const terms = wrapper.findAll('.catalog-toolbar__term')
    expect(terms).toHaveLength(2)
    expect(terms[0].text()).toContain('Посуточная')
    expect(terms[1].text()).toContain('Долгосрочная')
    expect(wrapper.findAll('.catalog-toolbar__term-icon')).toHaveLength(2)
  })

  it('switches rent term on click', async () => {
    const listings = useListingsStore()
    listings.search = vi.fn().mockResolvedValue(undefined)

    const wrapper = mount(CatalogToolbar, {
      props: { dealType: 'rent' },
      global: { plugins: [i18n] },
    })

    await wrapper.findAll('.catalog-toolbar__term')[0].trigger('click')
    await flushPromises()

    expect(listings.rentTerm).toBe('daily')
    expect(listings.search).toHaveBeenCalled()
  })

  it('renders sliding category indicator', async () => {
    const wrapper = mount(CatalogToolbar, {
      props: { dealType: 'sale' },
      global: { plugins: [i18n] },
    })
    await flushPromises()

    expect(wrapper.find('.catalog-toolbar__indicator--categories').exists()).toBe(true)
    expect(wrapper.find('.catalog-toolbar__tab--active').text()).toContain('Все')
  })

  it('keeps sliding indicator visible and active tabs transparent', () => {
    const source = readFileSync(resolve(__dirname, './CatalogToolbar.vue'), 'utf8')
    expect(source).toContain('transform 0.28s cubic-bezier')
    expect(source).toContain('translate3d')
    expect(source).toContain('.catalog-toolbar__tab--active')
    expect(source).toContain('background: transparent')
    expect(source).not.toContain('.catalog-toolbar__indicator {\n    display: none')
  })

  it('moves indicator style when category changes', async () => {
    const listings = useListingsStore()
    listings.applyCatalogCategory = vi.fn().mockImplementation(async (category) => {
      listings.catalogCategory = category
    })

    const wrapper = mount(CatalogToolbar, {
      props: { dealType: 'rent' },
      global: { plugins: [i18n] },
    })
    await flushPromises()

    const firstTab = wrapper.find('.catalog-toolbar__tab').element as HTMLElement
    const secondTab = wrapper.findAll('.catalog-toolbar__tab')[1].element as HTMLElement
    Object.defineProperty(firstTab, 'offsetLeft', { configurable: true, get: () => 0 })
    Object.defineProperty(firstTab, 'offsetWidth', { configurable: true, get: () => 64 })
    Object.defineProperty(secondTab, 'offsetLeft', { configurable: true, get: () => 72 })
    Object.defineProperty(secondTab, 'offsetWidth', { configurable: true, get: () => 88 })

    await wrapper.findAll('.catalog-toolbar__tab')[1].trigger('click')
    await flushPromises()
    await wrapper.vm.$nextTick()

    const style = wrapper.find('.catalog-toolbar__indicator--categories').attributes('style') ?? ''
    expect(wrapper.find('.catalog-toolbar__tab--active').text()).toContain('Квартиры')
    expect(style).toContain('translate3d(72px, 0, 0)')
    expect(style).toContain('width: 88px')
  })

  it('moves active category tab on click', async () => {
    const listings = useListingsStore()
    listings.applyCatalogCategory = vi.fn().mockImplementation(async (category) => {
      listings.catalogCategory = category
    })

    const wrapper = mount(CatalogToolbar, {
      props: { dealType: 'rent' },
      global: { plugins: [i18n] },
    })

    const tabs = wrapper.findAll('.catalog-toolbar__tab')
    const apartments = tabs.find((tab) => tab.text().includes('Квартиры'))
    expect(apartments).toBeTruthy()
    await apartments!.trigger('click')
    await flushPromises()

    expect(listings.applyCatalogCategory).toHaveBeenCalledWith('apartment')
    expect(wrapper.find('.catalog-toolbar__tab--active').text()).toContain('Квартиры')
  })

  it('switches sale and rent on commercial catalog', async () => {
    const listings = useListingsStore()
    listings.commercialCatalogActive = true
    listings.listingType = 'commercial'
    listings.dealType = 'sale'
    listings.search = vi.fn().mockResolvedValue(undefined)

    const wrapper = mount(CatalogToolbar, {
      props: { dealType: 'sale', commercialCatalog: true },
      global: { plugins: [i18n] },
    })

    const tabs = wrapper.findAll('.catalog-toolbar__tab')
    expect(tabs).toHaveLength(2)
    expect(tabs[0].text()).toContain('Продажа')
    expect(tabs[1].text()).toContain('Аренда')

    await tabs[1].trigger('click')
    await flushPromises()

    expect(listings.dealType).toBe('rent')
    expect(listings.listingType).toBe('commercial')
    expect(listings.search).toHaveBeenCalled()
  })
})
