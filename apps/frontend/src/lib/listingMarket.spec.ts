import { describe, expect, it } from 'vitest'
import { formatMarketChangePct, marketRankPercent } from '@/lib/listingMarket'

describe('listingMarket', () => {
  it('formats change percent with sign', () => {
    expect(formatMarketChangePct(-4.2)).toBe('-4.2%')
    expect(formatMarketChangePct(3)).toBe('+3%')
    expect(formatMarketChangePct(0)).toBe('0%')
  })

  it('maps rank to bar width percent', () => {
    expect(marketRankPercent(1, 10)).toBe(10)
    expect(marketRankPercent(10, 10)).toBe(100)
    expect(marketRankPercent(1, 0)).toBe(100)
  })
})
