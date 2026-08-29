import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingVerifiedBadge from '@/components/ListingVerifiedBadge.vue'
import ru from '@/locales/ru.json'

describe('ListingVerifiedBadge', () => {
  it('renders verified label', () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(ListingVerifiedBadge, {
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Проверено')
    expect(wrapper.find('img').exists()).toBe(true)
  })
})
