import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { createI18n } from 'vue-i18n'
import ListingWizardPanel from '@/modules/seller/components/ListingWizardPanel.vue'
import { WIZARD_DRAFT_STORAGE_KEY } from '@/modules/seller/lib/listingWizard'
import { scrollElementBelowStickyHeader } from '@/lib/scrollBelowStickyHeader'
import ru from '@/locales/ru.json'

vi.mock('@/api/account', () => ({
  createMyListing: vi.fn().mockResolvedValue({ id: 10, status: 'draft' }),
}))

vi.mock('@/api/exchangeRates', () => ({
  fetchExchangeRates: vi.fn().mockResolvedValue({
    usdToByn: 3.27,
    usdToRub: 93,
    source: 'nbrb',
    updatedAt: '2026-07-16T00:00:00+00:00',
  }),
}))

vi.mock('@/api/reference', () => ({
  fetchCities: vi.fn().mockResolvedValue([{ id: 1, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' }]),
  fetchDistricts: vi.fn().mockResolvedValue([{ id: 2, name: 'Центр', cityId: 1 }]),
  fetchMetroStations: vi.fn().mockResolvedValue([
    { id: 3, name: 'Немига', cityId: 1, lineColor: '#0072BC' },
    { id: 4, name: 'Малиновка', cityId: 1, lineColor: '#009A49' },
  ]),
}))

vi.mock('@/modules/seller/components/WizardLocationMap.vue', () => ({
  default: {
    name: 'WizardLocationMap',
    props: ['latitude', 'longitude'],
    emits: ['update:coords'],
    template: '<div class="wizard-location-map-stub" />',
  },
}))

vi.mock('@/lib/reverseGeocode', () => ({
  reverseGeocode: vi.fn().mockResolvedValue({
    label: 'Минск',
    region: 'Минск',
    city: 'Минск',
    district: 'Центр',
    street: 'ул. Ленина',
    house: '10',
    address: 'ул. Ленина, 10',
  }),
}))

vi.mock('@/lib/scrollBelowStickyHeader', () => ({
  scrollElementBelowStickyHeader: vi.fn(),
}))

describe('ListingWizardPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('renders seven steps and validates merged location step', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/seller/create', component: { template: '<div />' } }],
    })
    await router.push('/account/seller/create')
    await router.isReady()

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const pinia = createPinia()
    const wrapper = mount(ListingWizardPanel, {
      global: { plugins: [i18n, router, pinia] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Подача объявления')
    expect(wrapper.find('.listing-wizard__deal-step').exists()).toBe(true)
    expect(wrapper.find('.listing-wizard__panel-title--deal').exists()).toBe(true)
    expect(wrapper.find('.listing-wizard__deal-tiles--deal').exists()).toBe(true)
    expect(wrapper.find('.listing-wizard__save-exit').exists()).toBe(true)
    expect(wrapper.get('.listing-wizard__save-exit').text()).toContain('Сохранить и выйти')
    expect(wrapper.find('.listing-wizard__save-exit-icon').exists()).toBe(true)
    expect(wrapper.findAll('.listing-wizard__deal-tile')).toHaveLength(3)
    expect(wrapper.find('.listing-wizard__deal-tile--active').text()).toContain('Продажа')
    expect(wrapper.findAll('.listing-wizard__chain-num')).toHaveLength(7)
    expect(wrapper.get('.listing-wizard__btn--next').text()).toContain('Далее')
    expect(wrapper.classes()).toContain('listing-wizard')
    expect(wrapper.find('.listing-wizard__layout').exists()).toBe(true)
    expect(wrapper.find('.listing-wizard__main').exists()).toBe(true)
    expect(wrapper.find('.listing-wizard__panel').exists()).toBe(true)
    expect(wrapper.find('.listing-wizard__stage--next').exists()).toBe(true)

    await wrapper.get('.listing-wizard__btn--next').trigger('click')
    await flushPromises()
    expect(wrapper.find('.listing-wizard__chain-item--active').text()).toContain('Объект')
    expect(scrollElementBelowStickyHeader).toHaveBeenCalled()
    expect(wrapper.findAll('.listing-wizard__deal-tile')).toHaveLength(3)
    expect(wrapper.find('.listing-wizard__deal-tile--active').text()).toContain('Квартира')
    expect(wrapper.find('.listing-wizard__stage--next').exists()).toBe(true)

    const actionButtons = wrapper.findAll('.listing-wizard__actions .listing-wizard__btn')
    expect(actionButtons).toHaveLength(2)
    expect(actionButtons[0].classes()).toContain('listing-wizard__btn--next')
    expect(actionButtons[1].classes()).toContain('listing-wizard__btn--back')

    await wrapper.get('.listing-wizard__btn--back').trigger('click')
    await flushPromises()
    expect(wrapper.find('.listing-wizard__chain-item--active').text()).toContain('Сделка')
    expect(wrapper.find('.listing-wizard__stage--prev').exists()).toBe(true)
    expect(scrollElementBelowStickyHeader).toHaveBeenCalledTimes(2)

    await wrapper.get('.listing-wizard__btn--next').trigger('click')
    await flushPromises()
    expect(wrapper.find('.listing-wizard__chain-item--active').text()).toContain('Объект')

    await wrapper.get('.listing-wizard__btn--next').trigger('click')
    await flushPromises()
    expect(wrapper.find('.listing-wizard__chain-item--active').text()).toContain('Локация')
    expect(wrapper.find('.wizard-location-map-stub').exists()).toBe(true)
    expect(wrapper.text()).toContain('Отсутствует')
    expect(wrapper.findAll('.wizard-location-field').length).toBeGreaterThanOrEqual(8)

    await wrapper.get('.listing-wizard__btn--next').trigger('click')
    await flushPromises()
    expect(wrapper.find('.wizard-location-field__control.is-invalid').exists()).toBe(true)
    expect(localStorage.getItem(WIZARD_DRAFT_STORAGE_KEY)).toBeTruthy()

    window.confirm = vi.fn(() => true)
    await wrapper.get('.listing-wizard__clear').trigger('click')
    await flushPromises()
    expect(localStorage.getItem(WIZARD_DRAFT_STORAGE_KEY)).toBeNull()
    expect(wrapper.find('.listing-wizard__chain-item--active').text()).toContain('Сделка')
  })

  it('saves local draft and exits wizard from header action', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/create', component: { template: '<div />' } },
        { path: '/account/seller/listings', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/create')
    await router.isReady()

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingWizardPanel, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    await wrapper.get('.listing-wizard__btn--next').trigger('click')
    await flushPromises()
    expect(wrapper.find('.listing-wizard__chain-item--active').text()).toContain('Объект')

    await wrapper.get('.listing-wizard__save-exit').trigger('click')
    await flushPromises()

    expect(localStorage.getItem(WIZARD_DRAFT_STORAGE_KEY)).toBeTruthy()
    expect(router.currentRoute.value.path).toBe('/account/seller/listings')
  })

  it('shows rent term as tiles on details step', async () => {
    localStorage.setItem(WIZARD_DRAFT_STORAGE_KEY, JSON.stringify({
      stepIndex: 3,
      draft: {
        dealType: 'rent',
        listingType: 'apartment',
        region: '',
        city: 'Минск',
        district: 'Центр',
        metro: '',
        metroLineColor: '#0072BC',
        street: 'ул. Ленина',
        house: '10',
        entrance: '',
        apartmentNumber: '',
        latitude: 53.9,
        longitude: 27.5,
        metroMinutes: null,
        rooms: 2,
        area: 50,
        floor: 3,
        totalFloors: 9,
        price: null,
        priceByn: null,
        rentTerm: 'long',
        hasDeposit: false,
        utilitiesIncluded: false,
        noCommission: false,
        fromOwner: true,
        hasRenovation: false,
        priceNegotiable: false,
        images: [],
      },
    }))

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/seller/create', component: { template: '<div />' } }],
    })
    await router.push('/account/seller/create')
    await router.isReady()

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingWizardPanel, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    expect(wrapper.find('.listing-wizard__chain-item--active').text()).toContain('Параметры')
    expect(wrapper.text()).toContain('Срок аренды')
    expect(wrapper.text()).toContain('Кто вы')
    expect(wrapper.find('[data-testid="seller-role-tiles"]').exists()).toBe(true)
    expect(wrapper.find('.listing-wizard__deal-tiles--two').exists()).toBe(true)
    const rentTermTiles = wrapper.findAll('.listing-wizard__deal-tiles--two .listing-wizard__deal-tile')
    expect(rentTermTiles.length).toBeGreaterThanOrEqual(2)
    expect(rentTermTiles[0].text()).toContain('Длительно')
    expect(rentTermTiles[1].text()).toContain('Посуточно')
    expect(rentTermTiles[0].classes()).toContain('listing-wizard__deal-tile--active')

    await rentTermTiles[1].trigger('click')
    await flushPromises()
    expect(rentTermTiles[1].classes()).toContain('listing-wizard__deal-tile--active')

    const roleTiles = wrapper.findAll('[data-testid="seller-role-tiles"] .listing-wizard__deal-tile')
    expect(roleTiles).toHaveLength(2)
    expect(roleTiles[0].text()).toContain('Собственник')
    expect(roleTiles[1].text()).toContain('Агент')
    await roleTiles[1].trigger('click')
    await flushPromises()
    expect(roleTiles[1].classes()).toContain('listing-wizard__deal-tile--active')
  })
})
