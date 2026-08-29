import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import AccountSettingsView from '@/views/account/AccountSettingsView.vue'
import { i18n } from '@/modules/locale'
import { THEME_PALETTE_OPTIONS } from '@/modules/theme/lib/palettes'
import { useAuthStore } from '@/stores/auth'

describe('AccountSettingsView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    const auth = useAuthStore()
    auth.user = { id: 1, email: 'user@test.local', name: 'User', roles: ['ROLE_USER'] }
  })

  it('renders appearance and language settings', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/user/settings', component: AccountSettingsView },
        { path: '/account/user/profile', component: { template: '<div />' } },
        { path: '/', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/user/settings')
    await router.isReady()

    const wrapper = mount(AccountSettingsView, {
      global: {
        plugins: [i18n, router],
      },
    })

    expect(wrapper.text()).toContain('Настройки')
    expect(wrapper.text()).toContain('Цветовая палитра')
    expect(wrapper.findAll('.theme-appearance__palette')).toHaveLength(THEME_PALETTE_OPTIONS.length)
    expect(wrapper.findAll('.account-settings__mode-btn')).toHaveLength(2)
  })
})
