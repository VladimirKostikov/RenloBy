import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import HeaderTopBar from '@/components/layout/HeaderTopBar.vue'
import { i18n } from '@/modules/locale'
import { resetUserLocationBootstrap } from '@/modules/location/composables/useUserLocation'
import { useAuthStore } from '@/stores/auth'
import { useListingsStore } from '@/stores/listings'

vi.mock('@/api/reference', () => ({
  fetchCities: vi.fn().mockResolvedValue([
    { id: 1, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' },
    { id: 2, name: 'Брест', slug: 'brest-city', regionSlug: 'brest' },
  ]),
  fetchDistricts: vi.fn().mockResolvedValue([]),
  fetchMetroStations: vi.fn().mockResolvedValue([]),
}))

vi.mock('@/api/listings', () => ({
  fetchListings: vi.fn().mockResolvedValue({ items: [], total: 0, page: 1, limit: 20 }),
  fetchListing: vi.fn(),
}))

vi.mock('@/api/siteSettings', () => ({
  fetchSiteSettings: vi.fn().mockResolvedValue({
    id: 1,
    aboutText: 'About',
    phoneDisplay: '+375 29 000-00-00',
    phoneRaw: '+375290000000',
    email: 'support@renlo.by',
    supportHours: '9-18',
    ownerName: 'Renlo',
    address: 'Minsk',
    offersText: null,
    offersEmail: null,
    telegramUrl: 'https://t.me/renlo_bot',
    whatsappUrl: 'https://wa.me/375290000000',
    vkUrl: 'https://vk.com/renlo',
    isTest: false,
  }),
}))

vi.mock('@/api/exchangeRates', () => ({
  fetchExchangeRates: vi.fn().mockResolvedValue({
    usdToByn: 3.41,
    usdToRub: 96,
    source: 'nbrb',
    updatedAt: '2026-07-16T00:00:00+00:00',
  }),
}))

describe('HeaderTopBar', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    resetUserLocationBootstrap()
    vi.stubGlobal('navigator', {
      geolocation: undefined,
    })
  })

  it('renders city selector without currency switcher', async () => {
    const wrapper = mount(HeaderTopBar, {
      global: {
        plugins: [i18n],
        stubs: { RouterLink: { template: '<a><slot /></a>' } },
      },
    })

    await flushPromises()

    expect(wrapper.find('.header-top-bar__city').exists()).toBe(true)
    expect(wrapper.find('.theme-menu__trigger').exists()).toBe(true)
    expect(wrapper.find('.theme-menu__rainbow').exists()).toBe(true)
    expect(wrapper.find('.header-top-bar__rate').exists()).toBe(true)
    expect(wrapper.get('.header-top-bar__rate').text()).toContain('1 $ =')
    expect(wrapper.get('.header-top-bar__rate').text()).toContain('BYN')
    expect(wrapper.get('.header-top-bar__rate').attributes('href')).toBe('https://www.nbrb.by/statistics/rates/ratesdaily')
    expect(wrapper.get('.header-top-bar__rate').attributes('target')).toBe('_blank')
    expect(wrapper.find('.header-top-bar__currency').exists()).toBe(false)
    expect(wrapper.find('.header-top-bar__locale').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('RU')
    expect(wrapper.text()).not.toContain('EN')
  })

  it('renders social links and admin phone', async () => {
    const wrapper = mount(HeaderTopBar, {
      global: {
        plugins: [i18n],
        stubs: { RouterLink: true },
      },
    })

    await flushPromises()

    const socials = wrapper.findAll('.header-top-bar__social')
    expect(socials).toHaveLength(3)
    expect(socials[0].attributes('href')).toBe('https://t.me/renlo_bot')
    expect(socials[1].attributes('href')).toBe('https://wa.me/375290000000')
    expect(socials[2].attributes('href')).toBe('https://vk.com/renlo')

    const phone = wrapper.get('.header-top-bar__phone')
    expect(phone.attributes('href')).toBe('tel:+375290000000')
    expect(phone.text()).toContain('+375 29 000-00-00')
  })

  it('shows admin panel link for admin', async () => {
    const auth = useAuthStore()
    auth.user = {
      id: 1,
      email: 'admin@renlo.local',
      name: 'Admin',
      roles: ['ROLE_ADMIN'],
    }

    const wrapper = mount(HeaderTopBar, {
      global: {
        plugins: [i18n],
        stubs: {
          RouterLink: {
            props: ['to'],
            template: '<a class="header-top-bar__admin" :href="to"><slot /></a>',
          },
        },
      },
    })

    await flushPromises()

    const adminLink = wrapper.find('.header-top-bar__admin')
    expect(adminLink.exists()).toBe(true)
    expect(adminLink.text()).toContain(i18n.global.t('nav.admin'))
  })

  it('defaults to Belarus without city filter', async () => {
    const wrapper = mount(HeaderTopBar, {
      global: {
        plugins: [i18n],
        stubs: { RouterLink: true },
      },
    })

    await flushPromises()

    const listings = useListingsStore()
    expect(listings.cityId).toBeUndefined()
    expect(wrapper.get('.header-top-bar__city').element).toMatchObject({ value: '' })
    expect(wrapper.text()).toContain(i18n.global.t('map.breadcrumb.belarus'))
  })

  it('stores selected city in localStorage', async () => {
    const wrapper = mount(HeaderTopBar, {
      global: {
        plugins: [i18n],
        stubs: { RouterLink: true },
      },
    })

    await flushPromises()

    const listings = useListingsStore()
    const select = wrapper.get('.header-top-bar__city')
    await select.setValue('2')
    await select.trigger('change')
    await flushPromises()

    expect(listings.cityId).toBe(2)
    expect(localStorage.getItem('donmap-city-id')).toBe('2')
  })

  it('clears city filter when Belarus is selected', async () => {
    const wrapper = mount(HeaderTopBar, {
      global: {
        plugins: [i18n],
        stubs: { RouterLink: true },
      },
    })

    await flushPromises()

    const listings = useListingsStore()
    const select = wrapper.get('.header-top-bar__city')
    await select.setValue('1')
    await select.trigger('change')
    await flushPromises()

    await select.setValue('')
    await select.trigger('change')
    await flushPromises()

    expect(listings.cityId).toBeUndefined()
    expect(localStorage.getItem('donmap-city-id')).toBeNull()
  })
})
