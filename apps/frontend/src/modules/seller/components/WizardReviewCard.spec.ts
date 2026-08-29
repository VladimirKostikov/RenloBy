import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import WizardReviewCard from '@/modules/seller/components/WizardReviewCard.vue'
import { createEmptyWizardDraft } from '@/modules/seller/lib/listingWizard'
import ru from '@/locales/ru.json'

describe('WizardReviewCard', () => {
  it('renders media gallery price and structured details', () => {
    const draft = createEmptyWizardDraft()
    draft.dealType = 'rent'
    draft.listingType = 'apartment'
    draft.rentTerm = 'long'
    draft.city = 'Минск'
    draft.district = 'Центральный'
    draft.street = 'Интернациональная улица'
    draft.house = '28'
    draft.price = 100
    draft.priceByn = 327
    draft.rooms = 1
    draft.area = 42
    draft.floor = 3
    draft.totalFloors = 9
    draft.priceNegotiable = true
    draft.fromOwner = true
    draft.images = [
      '/uploads/listings/2026/07/a.jpg',
      '/uploads/listings/2026/07/b.jpg',
      'javascript:alert(1)',
    ]

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardReviewCard, {
      props: { draft },
      global: { plugins: [i18n] },
    })

    expect(wrapper.findAll('.wizard-review__hero img')).toHaveLength(1)
    expect(wrapper.findAll('.wizard-review__thumb img')).toHaveLength(1)
    expect(wrapper.text()).toContain('2 фото')
    expect(wrapper.text()).toContain('Интернациональная улица, 28')
    expect(wrapper.text()).toContain('Минск · Центральный')
    expect(wrapper.text()).toContain('Аренда')
    expect(wrapper.text()).toContain('Квартира')
    expect(wrapper.text()).toContain('Длительно')
    expect(wrapper.text()).toContain('327 BYN')
    expect(wrapper.text()).toContain('100 $')
    expect(wrapper.text()).toContain('3/9')
    expect(wrapper.text()).toContain('Возможно обсуждение')
    expect(wrapper.text()).toContain('Собственник')
    expect(wrapper.html()).not.toContain('javascript:alert(1)')
  })

  it('shows empty media state without images', () => {
    const draft = createEmptyWizardDraft()
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardReviewCard, {
      props: { draft },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Фото пока нет')
    expect(wrapper.find('.wizard-review__hero').exists()).toBe(false)
  })
})
