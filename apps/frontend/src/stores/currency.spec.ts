import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { DEFAULT_CURRENCY, useCurrencyStore } from '@/stores/currency'

describe('currency store', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('defaults to BYN when nothing is stored', () => {
    const currency = useCurrencyStore()
    expect(currency.code).toBe(DEFAULT_CURRENCY)
    expect(currency.code).toBe('byn')
  })

  it('defaults to BYN for invalid stored value', () => {
    localStorage.setItem('renlo-currency', 'eur')
    setActivePinia(createPinia())
    const currency = useCurrencyStore()
    expect(currency.code).toBe('byn')
  })

  it('keeps stored USD preference', () => {
    localStorage.setItem('renlo-currency', 'usd')
    setActivePinia(createPinia())
    const currency = useCurrencyStore()
    expect(currency.code).toBe('usd')
  })

  it('switches currency and persists', () => {
    const currency = useCurrencyStore()
    currency.setCurrency('usd')
    expect(currency.code).toBe('usd')
    expect(localStorage.getItem('renlo-currency')).toBe('usd')

    currency.toggleCurrency()
    expect(currency.code).toBe('byn')
    expect(localStorage.getItem('renlo-currency')).toBe('byn')
  })
})
