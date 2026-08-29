import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { createMemoryHistory, createRouter } from 'vue-router'
import AccountSellerAnalyticsView from '@/views/account/AccountSellerAnalyticsView.vue'
import ru from '@/locales/ru.json'

const fetchListingAnalyticsOptions = vi.fn()
const fetchListingAnalyticsDetail = vi.fn()

vi.mock('@/api/account', () => ({
  fetchListingAnalyticsOptions: (...args: unknown[]) => fetchListingAnalyticsOptions(...args),
  fetchListingAnalyticsDetail: (...args: unknown[]) => fetchListingAnalyticsDetail(...args),
}))

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: vi.fn(),
}))

function makeOption(id: number) {
  return {
    id,
    title: `Квартира ${id}`,
    address: `ул. Тест, ${id}`,
    image: 'https://example.com/a.jpg',
    rooms: 2,
    area: 56,
    status: 'published',
    views: 120,
  }
}

describe('AccountSellerAnalyticsView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
    fetchListingAnalyticsOptions.mockResolvedValue({
      items: [makeOption(42)],
      total: 1,
      page: 1,
      limit: 20,
    })
    fetchListingAnalyticsDetail.mockResolvedValue({
      listing: makeOption(42),
      updatedAt: '2026-07-16T12:20:00+00:00',
      views: {
        day: 10,
        week: 70,
        month: 200,
        dayChangePct: 5,
        weekChangePct: 18,
        monthChangePct: 12,
      },
      contactOpensWeek: 37,
      contactOpensChangePct: 18,
      messagesWeek: 19,
      messagesChangePct: 18,
      conversionPct: 4.2,
      conversionChangePct: 18,
      viewsSeries: [
        { date: '2026-07-10', views: 36, average: 50 },
        { date: '2026-07-11', views: 42, average: 50 },
      ],
      funnel: {
        views: 124,
        contacts: 37,
        messages: 12,
        viewToContactPct: 29.8,
        contactToMessagePct: 32.4,
      },
      promotion: {
        active: true,
        tariff: 'premium',
        rows: [
          { metric: 'views', before: 20, after: 70, growthPct: 140 },
        ],
      },
      engagement: {
        contactsTotal: 37,
        messagesTotal: 12,
        contactsAvg: 5.3,
        contactsPeak: 9,
        series: [
          { date: '2026-07-01', contacts: 4, messages: 1 },
          { date: '2026-07-16', contacts: 9, messages: 3 },
        ],
      },
    })
  })

  async function mountView() {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/analytics', component: AccountSellerAnalyticsView },
        { path: '/account/seller/create', component: { template: '<div />' } },
        { path: '/account/seller/listings', component: { template: '<div />' } },
        { path: '/account/seller/promotion', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/analytics')
    await router.isReady()

    return mount(AccountSellerAnalyticsView, {
      global: {
        plugins: [i18n, createPinia(), router],
      },
    })
  }

  it('asks to select a listing before loading analytics', async () => {
    const wrapper = await mountView()
    await flushPromises()

    expect(wrapper.find('h1').text()).toBe('Аналитика объявления')
    expect(wrapper.find('[data-testid="analytics-select"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="analytics-search"]').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('Выберите объявление')
    expect(wrapper.text()).toContain('Квартира 42')
    expect(fetchListingAnalyticsOptions).toHaveBeenCalledWith({
      page: 1,
      limit: 20,
      q: undefined,
    })
    expect(fetchListingAnalyticsDetail).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="analytics-metrics"]').exists()).toBe(false)
  })

  it('loads analytics after listing is selected', async () => {
    const wrapper = await mountView()
    await flushPromises()

    await wrapper.get('.listing-analytics__select-card').trigger('click')
    await flushPromises()

    expect(fetchListingAnalyticsDetail).toHaveBeenCalledWith(42, 'week')
    expect(wrapper.find('[data-testid="analytics-select"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('Открытия контактов')
    expect(wrapper.text()).toContain('37')
    expect(wrapper.find('[data-testid="analytics-metrics"] .metric-card--views').exists()).toBe(true)
    expect(wrapper.findAll('[data-testid="analytics-metrics"] .metric-card__period')).toHaveLength(3)
    expect(wrapper.find('[data-testid="analytics-funnel"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="analytics-engagement"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="analytics-tips"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('Воронка интереса')
    expect(wrapper.text()).toContain('Динамика обращений')
    expect(wrapper.text()).not.toContain('Советы ИИ')
    expect(wrapper.text()).toContain('Среднее за период')
  })

  it('paginates listing options', async () => {
    fetchListingAnalyticsOptions
      .mockResolvedValueOnce({
        items: Array.from({ length: 20 }, (_, i) => makeOption(i + 1)),
        total: 25,
        page: 1,
        limit: 20,
      })
      .mockResolvedValueOnce({
        items: Array.from({ length: 5 }, (_, i) => makeOption(i + 21)),
        total: 25,
        page: 2,
        limit: 20,
      })

    const wrapper = await mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Страница 1 из 2')
    await wrapper.get('.listing-analytics__page-btn:last-child').trigger('click')
    await flushPromises()

    expect(fetchListingAnalyticsOptions).toHaveBeenLastCalledWith({
      page: 2,
      limit: 20,
      q: undefined,
    })
    expect(wrapper.text()).toContain('Квартира 21')
    expect(wrapper.text()).toContain('Страница 2 из 2')
  })

  it('searches listings by query', async () => {
    vi.useFakeTimers()
    fetchListingAnalyticsOptions
      .mockResolvedValueOnce({
        items: [makeOption(42)],
        total: 1,
        page: 1,
        limit: 20,
      })
      .mockResolvedValueOnce({
        items: [makeOption(7)],
        total: 1,
        page: 1,
        limit: 20,
      })

    const wrapper = await mountView()
    await flushPromises()

    await wrapper.get('[data-testid="analytics-search"]').setValue('ул. Тест')
    await wrapper.get('[data-testid="analytics-search"]').trigger('input')
    await vi.advanceTimersByTimeAsync(350)
    await flushPromises()

    expect(fetchListingAnalyticsOptions).toHaveBeenLastCalledWith({
      page: 1,
      limit: 20,
      q: 'ул. Тест',
    })
    expect(wrapper.text()).toContain('Квартира 7')
    vi.useRealTimers()
  })

  it('scrolls smoothly when opening listing analytics', async () => {
    const scrollTo = vi.fn()
    vi.stubGlobal('scrollTo', scrollTo)

    const wrapper = await mountView()
    await flushPromises()

    await wrapper.get('.listing-analytics__select-card').trigger('click')
    await flushPromises()

    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' })
    expect(wrapper.find('.listing-analytics__detail').exists()).toBe(true)

    vi.unstubAllGlobals()
  })
})
