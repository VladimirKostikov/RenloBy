import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { createRouter, createWebHistory } from 'vue-router'
import AccountNotificationsView from '@/views/account/AccountNotificationsView.vue'
import { useNotificationsStore } from '@/stores/notifications'
import ru from '@/locales/ru.json'

const fetchNotifications = vi.fn()
const markRead = vi.fn()
const markAllRead = vi.fn()

vi.mock('@/api/notifications', () => ({
  fetchNotifications: (...args: unknown[]) => fetchNotifications(...args),
  fetchUnreadNotificationCount: vi.fn(async () => 1),
  markNotificationRead: (...args: unknown[]) => markRead(...args),
  markAllNotificationsRead: (...args: unknown[]) => markAllRead(...args),
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({ isAuthenticated: true }),
}))

describe('AccountNotificationsView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
    fetchNotifications.mockResolvedValue([
      {
        id: 1,
        type: 'listing_status_changed',
        payload: {
          listingId: 11,
          address: 'ул. Тест, 1',
          previousStatus: 'pending',
          status: 'published',
        },
        isRead: false,
        createdAt: '2026-07-16T10:00:00+00:00',
        isTest: false,
      },
    ])
    markRead.mockResolvedValue({
      id: 1,
      type: 'listing_status_changed',
      payload: {
        listingId: 11,
        address: 'ул. Тест, 1',
        previousStatus: 'pending',
        status: 'published',
      },
      isRead: true,
      createdAt: '2026-07-16T10:00:00+00:00',
      isTest: false,
    })
    markAllRead.mockResolvedValue(0)
  })

  it('renders listing status notification and marks it read on click', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/', component: { template: '<div />' } },
        { path: '/account/seller/listings', component: { template: '<div />' } },
        { path: '/account/seller/requests', component: { template: '<div />' } },
      ],
    })
    await router.push('/')

    const wrapper = mount(AccountNotificationsView, {
      global: {
        plugins: [i18n, router],
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Объявление одобрено')
    expect(wrapper.text()).toContain('ул. Тест, 1')
    expect(wrapper.find('.account-notifications__item--unread').exists()).toBe(true)

    await wrapper.get('.account-notifications__item').trigger('click')
    await flushPromises()

    expect(markRead).toHaveBeenCalledWith(1)
    expect(useNotificationsStore().unreadCount).toBe(0)
  })

  it('opens requests section for contact request notification', async () => {
    fetchNotifications.mockResolvedValue([
      {
        id: 2,
        type: 'listing_contact_request_created',
        payload: {
          listingId: 11,
          address: 'ул. Тест, 1',
          requestId: 5,
          phone: '+375291112233',
          name: 'Анна',
          message: 'Хочу посмотреть',
        },
        isRead: false,
        createdAt: '2026-07-16T10:00:00+00:00',
        isTest: false,
      },
    ])
    markRead.mockResolvedValue({
      id: 2,
      type: 'listing_contact_request_created',
      payload: {
        listingId: 11,
        address: 'ул. Тест, 1',
        requestId: 5,
        phone: '+375291112233',
        name: 'Анна',
        message: 'Хочу посмотреть',
      },
      isRead: true,
      createdAt: '2026-07-16T10:00:00+00:00',
      isTest: false,
    })

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/', component: { template: '<div />' } },
        { path: '/account/seller/listings', component: { template: '<div />' } },
        { path: '/account/seller/requests', component: { template: '<div />' } },
      ],
    })
    await router.push('/')

    const wrapper = mount(AccountNotificationsView, {
      global: {
        plugins: [i18n, router],
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Новая заявка')
    expect(wrapper.text()).toContain('+375291112233')

    await wrapper.get('.account-notifications__item').trigger('click')
    await flushPromises()

    expect(markRead).toHaveBeenCalledWith(2)
    expect(router.currentRoute.value.path).toBe('/account/seller/requests')
  })
})
