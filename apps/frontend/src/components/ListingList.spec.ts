import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { describe, expect, it, vi, beforeEach } from 'vitest'
import ListingList from '@/components/ListingList.vue'
import { i18n } from '@/modules/locale'
import { useListingsStore } from '@/stores/listings'

const listingFixture = {
  id: 1,
  dealType: 'sale' as const,
  listingType: 'apartment' as const,
  status: 'published' as const,
  price: 100000,
  pricePerSqm: 2000,
  rooms: 2,
  area: 50,
  floor: 3,
  totalFloors: 9,
  address: 'Test address',
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
  images: [],
  publishedAt: '2026-01-01T00:00:00Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: null,
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div />' } },
    { path: '/search', name: 'search-map', component: { template: '<div />' } },
    { path: '/listings/:id', name: 'listing-detail', component: { template: '<div />' } },
  ],
})

describe('ListingList', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    vi.stubGlobal('location', { assign: vi.fn() })
    await router.push('/')
  })

  it('navigates to search map when show more mode is navigate', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    listings.items = [listingFixture]
    listings.total = 10

    const wrapper = mount(ListingList, {
      global: {
        plugins: [pinia, i18n, router],
      },
      props: {
        showMoreMode: 'navigate',
        previewLimit: 5,
      },
    })

    await wrapper.get('.listing-panel__more').trigger('click')

    expect(window.location.assign).toHaveBeenCalledWith('/search?panel=extended')
  })

  it('opens listing in modal without full page navigation', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    listings.items = [listingFixture]
    listings.total = 1
    const openDetail = vi.spyOn(listings, 'openDetailListing').mockResolvedValue()
    const pushSpy = vi.spyOn(router, 'push')

    const wrapper = mount(ListingList, {
      global: {
        plugins: [pinia, i18n, router],
        stubs: {
          ListingCard: true,
        },
      },
    })

    await wrapper.get('.listing-panel__item').trigger('click')

    expect(openDetail).toHaveBeenCalledWith(1)
    expect(pushSpy).toHaveBeenCalled()
    expect(window.location.assign).not.toHaveBeenCalled()
  })

  it('shows empty message when there are no listings', () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    listings.items = []
    listings.total = 0

    const wrapper = mount(ListingList, {
      global: {
        plugins: [pinia, i18n, router],
      },
    })

    expect(wrapper.get('.listing-panel__state').text()).toBe('Предложений нет')
  })

  it('shows at most previewLimit items', () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    listings.items = [
      listingFixture,
      { ...listingFixture, id: 2, address: 'Second' },
      { ...listingFixture, id: 3, address: 'Third' },
    ]
    listings.total = 3

    const wrapper = mount(ListingList, {
      global: {
        plugins: [pinia, i18n, router],
        stubs: { ListingCard: true },
      },
      props: {
        previewLimit: 2,
      },
    })

    expect(wrapper.findAll('.listing-panel__item')).toHaveLength(2)
  })

  it('animates list reorder when sort changes', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    listings.items = [
      listingFixture,
      { ...listingFixture, id: 2, price: 50000, address: 'Cheap' },
    ]
    listings.total = 2
    listings.sort = 'newest'
    const search = vi.spyOn(listings, 'search').mockImplementation(async () => {
      listings.items = [
        { ...listingFixture, id: 2, price: 50000, address: 'Cheap' },
        listingFixture,
      ]
    })

    const wrapper = mount(ListingList, {
      global: {
        plugins: [pinia, i18n, router],
        stubs: {
          ListingCard: true,
          FilterSelect: {
            props: ['modelValue', 'options'],
            emits: ['update:modelValue'],
            template:
              '<button class="sort-stub" @click="$emit(\'update:modelValue\', \'priceAsc\')">sort</button>',
          },
        },
      },
    })

    expect(wrapper.find('.listing-panel__list-track').exists()).toBe(true)
    await wrapper.get('.sort-stub').trigger('click')
    await wrapper.vm.$nextTick()

    expect(search).toHaveBeenCalled()
    expect(wrapper.find('.listing-panel__list--sorting').exists()).toBe(true)
  })
})
