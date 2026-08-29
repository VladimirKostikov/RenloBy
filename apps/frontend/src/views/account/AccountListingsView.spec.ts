import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import AccountListingsView from '@/views/account/AccountListingsView.vue'
import { i18n } from '@/modules/locale'
import type { ListingDto } from '@/types'

const fetchMyListings = vi.fn()

vi.mock('@/api/account', () => ({
  fetchMyListings: (...args: unknown[]) => fetchMyListings(...args),
  publishMyListing: vi.fn(),
  archiveMyListing: vi.fn(),
  deleteMyDraftListing: vi.fn().mockResolvedValue(undefined),
  updateMyListing: vi.fn(),
}))

function makeListing(id: number): ListingDto {
  return {
    id,
    dealType: 'sale',
    listingType: 'apartment',
    status: 'published',
    price: 100000,
    pricePerSqm: 1000,
    rooms: 2,
    area: 50,
    floor: 3,
    totalFloors: 9,
    address: `Street ${id}`,
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
    views: id,
    images: [],
    publishedAt: '2026-01-01T00:00:00+00:00',
    userId: 1,
    cityId: 1,
    districtId: 1,
    metroStationId: null,
  }
}

describe('AccountListingsView', () => {
  beforeEach(() => {
    fetchMyListings.mockReset()
    setActivePinia(createPinia())
  })

  it('loads listings with page size 20 and paginates', async () => {
    fetchMyListings.mockResolvedValueOnce({
      items: Array.from({ length: 20 }, (_, i) => makeListing(i + 1)),
      total: 25,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
        { path: '/', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    expect(fetchMyListings).toHaveBeenCalledWith({ page: 1, limit: 20 })
    expect(wrapper.findAll('tbody tr')).toHaveLength(20)
    expect(wrapper.text()).toContain('Страница 1 из 2')

    fetchMyListings.mockResolvedValueOnce({
      items: Array.from({ length: 5 }, (_, i) => makeListing(i + 21)),
      total: 25,
      page: 2,
      limit: 20,
    })

    await wrapper.get('.account-listings__page-btn:last-child').trigger('click')
    await flushPromises()

    expect(fetchMyListings).toHaveBeenLastCalledWith({ page: 2, limit: 20 })
    expect(wrapper.findAll('tbody tr')).toHaveLength(5)
    expect(wrapper.text()).toContain('Страница 2 из 2')
  })

  it('hides pagination when total is within one page', async () => {
    fetchMyListings.mockResolvedValueOnce({
      items: [makeListing(1)],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    expect(wrapper.find('.account-listings__pagination').exists()).toBe(false)
  })

  it('shows first listing image in photo column', async () => {
    fetchMyListings.mockResolvedValueOnce({
      items: [
        {
          ...makeListing(3),
          images: ['https://example.com/a.jpg', 'https://example.com/b.jpg'],
        },
      ],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Фото')
    const img = wrapper.get('.account-listings__thumb-img')
    expect(img.attributes('src')).toBe('https://example.com/a.jpg')
  })

  it('filters drafts list', async () => {
    fetchMyListings.mockResolvedValue({
      items: [
        {
          ...makeListing(5),
          status: 'draft',
          address: 'Draft street 1',
        },
      ],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    const draftTab = wrapper.findAll('.account-listings__filter').find((btn) => btn.text() === 'Черновики')
    expect(draftTab).toBeDefined()
    await draftTab!.trigger('click')
    await flushPromises()

    expect(fetchMyListings).toHaveBeenCalledWith({ page: 1, limit: 20, status: 'draft' })
    expect(wrapper.text()).toContain('Draft street 1')
    expect(wrapper.get('[data-testid="listing-delete"]').attributes('aria-label')).toContain('Удалить')
  })

  it('shows delete action for pending listings', async () => {
    fetchMyListings.mockResolvedValue({
      items: [
        {
          ...makeListing(8),
          status: 'pending',
          address: 'Pending street 8',
        },
      ],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    const pendingTab = wrapper.findAll('.account-listings__filter').find((btn) => btn.text() === 'На модерации')
    expect(pendingTab).toBeDefined()
    await pendingTab!.trigger('click')
    await flushPromises()

    expect(fetchMyListings).toHaveBeenCalledWith({ page: 1, limit: 20, status: 'pending' })
    expect(wrapper.text()).toContain('Pending street 8')
    const deleteBtn = wrapper.get('[data-testid="listing-delete"]')
    expect(deleteBtn.attributes('aria-label')).toContain('Удалить')
    expect(deleteBtn.classes()).toContain('account-action-btn--delete')
  })

  it('opens preview modal for pending listings', async () => {
    fetchMyListings.mockResolvedValue({
      items: [
        {
          ...makeListing(9),
          status: 'pending',
          address: 'Moderation street 9',
          districtName: 'Центр',
        },
      ],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: {
        plugins: [i18n, router, createPinia()],
        stubs: { ListingDetailModal: true },
      },
    })
    await flushPromises()

    const pendingTab = wrapper.findAll('.account-listings__filter').find((btn) => btn.text() === 'На модерации')
    await pendingTab!.trigger('click')
    await flushPromises()

    const previewBtn = wrapper.get('[data-testid="listing-preview"]')
    expect(previewBtn.attributes('aria-label')).toContain('Просмотреть')
    expect(previewBtn.classes()).toContain('account-action-btn--view')
    await previewBtn.trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'ListingDetailModal' })
    expect(modal.exists()).toBe(true)
    expect(modal.props('listing')).toMatchObject({
      id: 9,
      status: 'pending',
      address: 'Moderation street 9',
    })
    expect(modal.props('districtName')).toBe('Центр')
    expect(router.currentRoute.value.path).toBe('/account/seller/listings')
  })

  it('opens preview modal for published listings without leaving account', async () => {
    fetchMyListings.mockResolvedValue({
      items: [
        {
          ...makeListing(12),
          status: 'published',
          address: 'Published street 12',
        },
      ],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
        { path: '/sale/listings/:id', name: 'sale-listing-detail', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: {
        plugins: [i18n, router, createPinia()],
        stubs: { ListingDetailModal: true },
      },
    })
    await flushPromises()

    await wrapper.get('[data-testid="listing-preview"]').trigger('click')
    await flushPromises()

    const modal = wrapper.findComponent({ name: 'ListingDetailModal' })
    expect(modal.exists()).toBe(true)
    expect(modal.props('listing')).toMatchObject({
      id: 12,
      status: 'published',
      address: 'Published street 12',
    })
    expect(router.currentRoute.value.path).toBe('/account/seller/listings')
  })

  it('keeps action icons in a fixed four-slot grid', async () => {
    fetchMyListings.mockResolvedValue({
      items: [
        { ...makeListing(1), status: 'published' },
        { ...makeListing(2), status: 'pending' },
      ],
      total: 2,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    const rows = wrapper.findAll('.account-listings__actions-row')
    expect(rows).toHaveLength(2)
    rows.forEach((row) => {
      expect(row.element.children).toHaveLength(4)
    })
  })

  it('opens SEO modal for a listing', async () => {
    fetchMyListings.mockResolvedValueOnce({
      items: [{ ...makeListing(5), address: 'SEO street 5' }],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      attachTo: document.body,
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    await wrapper.get('[data-testid="listing-seo"]').trigger('click')
    await flushPromises()

    expect(document.querySelector('[data-testid="listing-seo-modal"]')).not.toBeNull()
    expect(document.body.textContent).toContain('SEO объявления')
    expect(document.body.textContent).toContain('SEO street 5')
    wrapper.unmount()
  })

  it('stacks published and verified statuses without overlap', async () => {
    fetchMyListings.mockResolvedValueOnce({
      items: [{ ...makeListing(1), status: 'published', verified: true }],
      total: 1,
      page: 1,
      limit: 20,
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/listings', component: AccountListingsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountListingsView, {
      global: { plugins: [i18n, router, createPinia()] },
    })
    await flushPromises()

    const statuses = wrapper.find('.account-listings__statuses')
    expect(statuses.exists()).toBe(true)
    expect(statuses.findAll('.listing-status-chip')).toHaveLength(2)
    expect(statuses.find('[data-status="published"]').exists()).toBe(true)
    expect(statuses.find('[data-status="verified"]').exists()).toBe(true)
  })
})
