import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { createMemoryHistory, createRouter } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import PromotionPanel from '@/modules/promotion/components/PromotionPanel.vue'
import ru from '@/locales/ru.json'
import type { PromotionTariff } from '@/types/promotion'

const tariffs: PromotionTariff[] = [
  {
    id: 'basic',
    nameKey: 'promotion.tariffs.basic.name',
    descriptionKey: 'promotion.tariffs.basic.description',
    durationKey: 'promotion.tariffs.basic.duration',
    featureKeys: ['promotion.tariffs.basic.features.0'],
    priceUsd: 9.9,
    popular: false,
    icon: '/figma/promo-basic.svg',
  },
  {
    id: 'optimal',
    nameKey: 'promotion.tariffs.optimal.name',
    descriptionKey: 'promotion.tariffs.optimal.description',
    durationKey: 'promotion.tariffs.optimal.duration',
    featureKeys: ['promotion.tariffs.optimal.features.0'],
    priceUsd: 19.9,
    popular: true,
    icon: '/figma/promo-optimal.svg',
  },
  {
    id: 'max',
    nameKey: 'promotion.tariffs.max.name',
    descriptionKey: 'promotion.tariffs.max.description',
    durationKey: 'promotion.tariffs.max.duration',
    featureKeys: ['promotion.tariffs.max.features.0'],
    priceUsd: 29.9,
    popular: false,
    icon: '/figma/promo-max.svg',
  },
]

vi.mock('@/modules/promotion/lib/promotionTariffs', () => ({
  PAYMENT_CURRENCIES: ['byn', 'rub', 'usd'],
  paymentCurrencyApiCode: (code: string) => code.toUpperCase(),
  loadPromotionTariffs: vi.fn(async () => tariffs),
  formatTariffPrice: () => '10 BYN',
}))

vi.mock('@/api/payments', () => ({
  createMyPayment: vi.fn(),
}))

vi.mock('@/modules/auth/composables/useAuthModal', () => ({
  useAuthModal: () => ({ openLogin: vi.fn() }),
}))

describe('PromotionPanel', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders embedded layout for account cabinet', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/', component: { template: '<div />' } }],
    })
    await router.push('/')

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(PromotionPanel, {
      props: { embedded: true },
      global: { plugins: [i18n, router] },
    })
    await flushPromises()

    expect(wrapper.classes()).toContain('promotion-panel--embedded')
    expect(wrapper.findAll('.promotion-card')).toHaveLength(3)
    expect(wrapper.find('.promotion-panel__header').exists()).toBe(true)
    expect(wrapper.find('.promotion-panel__currency').exists()).toBe(true)
  })
})
