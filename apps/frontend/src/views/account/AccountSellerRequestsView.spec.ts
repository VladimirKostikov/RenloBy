import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { createRouter, createMemoryHistory } from 'vue-router'
import AccountSellerRequestsView from '@/views/account/AccountSellerRequestsView.vue'

const fetchMyListingRequests = vi.fn()
const deleteMyListingRequest = vi.fn()

vi.mock('@/api/account', () => ({
  fetchMyListingRequests: (...args: unknown[]) => fetchMyListingRequests(...args),
  deleteMyListingRequest: (...args: unknown[]) => deleteMyListingRequest(...args),
}))

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: vi.fn(),
}))

function mountView() {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: {
      ru: {
        listing: { loading: 'Загрузка' },
        account: {
          error: 'Ошибка',
          requests: {
            title: 'Заявки',
            subtitle: 'Заявки по объявлениям',
            empty: 'Заявок пока нет',
            listingId: 'Объявление №{id}',
            unknownListing: 'Объявление без адреса',
            name: 'Имя',
            phone: 'Телефон',
            delete: 'Удалить заявку',
            deleteConfirm: 'Удалить эту заявку?',
            statuses: { new: 'Новая', contacted: 'Связались', closed: 'Закрыта' },
          },
        },
      },
    },
  })

  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/account/seller/requests', component: AccountSellerRequestsView }],
  })

  return { i18n, router }
}

describe('AccountSellerRequestsView', () => {
  beforeEach(() => {
    fetchMyListingRequests.mockReset()
    deleteMyListingRequest.mockReset()
    vi.stubGlobal('confirm', vi.fn(() => true))
  })

  it('renders seller requests list', async () => {
    fetchMyListingRequests.mockResolvedValue([
      {
        id: 1,
        listingId: 10,
        name: 'Анна',
        phone: '+375291112233',
        message: 'Хочу посмотреть квартиру',
        status: 'new',
        createdAt: '2026-07-16T12:00:00+00:00',
        listingAddress: 'ул. Тестовая, 1',
      },
    ])

    const { i18n, router } = mountView()
    await router.push('/account/seller/requests')
    await router.isReady()

    const wrapper = mount(AccountSellerRequestsView, {
      global: { plugins: [i18n, router] },
    })
    await flushPromises()

    expect(fetchMyListingRequests).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Заявки')
    expect(wrapper.text()).toContain('ул. Тестовая, 1')
    expect(wrapper.text()).toContain('+375291112233')
    expect(wrapper.text()).toContain('Хочу посмотреть квартиру')
    expect(wrapper.find('[data-testid="request-delete"]').exists()).toBe(true)
  })

  it('deletes request after confirm', async () => {
    fetchMyListingRequests.mockResolvedValue([
      {
        id: 7,
        listingId: 10,
        name: 'Анна',
        phone: '+375291112233',
        message: 'Хочу посмотреть квартиру',
        status: 'new',
        createdAt: '2026-07-16T12:00:00+00:00',
        listingAddress: 'ул. Тестовая, 1',
      },
    ])
    deleteMyListingRequest.mockResolvedValue(undefined)

    const { i18n, router } = mountView()
    await router.push('/account/seller/requests')
    await router.isReady()

    const wrapper = mount(AccountSellerRequestsView, {
      global: { plugins: [i18n, router] },
    })
    await flushPromises()

    await wrapper.find('[data-testid="request-delete"]').trigger('click')
    await flushPromises()

    expect(window.confirm).toHaveBeenCalled()
    expect(deleteMyListingRequest).toHaveBeenCalledWith(7)
    expect(wrapper.text()).toContain('Заявок пока нет')
  })

  it('shows empty state', async () => {
    fetchMyListingRequests.mockResolvedValue([])

    const { i18n, router } = mountView()
    await router.push('/account/seller/requests')
    await router.isReady()

    const wrapper = mount(AccountSellerRequestsView, {
      global: { plugins: [i18n, router] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Заявок пока нет')
  })
})
