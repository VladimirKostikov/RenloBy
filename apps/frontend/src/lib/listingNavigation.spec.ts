import { describe, expect, it } from 'vitest'
import { listingDetailRouteName } from '@/lib/listingNavigation'

describe('listingDetailRouteName', () => {
  it('maps deal and listing types to detail routes', () => {
    expect(listingDetailRouteName('sale')).toBe('sale-listing-detail')
    expect(listingDetailRouteName('rent')).toBe('rent-listing-detail')
    expect(listingDetailRouteName('sale', 'commercial')).toBe('commercial-listing-detail')
    expect(listingDetailRouteName('rent', 'commercial')).toBe('commercial-listing-detail')
    expect(listingDetailRouteName('sale', 'apartment')).toBe('sale-listing-detail')
  })
})
