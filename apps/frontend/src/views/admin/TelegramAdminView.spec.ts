import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import TelegramAdminView from '@/views/admin/TelegramAdminView.vue'
import ru from '@/locales/ru.json'

const fetchStatus = vi.fn()
const syncUpdates = vi.fn()

vi.mock('@/api/admin', () => ({
  fetchTelegramStatus: (...args: unknown[]) => fetchStatus(...args),
  syncTelegramUpdates: (...args: unknown[]) => syncUpdates(...args),
  updateTelegramSubscriber: vi.fn(),
  deleteTelegramSubscriber: vi.fn(),
}))

describe('TelegramAdminView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    fetchStatus.mockResolvedValue({
      configured: true,
      botUsername: 'renlo_bot',
      connectUrl: 'https://t.me/renlo_bot?start=connect',
      webhookUrl: '',
      webhookPendingUpdateCount: 2,
      webhookLastError: null,
      subscribers: [],
    })
    syncUpdates.mockResolvedValue({
      configured: true,
      botUsername: 'renlo_bot',
      connectUrl: 'https://t.me/renlo_bot?start=connect',
      webhookUrl: '',
      webhookPendingUpdateCount: 0,
      webhookLastError: null,
      processed: 2,
      connected: 1,
      subscribers: [
        {
          id: 1,
          chatId: '123',
          username: 'boss',
          firstName: 'Boss',
          isActive: true,
          connectedAt: '2026-07-16T10:00:00+00:00',
        },
      ],
    })
  })

  it('syncs updates and shows subscribers', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(TelegramAdminView, {
      global: { plugins: [i18n] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Синхронизировать')
    expect(wrapper.text()).not.toContain('Webhook не настроен')
    expect(wrapper.text()).not.toContain('Откройте ссылку на бота ниже')

    await wrapper.get('.admin-btn-primary').trigger('click')
    await flushPromises()

    expect(syncUpdates).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Обработано обновлений: 2')
    expect(wrapper.text()).toContain('boss')
  })
})
