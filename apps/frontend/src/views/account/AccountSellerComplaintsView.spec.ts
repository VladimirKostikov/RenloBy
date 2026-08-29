import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import AccountSellerComplaintsView from '@/views/account/AccountSellerComplaintsView.vue'
import { i18n } from '@/modules/locale'

const fetchMyListingReports = vi.fn()

vi.mock('@/api/account', () => ({
  fetchMyListingReports: (...args: unknown[]) => fetchMyListingReports(...args),
}))

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: () => undefined,
}))

describe('AccountSellerComplaintsView', () => {
  beforeEach(() => {
    fetchMyListingReports.mockReset()
  })

  it('renders seller complaints list', async () => {
    fetchMyListingReports.mockResolvedValue([
      {
        id: 1,
        listingId: 42,
        reason: 'spam',
        comment: 'Спам в описании объявления и контактах.',
        status: 'new',
        createdAt: '2026-07-16T10:00:00+00:00',
        listingAddress: 'ул. Тестовая, 1',
      },
    ])

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/seller/complaints', component: AccountSellerComplaintsView }],
    })
    await router.push('/account/seller/complaints')
    await router.isReady()

    const wrapper = mount(AccountSellerComplaintsView, {
      global: { plugins: [i18n, router] },
    })
    await flushPromises()

    expect(fetchMyListingReports).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Жалобы')
    expect(wrapper.text()).toContain('ул. Тестовая, 1')
    expect(wrapper.text()).toContain('Спам')
    expect(wrapper.text()).toContain('Новая')
    expect(wrapper.text()).toContain('Спам в описании объявления и контактах.')
  })

  it('shows empty state', async () => {
    fetchMyListingReports.mockResolvedValue([])

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/seller/complaints', component: AccountSellerComplaintsView }],
    })
    await router.push('/account/seller/complaints')
    await router.isReady()

    const wrapper = mount(AccountSellerComplaintsView, {
      global: { plugins: [i18n, router] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Жалоб пока нет')
  })
})
