import { describe, expect, it } from 'vitest'
import { formatListingOfferType, listingDealTypeKey, listingPropertyTypeKey } from '@/lib/listingOfferType'

describe('listingOfferType', () => {
  const translate = (key: string) => {
    const map: Record<string, string> = {
      'dealType.sale': 'Продажа',
      'dealType.rent': 'Аренда',
      'listingType.apartment': 'Квартира',
      'listingType.house': 'Дом',
      'listingType.room': 'Комната',
      'listingType.commercial': 'Бизнес',
    }
    return map[key] ?? key
  }

  it('builds deal and property type keys', () => {
    expect(listingDealTypeKey('rent')).toBe('dealType.rent')
    expect(listingPropertyTypeKey('commercial')).toBe('listingType.commercial')
  })

  it('formats offer type label', () => {
    expect(formatListingOfferType({ dealType: 'rent', listingType: 'apartment' }, translate))
      .toBe('Аренда · Квартира')
    expect(formatListingOfferType({ dealType: 'sale', listingType: 'commercial' }, translate))
      .toBe('Продажа · Бизнес')
    expect(formatListingOfferType({ dealType: 'sale', listingType: 'house' }, translate))
      .toBe('Продажа · Дом')
  })
})
