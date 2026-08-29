import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import AccountSidebar from '@/modules/account/components/AccountSidebar.vue'
import {
  ACCOUNT_SELLER_NAV_ITEMS,
  ACCOUNT_USER_NAV_ITEMS,
} from '@/modules/account/lib/accountNav'
import { i18n } from '@/modules/locale'

describe('AccountSidebar', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders cabinet switcher buttons', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/user/profile', component: { template: '<div />' } }],
    })
    await router.push('/account/user/profile')
    await router.isReady()

    const wrapper = mount(AccountSidebar, {
      global: {
        plugins: [i18n, router],
      },
    })

    const buttons = wrapper.findAll('.account-sidebar__switch-btn')
    expect(buttons).toHaveLength(2)
    expect(buttons[0]?.text()).toContain('Пользователь')
    expect(buttons[1]?.text()).toContain('Продавец')
    expect(buttons[0]?.classes()).toContain('account-sidebar__switch-btn--active')
    const userIcon = buttons[0]?.find('.account-sidebar__switch-icon').element as HTMLElement
    const sellerIcon = buttons[1]?.find('.account-sidebar__switch-icon').element as HTMLElement
    expect(userIcon.style.maskImage || userIcon.style.webkitMaskImage).toContain(
      'account-cabinet-user.svg',
    )
    expect(sellerIcon.style.maskImage || sellerIcon.style.webkitMaskImage).toContain(
      'account-cabinet-seller.svg',
    )
  })

  it('shows only user nav items on user cabinet routes', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/user/profile', component: { template: '<div />' } }],
    })
    await router.push('/account/user/profile')
    await router.isReady()

    const wrapper = mount(AccountSidebar, {
      global: {
        plugins: [i18n, router],
      },
    })

    const icons = wrapper.findAll('.account-sidebar__link-icon')
    expect(icons).toHaveLength(ACCOUNT_USER_NAV_ITEMS.length)
    expect(wrapper.find('a[href="/account/user/profile"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/user/notifications"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/user/favorites"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/user/settings"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/seller/listings"]').exists()).toBe(false)
  })

  it('shows pinned profile and notifications with seller nav items', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/account/seller/listings', component: { template: '<div />' } }],
    })
    await router.push('/account/seller/listings')
    await router.isReady()

    const wrapper = mount(AccountSidebar, {
      global: {
        plugins: [i18n, router],
      },
    })

    const links = wrapper.findAll('.account-sidebar__link')
    expect(links).toHaveLength(ACCOUNT_SELLER_NAV_ITEMS.length)
    expect(links[0]?.attributes('href')).toBe('/account/user/profile')
    expect(links[1]?.attributes('href')).toBe('/account/user/notifications')
    expect(wrapper.find('a[href="/account/user/profile"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/user/notifications"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/seller/listings"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/seller/requests"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/seller/complaints"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/account/user/favorites"]').exists()).toBe(false)
  })

  it('switches cabinet on button click', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/user/profile', component: { template: '<div />' } },
        { path: '/account/seller/listings', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/user/profile')
    await router.isReady()

    const wrapper = mount(AccountSidebar, {
      global: {
        plugins: [i18n, router],
      },
    })

    await wrapper.findAll('.account-sidebar__switch-btn')[1]?.trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/account/seller/listings')
  })
})
