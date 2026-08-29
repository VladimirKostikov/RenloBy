import { afterEach, describe, expect, it } from 'vitest'
import {
  convertFromUsd,
  convertToUsd,
  formatListingPrice,
  formatListingPricePerSqm,
  formatMarkerPrice,
  resetUsdToBynRate,
  setUsdToBynRate,
} from '@/lib/formatPrice'

describe('formatPrice', () => {
  afterEach(() => {
    resetUsdToBynRate()
  })

  it('formats full usd prices without thousand abbreviation', () => {
    expect(formatListingPrice(850, 'usd')).toBe('850\u00a0$')
    expect(formatListingPrice(125_000, 'usd')).toBe('125 000\u00a0$')
  })

  it('converts usd to byn for display by default', () => {
    expect(convertFromUsd(100)).toBe(327)
    expect(formatListingPrice(100)).toBe('327\u00a0BYN')
    expect(formatListingPrice(58_000)).toBe('189 660\u00a0BYN')
  })

  it('applies runtime usd to byn rate', () => {
    setUsdToBynRate(3.5)
    expect(convertFromUsd(100)).toBe(350)
    expect(convertToUsd(350)).toBe(100)
  })

  it('converts display currency back to usd for api filters', () => {
    expect(convertToUsd(327)).toBe(100)
    expect(convertToUsd(150_000, 'usd')).toBe(150_000)
  })

  it('formats price per sqm with currency suffix', () => {
    expect(formatListingPricePerSqm(1200, 'usd')).toContain('$/м²')
    expect(formatListingPricePerSqm(1200)).toContain('BYN/м²')
  })

  it('uses full BYN formatter for marker tooltip', () => {
    expect(formatMarkerPrice(50_000)).toBe(formatListingPrice(50_000, 'byn'))
    expect(formatMarkerPrice(50_000)).toBe('163 500\u00a0BYN')
  })
})
