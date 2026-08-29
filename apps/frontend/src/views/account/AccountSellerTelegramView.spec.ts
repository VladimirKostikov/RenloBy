import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import AccountSellerTelegramView from '@/views/account/AccountSellerTelegramView.vue'
import ru from '@/locales/ru.json'

const fetchSellerTelegramStatus = vi.fn()
const disconnectSellerTelegram = vi.fn()

vi.mock('@/api/account', () => ({
  fetchSellerTelegramStatus: (...args: unknown[]) => fetchSellerTelegramStatus(...args),
  disconnectSellerTelegram: (...args: unknown[]) => disconnectSellerTelegram(...args),
}))

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: vi.fn(),
}))

describe('AccountSellerTelegramView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    fetchSellerTelegramStatus.mockResolvedValue({
      configured: true,
      connected: false,
      botUsername: 'renlo_bot',
      connectUrl: 'https://t.me/renlo_bot?start=s1_1_abc',
      username: null,
      connectedAt: null,
    })
  })

  it('shows disconnected state and connect link', async () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(AccountSellerTelegramView, {
      global: { plugins: [i18n] },
    })
    await flushPromises()

    expect(wrapper.find('h1').text()).toBe('Telegram уведомления')
    expect(wrapper.text()).toContain('Не подключено')
    const connect = wrapper.get('[data-testid="telegram-connect"]')
    expect(connect.attributes('href')).toContain('t.me/renlo_bot')
  })

  it('shows connected badge and disconnect action', async () => {
    fetchSellerTelegramStatus.mockResolvedValue({
      configured: true,
      connected: true,
      botUsername: 'renlo_bot',
      connectUrl: 'https://t.me/renlo_bot?start=s1_1_abc',
      username: 'seller_tg',
      connectedAt: '2026-07-16T12:00:00+00:00',
    })

    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(AccountSellerTelegramView, {
      global: { plugins: [i18n] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Подключено')
    expect(wrapper.text()).toContain('@seller_tg')
    expect(wrapper.find('[data-testid="telegram-disconnect"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="telegram-connect"]').exists()).toBe(false)
  })
})
