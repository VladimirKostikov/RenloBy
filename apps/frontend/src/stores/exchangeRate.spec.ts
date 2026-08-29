import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useExchangeRateStore } from '@/stores/exchangeRate'
import { getUsdToBynRate, resetUsdToBynRate } from '@/lib/formatPrice'

vi.mock('@/api/exchangeRates', () => ({
  fetchExchangeRates: vi.fn().mockResolvedValue({
    usdToByn: 3.5,
    usdToRub: 95,
    source: 'nbrb',
    updatedAt: '2026-07-16T00:00:00+00:00',
  }),
}))

describe('exchangeRate store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    resetUsdToBynRate()
  })

  it('loads nbrb rate into formatPrice runtime rate', async () => {
    const store = useExchangeRateStore()
    await store.load()

    expect(store.usdToByn).toBe(3.5)
    expect(store.source).toBe('nbrb')
    expect(getUsdToBynRate()).toBe(3.5)
    expect(store.rateLabel).toContain('3,5')
  })
})
