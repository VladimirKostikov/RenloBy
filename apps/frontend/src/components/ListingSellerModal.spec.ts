import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingSellerModal from '@/components/ListingSellerModal.vue'
import ru from '@/locales/ru.json'

const fetchSellerProfile = vi.fn()
const fetchSellerListings = vi.fn()

vi.mock('@/api/sellers', () => ({
  fetchSellerProfile: (...args: unknown[]) => fetchSellerProfile(...args),
  fetchSellerListings: (...args: unknown[]) => fetchSellerListings(...args),
}))

describe('ListingSellerModal', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    fetchSellerProfile.mockReset()
    fetchSellerListings.mockReset()
    fetchSellerProfile.mockResolvedValue({
      id: 7,
      name: 'Иван Продавец',
      photo: null,
      phone: '+375291112233',
      telegram: 'ivan',
      whatsapp: null,
      viber: null,
      lastSeenAt: new Date().toISOString(),
      registeredAt: '2025-03-12T10:00:00.000Z',
      listingsCount: 1,
    })
    fetchSellerListings.mockResolvedValue({
      items: [
        {
          id: 11,
          dealType: 'sale',
          listingType: 'apartment',
          status: 'published',
          price: 100000,
          pricePerSqm: 1000,
          rooms: 2,
          area: 50,
          floor: 3,
          totalFloors: 9,
          address: 'ул. Тест, 1',
          latitude: 53.9,
          longitude: 27.5,
          metroMinutes: null,
          verified: false,
          aiGoodPrice: false,
          rentTerm: null,
          hasDeposit: false,
          utilitiesIncluded: false,
          noCommission: false,
          fromOwner: true,
          hasRenovation: false,
          priceNegotiable: false,
          views: 0,
          images: [],
          publishedAt: '2026-07-16T10:00:00+00:00',
          userId: 7,
          cityId: 1,
          districtId: 1,
          metroStationId: null,
        },
      ],
      total: 1,
      page: 1,
      limit: 12,
    })
  })

  it('loads seller profile and listings', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingSellerModal, {
      props: {
        open: true,
        sellerId: 7,
        fromOwner: true,
        initialSeller: {
          id: 7,
          name: 'Иван Продавец',
          photo: null,
          phone: '+375291112233',
          telegram: 'ivan',
          whatsapp: null,
          viber: null,
        },
      },
      global: {
        plugins: [i18n],
        stubs: {
          teleport: true,
          CatalogGridCard: {
            props: ['listing'],
            template: '<div class="stub-card">{{ listing.address }}</div>',
          },
        },
      },
    })

    await flushPromises()

    expect(fetchSellerProfile).toHaveBeenCalledWith(7)
    expect(fetchSellerListings).toHaveBeenCalledWith(7, { page: 1, limit: 12 })
    expect(wrapper.text()).toContain('О продавце')
    expect(wrapper.text()).toContain('Сейчас на сайте')
    expect(wrapper.text()).toContain('ул. Тест, 1')
  })

  it('shows registration date when seller was inactive for a long time', async () => {
    fetchSellerProfile.mockResolvedValue({
      id: 7,
      name: 'Иван Продавец',
      photo: null,
      phone: '+375291112233',
      telegram: 'ivan',
      whatsapp: null,
      viber: null,
      lastSeenAt: null,
      registeredAt: '2025-03-12T10:00:00.000Z',
      listingsCount: 1,
    })

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingSellerModal, {
      props: {
        open: true,
        sellerId: 7,
        fromOwner: true,
        initialSeller: {
          id: 7,
          name: 'Иван Продавец',
          photo: null,
          phone: '+375291112233',
          telegram: 'ivan',
          whatsapp: null,
          viber: null,
        },
      },
      global: {
        plugins: [i18n],
        stubs: {
          teleport: true,
          CatalogGridCard: true,
        },
      },
    })

    await flushPromises()

    expect(wrapper.text()).toContain('Давно не был на сайте')
    expect(wrapper.text()).toContain('На сайте с')
    expect(wrapper.text()).toMatch(/2025/)
  })
})
