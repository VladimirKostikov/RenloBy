import { describe, expect, it } from 'vitest'
import {
  PROMOTION_TARIFF_DEFAULTS,
  convertTariffAmount,
  findPromotionTariff,
  formatTariffPrice,
  getPopularPromotionTariff,
  mergeTariffPrices,
  paymentCurrencyApiCode,
} from '@/modules/promotion/lib/promotionTariffs'

describe('promotionTariffs', () => {
  it('has three affordable default tariffs', () => {
    expect(PROMOTION_TARIFF_DEFAULTS).toHaveLength(3)
    expect(PROMOTION_TARIFF_DEFAULTS.map((tariff) => tariff.id)).toEqual(['basic', 'standard', 'premium'])
    expect(PROMOTION_TARIFF_DEFAULTS[0].priceUsd).toBe(9.9)
    expect(PROMOTION_TARIFF_DEFAULTS[1].priceUsd).toBe(19.9)
    expect(PROMOTION_TARIFF_DEFAULTS[2].priceUsd).toBe(34.9)
  })

  it('marks middle tariff as best value', () => {
    expect(PROMOTION_TARIFF_DEFAULTS.filter((tariff) => tariff.popular)).toHaveLength(1)
    expect(getPopularPromotionTariff()?.id).toBe('standard')
  })

  it('converts prices to byn rub and usd', () => {
    expect(convertTariffAmount(9.9, 'byn')).toBe(32)
    expect(convertTariffAmount(9.9, 'rub')).toBe(920)
    expect(convertTariffAmount(9.9, 'usd')).toBe(9.9)
    expect(formatTariffPrice(9.9, 'byn')).toContain('BYN')
    expect(formatTariffPrice(9.9, 'rub')).toContain('₽')
    expect(paymentCurrencyApiCode('byn')).toBe('BYN')
  })

  it('finds tariff by id', () => {
    expect(findPromotionTariff('premium')?.priceUsd).toBe(34.9)
  })

  it('merges remote multi-currency prices into defaults', () => {
    const merged = mergeTariffPrices(PROMOTION_TARIFF_DEFAULTS, [
      { code: 'basic', priceUsd: '11.00', priceByn: '36.00', priceRub: '1020.00', isPopular: true },
      { code: 'standard', priceUsd: '19.90', isPopular: false },
    ])
    expect(merged[0].priceUsd).toBe(11)
    expect(merged[0].priceByn).toBe(36)
    expect(merged[0].priceRub).toBe(1020)
    expect(merged[0].popular).toBe(true)
    expect(merged[1].popular).toBe(false)
    expect(merged[2].priceUsd).toBe(34.9)
  })

  it('uses dedicated tariff icons', () => {
    expect(PROMOTION_TARIFF_DEFAULTS.map((tariff) => tariff.icon)).toEqual([
      '/figma/tariff-start.svg',
      '/figma/tariff-optimum.svg',
      '/figma/tariff-maximum.svg',
    ])
  })
})
