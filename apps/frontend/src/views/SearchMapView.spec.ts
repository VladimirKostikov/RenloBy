import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import SearchMapView from '@/views/SearchMapView.vue'
import { i18n } from '@/modules/locale'
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

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: vi.fn(),
}))

vi.mock('@/modules/seo/components/SeoPageHeading.vue', () => ({
  default: {
    name: 'SeoPageHeading',
    props: ['title'],
    template: '<h1 class="seo-heading-stub">{{ title }}</h1>',
  },
}))

vi.mock('@/components/MapPanel.vue', () => ({
  default: {
    name: 'MapPanel',
    template: '<aside class="map-panel-stub" />',
  },
}))

vi.mock('@/components/ListingList.vue', () => ({
  default: {
    name: 'ListingList',
    template: '<section class="listing-panel-stub" />',
  },
}))

vi.mock('@/components/FilterBar.vue', () => ({
  default: {
    name: 'FilterBar',
    template: '<div class="filter-bar-stub" />',
  },
}))

vi.mock('@/modules/search/components/SearchSidebarFilters.vue', () => ({
  default: {
    name: 'SearchSidebarFilters',
    template: '<aside class="search-sidebar-stub" />',
  },
}))

describe('SearchMapView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  async function mountView() {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/search', name: 'search-map', component: SearchMapView },
        {
          path: '/search/listings/:id',
          name: 'search-listing-detail',
          component: SearchMapView,
        },
      ],
    })

    await router.push('/search')
    await router.isReady()

    const listings = useListingsStore()
    listings.cities = [{ id: 1, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' }]

    return mount(SearchMapView, {
      global: {
        plugins: [i18n, router],
      },
    })
  }

  it('renders list/map switch for mobile panels', async () => {
    const wrapper = await mountView()
    await flushPromises()

    expect(wrapper.find('.search-map__mobile-switch').exists()).toBe(true)
    expect(wrapper.find('.search-map__filters-open').exists()).toBe(true)
    expect(wrapper.text()).toContain(i18n.global.t('searchMap.panelList'))
    expect(wrapper.text()).toContain(i18n.global.t('searchMap.panelMap'))
    expect(wrapper.text()).toContain(i18n.global.t('filters.title'))
  })

  it('opens vertical filters from mobile toolbar button', async () => {
    const wrapper = await mountView()
    await flushPromises()

    await wrapper.find('.search-map__filters-open').trigger('click')
    await flushPromises()

    expect(wrapper.find('.search-sidebar-stub').exists()).toBe(true)
    expect(wrapper.vm.$router.currentRoute.value.query.panel).toBe('extended')
  })

  it('closes filters without flashing a second panel state', async () => {
    const wrapper = await mountView()
    await flushPromises()

    await wrapper.find('.search-map__filters-open').trigger('click')
    await flushPromises()
    expect(wrapper.find('.search-map__filters').exists()).toBe(true)

    await wrapper.find('.search-map__filters-close').trigger('click')
    await flushPromises()

    expect(wrapper.find('.search-sidebar-stub').exists()).toBe(false)
    expect(wrapper.vm.$router.currentRoute.value.query.panel).toBeUndefined()
    expect(wrapper.find('.filter-bar-stub').exists()).toBe(true)
  })

  it('keeps mobile drawer styles on filters panel itself', () => {
    const source = readFileSync(resolve(__dirname, './SearchMapView.vue'), 'utf8')
    expect(source).not.toContain('search-map__filters--mobile')
    expect(source).toContain('@media (max-width: 1279px)')
    expect(source).toContain('position: fixed')
    expect(source).toContain('.search-map__filters {')
  })

  it('hides horizontal filter bar styles on mobile breakpoint', () => {
    const source = readFileSync(resolve(__dirname, './SearchMapView.vue'), 'utf8')
    expect(source).toContain('@media (max-width: 767px)')
    expect(source).toContain('.search-map :deep(.filter-bar)')
    expect(source).toContain('display: none')
    expect(source).toContain('.search-map__filters-open')
  })

  it('switches content mode to map panel', async () => {
    const wrapper = await mountView()
    await flushPromises()

    const buttons = wrapper.findAll('.search-map__mobile-switch-btn')
    await buttons[1].trigger('click')

    expect(wrapper.find('.search-map__content--map').exists()).toBe(true)
    expect(wrapper.find('.search-map__content--list').exists()).toBe(false)
  })
})
