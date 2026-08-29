import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import AccountSellerCreateListingView from '@/views/account/AccountSellerCreateListingView.vue'
import { i18n } from '@/modules/locale'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/auth', () => ({
  fetchMe: vi.fn(async () => {
    const auth = useAuthStore()
    return auth.user
  }),
}))

vi.mock('@/modules/seller/components/ListingWizardPanel.vue', () => ({
  default: {
    name: 'ListingWizardPanel',
    template: '<div class="listing-wizard-stub" />',
  },
}))

describe('AccountSellerCreateListingView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  async function mountView() {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/seller/create', component: AccountSellerCreateListingView },
        { path: '/account/user/profile', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/seller/create')
    await router.isReady()

    return mount(AccountSellerCreateListingView, {
      global: { plugins: [i18n, router] },
    })
  }

  it('blocks listing creation until profile is complete', async () => {
    const auth = useAuthStore()
    auth.user = {
      id: 1,
      email: 'u@test.local',
      name: '',
      roles: ['ROLE_USER'],
      lastName: null,
      firstName: null,
      patronymic: null,
      telegram: null,
    }

    const wrapper = await mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Сначала заполните профиль')
    expect(wrapper.text()).toContain('Фамилия')
    expect(wrapper.text()).toContain('Хотя бы одна соцсеть')
    expect(wrapper.find('.listing-wizard-stub').exists()).toBe(false)
    expect(wrapper.get('a.account-create-listing__gate-cta').attributes('href')).toContain(
      '/account/user/profile',
    )
  })

  it('shows wizard when profile is complete', async () => {
    const auth = useAuthStore()
    auth.user = {
      id: 1,
      email: 'u@test.local',
      name: 'Иванов Иван Иванович',
      roles: ['ROLE_USER'],
      lastName: 'Иванов',
      firstName: 'Иван',
      patronymic: 'Иванович',
      telegram: '@ivan',
    }

    const wrapper = await mountView()
    await flushPromises()

    expect(wrapper.find('.listing-wizard-stub').exists()).toBe(true)
    expect(wrapper.find('.account-create-listing__gate').exists()).toBe(false)
  })
})
