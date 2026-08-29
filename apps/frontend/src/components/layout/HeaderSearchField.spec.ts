import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import HeaderSearchField, { SUGGEST_DEBOUNCE_MS } from '@/components/layout/HeaderSearchField.vue'
import { i18n } from '@/modules/locale'
import { useListingsStore } from '@/stores/listings'
import type { ListingDto } from '@/types'

const fetchListings = vi.fn()
const fetchAddressSuggestions = vi.fn()

vi.mock('@/api/listings', () => ({
  fetchListings: (...args: unknown[]) => fetchListings(...args),
  fetchListing: vi.fn(),
  fetchAddressSuggestions: (...args: unknown[]) => fetchAddressSuggestions(...args),
}))

const listingFixture = {
  id: 42,
  dealType: 'sale',
  listingType: 'apartment',
  status: 'published',
  price: 120000,
  pricePerSqm: 2000,
  rooms: 2,
  area: 60,
  floor: 3,
  totalFloors: 9,
  address: 'ул. Независимости, 10',
  latitude: 53.9,
  longitude: 27.5,
  metroMinutes: null,
  verified: false,
  aiGoodPrice: false,
  rentTerm: null,
  hasDeposit: false,
  utilitiesIncluded: false,
  noCommission: false,
  fromOwner: false,
  hasRenovation: false,
  views: 0,
  images: ['https://example.com/photo.jpg'],
  publishedAt: '2026-01-01T00:00:00Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: null,
} as ListingDto

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div />' } },
    { path: '/listings/:id', name: 'listing-detail', component: { template: '<div />' } },
  ],
})

describe('HeaderSearchField', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
    fetchListings.mockReset()
    fetchAddressSuggestions.mockReset()
    fetchListings.mockResolvedValue({
      items: [listingFixture],
      total: 1,
      page: 1,
      limit: 8,
    })
    fetchAddressSuggestions.mockResolvedValue([])
    await router.push('/')
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('shows preloader while typing and searches only after debounce', async () => {
    const listings = useListingsStore()
    const searchSpy = vi.spyOn(listings, 'search').mockResolvedValue()

    const wrapper = mount(HeaderSearchField, {
      global: { plugins: [createPinia(), i18n, router] },
      attachTo: document.body,
    })

    await wrapper.get('.header-search__input').setValue('не')
    expect(wrapper.find('.header-search__spinner').exists()).toBe(true)
    expect(fetchListings).not.toHaveBeenCalled()

    await wrapper.get('.header-search__input').setValue('незав')
    await vi.advanceTimersByTimeAsync(SUGGEST_DEBOUNCE_MS - 50)
    expect(fetchListings).not.toHaveBeenCalled()
    expect(wrapper.find('.header-search__spinner').exists()).toBe(true)

    await vi.advanceTimersByTimeAsync(50)
    await flushPromises()

    expect(searchSpy).not.toHaveBeenCalled()
    expect(fetchListings).toHaveBeenCalledTimes(1)
    expect(fetchListings).toHaveBeenCalledWith(
      expect.objectContaining({
        query: 'незав',
        limit: 8,
        page: 1,
      }),
    )
    expect(document.body.querySelector('.header-search__suggest')).not.toBeNull()
    expect(document.body.textContent).toContain('ул. Независимости, 10')
    expect(document.body.querySelector('.header-search__suggest-photo')).not.toBeNull()
    expect(wrapper.find('.header-search__spinner').exists()).toBe(false)

    wrapper.unmount()
  })

  it('resets debounce when typing continues', async () => {
    const wrapper = mount(HeaderSearchField, {
      global: { plugins: [createPinia(), i18n, router] },
      attachTo: document.body,
    })

    await wrapper.get('.header-search__input').setValue('неа')
    await vi.advanceTimersByTimeAsync(800)
    await wrapper.get('.header-search__input').setValue('незав')
    await vi.advanceTimersByTimeAsync(800)
    expect(fetchListings).not.toHaveBeenCalled()

    await vi.advanceTimersByTimeAsync(200)
    await flushPromises()

    expect(fetchListings).toHaveBeenCalledTimes(1)
    expect(fetchListings).toHaveBeenCalledWith(
      expect.objectContaining({ query: 'незав' }),
    )

    wrapper.unmount()
  })

  it('opens listing after selecting a suggestion', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    const openDetail = vi.spyOn(listings, 'openDetailListing').mockResolvedValue()
    const focusOnMap = vi.spyOn(listings, 'focusListingOnMap')
    const pushSpy = vi.spyOn(router, 'push')

    const wrapper = mount(HeaderSearchField, {
      global: { plugins: [pinia, i18n, router] },
      attachTo: document.body,
    })

    await wrapper.get('.header-search__input').setValue('незав')
    await vi.advanceTimersByTimeAsync(SUGGEST_DEBOUNCE_MS)
    await flushPromises()

    const option = document.body.querySelector('.header-search__suggest-item') as HTMLButtonElement
    expect(option).toBeTruthy()
    option.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
    await flushPromises()
    await vi.advanceTimersByTimeAsync(SUGGEST_DEBOUNCE_MS)
    await flushPromises()

    expect(openDetail).toHaveBeenCalledWith(42)
    expect(focusOnMap).toHaveBeenCalledWith(42)
    expect(pushSpy).toHaveBeenCalled()
    expect(document.body.querySelector('.header-search__suggest')).toBeNull()

    wrapper.unmount()
  })

  it('resolves region on submit and updates filters', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    listings.cities = [
      { id: 2, name: 'Гомель', slug: 'gomel-city', regionSlug: 'gomel' },
    ]
    const searchSpy = vi.spyOn(listings, 'search').mockResolvedValue()

    const wrapper = mount(HeaderSearchField, {
      global: { plugins: [pinia, i18n, router] },
      attachTo: document.body,
    })

    await wrapper.get('.header-search__input').setValue('Гомельская область')
    await wrapper.get('.header-search__input').trigger('keydown.enter')
    await flushPromises()

    expect(listings.regionSlug).toBe('gomel')
    expect(listings.cityId).toBeUndefined()
    expect(listings.searchQuery).toBe('')
    expect(searchSpy).toHaveBeenCalled()

    wrapper.unmount()
  })
})
