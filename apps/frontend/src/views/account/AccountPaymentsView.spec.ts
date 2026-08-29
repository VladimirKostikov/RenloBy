import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import AccountPaymentsView from '@/views/account/AccountPaymentsView.vue'
import ru from '@/locales/ru.json'

const fetchMyPayments = vi.fn()

vi.mock('@/api/payments', () => ({
  fetchMyPayments: (...args: unknown[]) => fetchMyPayments(...args),
}))

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: vi.fn(),
}))

describe('AccountPaymentsView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    fetchMyPayments.mockResolvedValue([
      {
        id: 1,
        userId: 2,
        amount: '29.00',
        currency: 'BYN',
        status: 'succeeded',
        provider: 'yookassa',
        providerPaymentId: 'pay_1',
        description: 'Продвижение на 7 дней',
        confirmationUrl: null,
        metadata: {},
        isTest: true,
        createdAt: '2026-07-16T10:00:00+00:00',
        updatedAt: '2026-07-16T10:00:00+00:00',
      },
    ])
  })

  it('renders payment history without tariffs panel', async () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(AccountPaymentsView, {
      global: {
        plugins: [i18n],
      },
    })

    await flushPromises()

    expect(wrapper.find('h1').text()).toBe('История платежей')
    expect(wrapper.find('.payments__tariffs').exists()).toBe(false)
    expect(wrapper.text()).toContain('29.00 BYN')
    expect(wrapper.text()).toContain('Продвижение на 7 дней')
    expect(wrapper.text()).not.toContain('Тарифы продвижения')
  })
})
