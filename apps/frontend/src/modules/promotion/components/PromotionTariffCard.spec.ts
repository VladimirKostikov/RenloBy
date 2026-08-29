import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import PromotionTariffCard from '@/modules/promotion/components/PromotionTariffCard.vue'
import { PROMOTION_TARIFFS } from '@/modules/promotion/lib/promotionTariffs'
import ru from '@/locales/ru.json'

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: { ru },
})

describe('PromotionTariffCard', () => {
  it('renders tariff details and emits select', async () => {
    const tariff = PROMOTION_TARIFFS[0]
    const wrapper = mount(PromotionTariffCard, {
      props: { tariff, currency: 'usd' },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Старт')
    expect(wrapper.text()).toContain('9,90 $')
    expect(wrapper.text()).toContain('Поднятие в поиске')

    await wrapper.get('.promotion-card__cta').trigger('click')
    expect(wrapper.emitted('select')?.[0]).toEqual(['basic'])
  })

  it('shows best-value badge for middle tariff', () => {
    const tariff = PROMOTION_TARIFFS[1]
    const wrapper = mount(PromotionTariffCard, {
      props: { tariff, currency: 'byn' },
      global: { plugins: [i18n] },
    })

    expect(wrapper.find('.promotion-card__badge').exists()).toBe(true)
    expect(wrapper.classes()).toContain('promotion-card--popular')
    expect(wrapper.text()).toContain('Выгодный')
    expect(wrapper.text()).toContain('BYN')
  })

  it('uses a tighter card footprint', () => {
    const source = readFileSync(resolve(__dirname, './PromotionTariffCard.vue'), 'utf8')

    expect(source).toContain('max-width: 240px')
    expect(source).toContain('padding: 21px 15px 16px')
    expect(source).toContain('font-size: 21px')
  })
})
