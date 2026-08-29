import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingDetailPanel from '@/components/ListingDetailPanel.vue'
import ru from '@/locales/ru.json'
import type { ListingDto } from '@/types'

vi.mock('@/components/ListingDetailMapPreview.vue', () => ({
  default: {
    name: 'ListingDetailMapPreview',
    props: ['latitude', 'longitude'],
    template: '<div class="map-preview-stub" />',
  },
}))

vi.mock('@/components/ListingCharacteristicsModal.vue', () => ({
  default: {
    name: 'ListingCharacteristicsModal',
    props: ['rows'],
    emits: ['close'],
    template: '<div class="chars-modal-stub" />',
  },
}))

vi.mock('@/components/CatalogGridCard.vue', () => ({
  default: {
    name: 'CatalogGridCard',
    props: ['listing', 'compact', 'favorited', 'compared'],
    emits: ['open', 'favorite', 'compare'],
    template:
      '<article class="catalog-card-stub" @click="$emit(\'open\', listing.id)">{{ listing.address }}</article>',
  },
}))

vi.mock('@/lib/scrollListingDetailToTop', () => ({
  scrollListingDetailToTop: vi.fn(),
}))

vi.mock('@/lib/listingNearbyInfrastructure', () => ({
  fetchListingInfrastructureSummary: vi.fn().mockResolvedValue([]),
  fetchListingNearbyPlaces: vi.fn().mockResolvedValue([]),
}))

vi.mock('@/api/listings', () => ({
  fetchListings: vi.fn().mockResolvedValue({ items: [], total: 0, page: 1, limit: 20 }),
  fetchListing: vi.fn(),
}))

vi.mock('@/api/account', () => ({
  recordListingContactEvent: vi.fn().mockResolvedValue(undefined),
}))

vi.mock('@/api/comparisons', () => ({
  fetchComparisons: vi.fn().mockResolvedValue([]),
  toggleComparison: vi.fn(),
  removeComparison: vi.fn(),
}))

vi.mock('@/stores/toast', () => ({
  useToastStore: () => ({ show: vi.fn() }),
}))

const listing: ListingDto = {
  id: 42,
  dealType: 'rent',
  listingType: 'apartment',
  status: 'published',
  price: 850,
  pricePerSqm: 14,
  rooms: 2,
  area: 58,
  floor: 7,
  totalFloors: 12,
  address: 'ул. Петра Мстиславца, 18',
  latitude: 53.9,
  longitude: 27.5,
  metroMinutes: 8,
  verified: true,
  aiGoodPrice: true,
  rentTerm: 'long',
  hasDeposit: false,
  utilitiesIncluded: false,
  noCommission: false,
  fromOwner: false,
  hasRenovation: false,
  priceNegotiable: false,
  views: 152,
  images: ['https://example.com/photo.jpg'],
  publishedAt: '2026-07-14T10:00:00.000Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: 1,
}

function mountPanel(options: {
  asPage?: boolean
  showClose?: boolean
  listing?: ListingDto
} = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  setActivePinia(createPinia())

  return mount(ListingDetailPanel, {
    props: {
      listing: options.listing ?? listing,
      districtName: 'Фрунзенский район, Минск',
      metroStation: {
        id: 1,
        name: 'Михалово',
        slug: 'mihalovo',
        lineColor: '#009A49',
        cityId: 1,
      },
      asPage: options.asPage ?? false,
      showClose: options.showClose ?? true,
    },
    global: {
      plugins: [i18n],
    },
  })
}

describe('ListingDetailPanel', () => {
  it('renders page layout without dialog role and close button', () => {
    const wrapper = mountPanel({ asPage: true, showClose: false })

    expect(wrapper.find('.listing-detail-modal--page').exists()).toBe(true)
    expect(wrapper.attributes('role')).toBeUndefined()
    expect(wrapper.find('.listing-detail-modal__close').exists()).toBe(false)
    expect(wrapper.find('.listing-detail-modal__top-main').exists()).toBe(true)
  })

  it('renders modal layout with close button', () => {
    const wrapper = mountPanel({ asPage: false, showClose: true })

    expect(wrapper.attributes('role')).toBe('dialog')
    expect(wrapper.find('.listing-detail-modal__close').exists()).toBe(true)
    expect(wrapper.find('.listing-detail-modal--page').exists()).toBe(false)
  })

  it('shows listing price and address in summary', () => {
    const wrapper = mountPanel({ asPage: true, showClose: false })

    expect(wrapper.text()).toMatch(/850|3 тыс\. BYN/)
    expect(wrapper.text()).toContain('ул. Петра Мстиславца, 18')
    expect(wrapper.text()).toContain('Михалово')
  })

  it('shows seller card for sale listings', () => {
    const saleListing: ListingDto = {
      ...listing,
      dealType: 'sale',
      price: 145000,
      pricePerSqm: 2500,
      fromOwner: true,
      seller: {
        id: 7,
        name: 'Иван Продавец',
        photo: null,
        phone: '+375291112233',
        telegram: 'ivan_seller',
        whatsapp: '+375291112233',
        viber: null,
      },
    }
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })
    setActivePinia(createPinia())
    const wrapper = mount(ListingDetailPanel, {
      props: {
        listing: saleListing,
        asPage: true,
        showClose: false,
      },
      global: { plugins: [i18n, createPinia()] },
    })

    expect(wrapper.find('.listing-seller').exists()).toBe(true)
    expect(wrapper.text()).toContain('Иван Продавец')
    expect(wrapper.text()).toContain('Собственник')
    expect(wrapper.text()).toContain('+375 29 111-22-33')
    expect(wrapper.text()).toContain('Связаться с продавцом')
    expect(wrapper.text()).not.toContain('Добавить в корзину')
    expect(wrapper.text()).not.toContain('Написать в чате')
  })

  it('opens seller profile modal when seller card is clicked', async () => {
    const saleListing: ListingDto = {
      ...listing,
      dealType: 'sale',
      fromOwner: true,
      seller: {
        id: 7,
        name: 'Иван Продавец',
        photo: null,
        phone: '+375291112233',
        telegram: null,
        whatsapp: null,
        viber: null,
      },
    }
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })
    setActivePinia(createPinia())
    const wrapper = mount(ListingDetailPanel, {
      props: {
        listing: saleListing,
        asPage: true,
        showClose: false,
      },
      global: {
        plugins: [i18n, createPinia()],
        stubs: {
          ListingSellerModal: {
            props: ['open', 'sellerId'],
            template: '<div v-if="open" class="listing-seller-modal-stub" :data-seller-id="sellerId" />',
          },
        },
      },
    })

    expect(wrapper.find('.listing-seller-modal-stub').exists()).toBe(false)
    await wrapper.find('.listing-seller').trigger('click')
    expect(wrapper.find('.listing-seller-modal-stub').exists()).toBe(true)
    expect(wrapper.find('.listing-seller-modal-stub').attributes('data-seller-id')).toBe('7')
  })

  it('shows seller card for rent listings when seller is present', () => {
    const rentListing: ListingDto = {
      ...listing,
      dealType: 'rent',
      fromOwner: true,
      seller: {
        id: 8,
        name: 'Анна Арендодатель',
        photo: null,
        phone: '+375297778899',
        telegram: 'anna_rent',
        whatsapp: '+375297778899',
        viber: null,
      },
    }
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })
    setActivePinia(createPinia())
    const wrapper = mount(ListingDetailPanel, {
      props: {
        listing: rentListing,
        asPage: true,
        showClose: false,
      },
      global: { plugins: [i18n, createPinia()] },
    })

    expect(wrapper.find('.listing-seller').exists()).toBe(true)
    expect(wrapper.text()).toContain('Анна Арендодатель')
    expect(wrapper.text()).toContain('+375 29 777-88-99')
  })

  it('hides seller card when seller is missing', () => {
    const wrapper = mountPanel({ asPage: true, showClose: false })
    expect(wrapper.find('.listing-seller').exists()).toBe(false)
  })

  it('renders favorite control without card background icon', () => {
    const wrapper = mountPanel({ asPage: true, showClose: false })
    const favorite = wrapper.find('.listing-detail-modal__icon-btn')

    expect(favorite.exists()).toBe(true)
    expect(favorite.find('img').exists()).toBe(false)
    expect(favorite.find('svg').exists()).toBe(true)
  })

  it('updates compare button label and active state after toggle', async () => {
    const { toggleComparison } = await import('@/api/comparisons')
    vi.mocked(toggleComparison).mockResolvedValue({
      active: true,
      item: { id: 9, userId: 1, listingId: listing.id },
    })

    const wrapper = mountPanel({ asPage: true, showClose: false })
    const compareBtn = wrapper
      .findAll('button.listing-detail-modal__action-btn')
      .find((button) => button.text().includes('В сравнение'))

    expect(compareBtn).toBeTruthy()
    expect(compareBtn!.classes()).not.toContain('listing-detail-modal__action-btn--active')

    await compareBtn!.trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('В сравнении')
    expect(
      wrapper
        .findAll('button.listing-detail-modal__action-btn')
        .find((button) => button.text().includes('В сравнении'))
        ?.classes(),
    ).toContain('listing-detail-modal__action-btn--active')
  })

  it('shows square thumbs row and photo count when there are multiple images', () => {
    const multi = {
      ...listing,
      images: [
        'https://example.com/1.jpg',
        'https://example.com/2.jpg',
        'https://example.com/3.jpg',
      ],
    }
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })
    setActivePinia(createPinia())
    const wrapper = mount(ListingDetailPanel, {
      props: {
        listing: multi,
        asPage: true,
        showClose: false,
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.findAll('.listing-detail-modal__thumb')).toHaveLength(3)
    expect(wrapper.find('.listing-detail-modal__photos-count').text()).toContain('3 фото')
    expect(wrapper.find('.listing-detail-modal__summary-footer').exists()).toBe(true)
    expect(wrapper.findAll('.listing-detail-modal__gallery-dot')).toHaveLength(3)
    expect(wrapper.find('.listing-detail-modal__gallery-nav--next').exists()).toBe(true)
  })

  it('switches gallery slide via pagination dot', async () => {
    const multi = {
      ...listing,
      images: [
        'https://example.com/1.jpg',
        'https://example.com/2.jpg',
        'https://example.com/3.jpg',
      ],
    }
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })
    setActivePinia(createPinia())
    const wrapper = mount(ListingDetailPanel, {
      props: {
        listing: multi,
        asPage: true,
        showClose: false,
      },
      global: { plugins: [i18n] },
    })

    await wrapper.findAll('.listing-detail-modal__gallery-dot')[2].trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.findAll('.listing-detail-modal__gallery-dot')[2].classes()).toContain(
      'listing-detail-modal__gallery-dot--active',
    )
    expect(wrapper.find('.listing-detail-modal__gallery-counter').text()).toBe('3/3')
  })

  it('opens characteristics modal from all characteristics link', async () => {
    vi.useFakeTimers()
    const wrapper = mountPanel({
      listing: {
        ...listing,
        rooms: 2,
        floor: 7,
        totalFloors: 12,
        area: 58,
        listingType: 'apartment',
      },
      asPage: true,
      showClose: false,
    })
    await vi.advanceTimersByTimeAsync(500)
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.listing-detail-modal__card--conditions').exists()).toBe(true)
    expect(wrapper.text()).toContain('Условия сделки')

    const link = wrapper
      .findAll('button.listing-detail-modal__link')
      .find((button) => button.text().includes('Все характеристики'))

    expect(link).toBeTruthy()
    await link!.trigger('click')
    expect(wrapper.find('.chars-modal-stub').exists()).toBe(true)
    vi.useRealTimers()
  })

  it('renders similar listings as catalog cards', async () => {
    vi.useFakeTimers()
    const pinia = createPinia()
    setActivePinia(pinia)
    const listingsStore = (await import('@/stores/listings')).useListingsStore()
    listingsStore.items = [
      listing,
      { ...listing, id: 43, address: 'ул. Похожая, 1' },
      { ...listing, id: 44, address: 'ул. Похожая, 2' },
      { ...listing, id: 45, address: 'ул. Похожая, 3' },
      { ...listing, id: 46, address: 'ул. Похожая, 4' },
    ]

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingDetailPanel, {
      props: {
        listing,
        asPage: true,
        showClose: false,
      },
      global: { plugins: [i18n, pinia] },
    })

    await vi.advanceTimersByTimeAsync(500)
    await wrapper.vm.$nextTick()
    await Promise.resolve()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.listing-detail-modal__similar-grid').exists()).toBe(true)
    expect(wrapper.find('.listing-detail-modal__bento-main').exists()).toBe(true)
    expect(wrapper.find('.listing-detail-modal__bento-grid').exists()).toBe(true)
    expect(wrapper.find('.listing-detail-modal__card--conditions').exists()).toBe(true)
    expect(wrapper.text()).toContain('Условия сделки')
    expect(wrapper.findAll('.catalog-card-stub')).toHaveLength(4)
    vi.useRealTimers()
  })

  it('scrolls to top when similar listing is opened', async () => {
    vi.useFakeTimers()
    const { scrollListingDetailToTop } = await import('@/lib/scrollListingDetailToTop')
    vi.mocked(scrollListingDetailToTop).mockClear()

    const pinia = createPinia()
    setActivePinia(pinia)
    const listingsStore = (await import('@/stores/listings')).useListingsStore()
    listingsStore.items = [
      listing,
      { ...listing, id: 43, address: 'ул. Похожая, 1' },
      { ...listing, id: 44, address: 'ул. Похожая, 2' },
      { ...listing, id: 45, address: 'ул. Похожая, 3' },
      { ...listing, id: 46, address: 'ул. Похожая, 4' },
    ]
    listingsStore.openDetailListing = vi.fn().mockResolvedValue(undefined)

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingDetailPanel, {
      props: {
        listing,
        asPage: false,
        showClose: true,
      },
      global: { plugins: [i18n, pinia] },
    })

    await vi.advanceTimersByTimeAsync(500)
    await wrapper.vm.$nextTick()

    await wrapper.find('.catalog-card-stub').trigger('click')

    expect(scrollListingDetailToTop).toHaveBeenCalled()
    expect(listingsStore.openDetailListing).toHaveBeenCalledWith(43)
    vi.useRealTimers()
  })
})
