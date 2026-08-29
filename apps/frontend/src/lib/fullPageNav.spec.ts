import { describe, expect, it } from 'vitest'
import {
  catalogPath,
  catalogPathFromRouteName,
  listingDetailPath,
  listingPath,
} from '@/lib/fullPageNav'

describe('fullPageNav', () => {
  it('builds catalog paths', () => {
    expect(catalogPath('sale')).toBe('/sale')
    expect(catalogPath('rent')).toBe('/rent')
    expect(catalogPath('commercial')).toBe('/commercial')
  })

  it('maps route names to catalog paths', () => {
    expect(catalogPathFromRouteName('sale-catalog')).toBe('/sale')
    expect(catalogPathFromRouteName('rent-listing-detail')).toBe('/rent')
    expect(catalogPathFromRouteName('home')).toBe('/')
  })

  it('builds listing detail paths', () => {
    expect(listingPath(12)).toBe('/listings/12')
    expect(listingDetailPath(5, {
      detailRouteName: 'city-listing-detail',
      citySlug: 'minsk',
    })).toBe('/city/minsk/listings/5')
    expect(listingDetailPath(7, {
      detailRouteName: 'region-listing-detail',
      regionSlug: 'gomel',
    })).toBe('/region/gomel/listings/7')
    expect(listingDetailPath(9, {
      detailRouteName: 'sale-listing-detail',
    })).toBe('/sale/listings/9')
    expect(listingDetailPath(3, {
      detailRouteName: 'search-listing-detail',
    })).toBe('/search/listings/3')
  })
})
