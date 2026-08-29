import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import CurrencyAmount from '@/components/CurrencyAmount.vue'

describe('CurrencyAmount', () => {
  it('shows BYN primary and USD secondary under it', () => {
    const wrapper = mount(CurrencyAmount, {
      props: { amountUsd: 100 },
    })

    expect(wrapper.find('.currency-amount__primary').text()).toBe('327\u00a0BYN')
    expect(wrapper.find('.currency-amount__secondary').text()).toBe('100\u00a0$')
  })

  it('formats per sqm in both currencies', () => {
    const wrapper = mount(CurrencyAmount, {
      props: { amountUsd: 1200, variant: 'perSqm' },
    })

    expect(wrapper.find('.currency-amount__primary').text()).toContain('BYN/м²')
    expect(wrapper.find('.currency-amount__secondary').text()).toContain('$/м²')
  })

  it('keeps amount and currency on one line in primary', () => {
    const wrapper = mount(CurrencyAmount, {
      props: { amountUsd: 58_000 },
    })

    expect(wrapper.find('.currency-amount__primary').text()).toBe('189 660\u00a0BYN')
    expect(wrapper.find('.currency-amount__secondary').text()).toBe('58 000\u00a0$')
  })
})
