import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { createRouter, createWebHistory } from 'vue-router'
import HomeHeader from '@/components/layout/HomeHeader.vue'
import { useAuthStore } from '@/stores/auth'
import { useComparisonsStore } from '@/stores/comparisons'
import { useFavoritesStore } from '@/stores/favorites'
import { useNotificationsStore } from '@/stores/notifications'
import ru from '@/locales/ru.json'

const fetchUnreadCount = vi.fn(async () => 0)

vi.mock('@/api/notifications', () => ({
  fetchUnreadNotificationCount: (...args: unknown[]) => fetchUnreadCount(...args),
  fetchNotifications: vi.fn(async () => []),
  markNotificationRead: vi.fn(),
  markAllNotificationsRead: vi.fn(),
}))

vi.mock('@/api/reference', () => ({
  fetchCities: vi.fn(async () => []),
  fetchDistricts: vi.fn(async () => []),
  fetchMetroStations: vi.fn(async () => []),
}))

function mountHeader() {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div />' } },
      { path: '/favorites', name: 'favorites', component: { template: '<div />' } },
      { path: '/compare', name: 'compare', component: { template: '<div />' } },
      { path: '/account/seller/create', name: 'account-seller-create', component: { template: '<div />' } },
    ],
  })

  setActivePinia(createPinia())

  const wrapper = mount(HomeHeader, {
    global: {
      plugins: [i18n, router],
    },
  })

  return { wrapper, router }
}

describe('HomeHeader', () => {
  it('renders centered search with favorites and compare links', () => {
    const { wrapper } = mountHeader()
    const center = wrapper.find('.home-header__center')

    expect(center.exists()).toBe(true)
    expect(center.find('.header-search__input').exists()).toBe(true)
    expect(center.find('.home-header__quick-actions').exists()).toBe(true)
    expect(center.find('a[href="/favorites"]').exists()).toBe(true)
    expect(center.find('a[href="/compare"]').exists()).toBe(true)
    expect(wrapper.find('.home-header__actions .home-header__login').exists()).toBe(true)
    expect(wrapper.find('.home-header__login .home-header__login-icon').exists()).toBe(true)
  })

  it('keeps padded hover area for favorites and compare actions', () => {
    const { wrapper } = mountHeader()
    const action = wrapper.find('a[href="/favorites"].home-header__action')

    expect(action.exists()).toBe(true)
    expect(action.classes()).toContain('home-header__action')
    expect(wrapper.find('.home-header__quick-actions').exists()).toBe(true)
  })

  it('renders action icons for profile and post listing', async () => {
    const { wrapper } = mountHeader()

    expect(wrapper.find('.home-header__cta .home-header__cta-icon').exists()).toBe(true)
    expect(wrapper.text()).toContain('Подать объявление')

    const auth = useAuthStore()
    auth.user = { id: 1, email: 'user@example.com', name: 'User', roles: ['ROLE_USER'] }
    await wrapper.vm.$nextTick()

    const profileLink = wrapper.find('a.home-header__user')
    expect(profileLink.exists()).toBe(true)
    expect(profileLink.attributes('href')).toBe('/account/user/profile')
    expect(profileLink.find('.home-header__user-icon').exists()).toBe(true)
    expect(profileLink.text()).toContain('Профиль')
  })

  it('shows notification dot next to profile when unread exist', async () => {
    fetchUnreadCount.mockResolvedValueOnce(3)
    const pinia = createPinia()
    setActivePinia(pinia)

    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/', name: 'home', component: { template: '<div />' } },
        { path: '/favorites', name: 'favorites', component: { template: '<div />' } },
        { path: '/compare', name: 'compare', component: { template: '<div />' } },
      ],
    })

    const auth = useAuthStore()
    auth.user = { id: 1, email: 'user@example.com', name: 'User', roles: ['ROLE_USER'] }

    const wrapper = mount(HomeHeader, {
      global: {
        plugins: [i18n, router, pinia],
      },
    })
    await wrapper.vm.$nextTick()
    await Promise.resolve()
    await wrapper.vm.$nextTick()

    expect(useNotificationsStore().unreadCount).toBe(3)
    expect(wrapper.find('.home-header__notify-dot').exists()).toBe(true)
  })

  it('shows collection counts in badges under action labels', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const favorites = useFavoritesStore()
    const comparisons = useComparisonsStore()

    vi.spyOn(favorites, 'count', 'get').mockReturnValue(1)
    vi.spyOn(comparisons, 'count', 'get').mockReturnValue(2)

    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/', name: 'home', component: { template: '<div />' } },
        { path: '/favorites', name: 'favorites', component: { template: '<div />' } },
        { path: '/compare', name: 'compare', component: { template: '<div />' } },
      ],
    })

    const wrapper = mount(HomeHeader, {
      global: {
        plugins: [i18n, router, pinia],
      },
    })

    const favoritesAction = wrapper.find('a[href="/favorites"]')
    const compareAction = wrapper.find('a[href="/compare"]')

    expect(favoritesAction.find('.home-header__action-label').text()).toBe('Избранное')
    expect(compareAction.find('.home-header__action-label').text()).toBe('Сравнение')
    expect(favoritesAction.find('.home-header__badge').text()).toBe('1')
    expect(compareAction.find('.home-header__badge').text()).toBe('2')
    expect(favoritesAction.find('.home-header__action-icon .home-header__badge').exists()).toBe(true)
    expect(compareAction.find('.home-header__action-icon .home-header__badge').exists()).toBe(true)
  })

  it('renders deal type nav with icons', () => {
    const { wrapper } = mountHeader()
    const nav = wrapper.find('.home-header__nav')

    expect(nav.exists()).toBe(true)
    expect(nav.findAll('.home-header__nav-item')).toHaveLength(3)
    expect(nav.findAll('.home-header__nav-icon')).toHaveLength(3)
    expect(nav.text()).toContain('Продажа')
    expect(nav.text()).toContain('Аренда')
    expect(nav.text()).toContain('Бизнес')
  })

  it('navigates authenticated user to create listing page', async () => {
    const { wrapper, router } = mountHeader()
    const auth = useAuthStore()
    auth.user = { id: 1, email: 'user@example.com', name: 'User', roles: ['ROLE_USER'] }
    await wrapper.vm.$nextTick()

    const push = vi.spyOn(router, 'push').mockResolvedValue(undefined as never)
    const scrollTo = vi.fn()
    vi.stubGlobal('scrollTo', scrollTo)

    await wrapper.find('.home-header__cta').trigger('click')

    expect(push).toHaveBeenCalledWith('/account/seller/create')
    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' })
  })
})
