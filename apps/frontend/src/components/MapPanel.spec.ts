import { computed, ref } from 'vue'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { describe, expect, it, vi } from 'vitest'
import MapPanel from '@/components/MapPanel.vue'
import { i18n } from '@/modules/locale'
import { useListingsStore } from '@/stores/listings'

vi.mock('@/modules/map/composables/useMapPanel', () => ({
  useMapPanel: () => ({
    isMapLoading: ref(false),
    mapLoadError: ref(false),
    viewState: ref({ mode: 'cities', regionSlug: 'gomel', citySlug: null }),
    tooltip: ref(null),
    popupPosition: ref(null),
    popupCardRef: ref(null),
    listingCardLoading: ref(false),
    selectedListing: computed(() => null),
    breadcrumb: computed(() => 'Гомельская область'),
    goBack: vi.fn(),
    closeListingPopup: vi.fn(),
    openListingDetail: vi.fn(),
    getMetroStation: vi.fn(),
    getDistrictLabel: vi.fn(),
  }),
}))

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div />' } },
    { path: '/region/:regionSlug', name: 'region-location', component: { template: '<div />' } },
  ],
})

describe('MapPanel region page link', () => {
  it('shows region page link when region is selected', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const listings = useListingsStore()
    listings.regionSlug = 'gomel'

    await router.push('/')
    const wrapper = mount(MapPanel, {
      global: {
        plugins: [pinia, i18n, router],
        stubs: {
          MapZoneTooltip: true,
          MapListingCard: true,
        },
      },
    })

    const link = wrapper.find('.map-panel__region-link')
    expect(link.exists()).toBe(true)
    expect(link.attributes('href')).toBe('/region/gomel')
    expect(link.text()).toContain('Страница региона')
  })
})
