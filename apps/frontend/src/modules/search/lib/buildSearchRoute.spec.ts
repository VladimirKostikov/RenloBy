import { describe, expect, it } from 'vitest'
import { buildSearchMapLocation, isExtendedFiltersOpen } from '@/modules/search/lib/buildSearchRoute'

describe('buildSearchRoute', () => {
  it('builds search map path without query', () => {
    expect(buildSearchMapLocation()).toBe('/search')
  })

  it('builds search map path with extended panel', () => {
    expect(buildSearchMapLocation({ panel: 'extended' })).toBe('/search?panel=extended')
  })

  it('detects extended filters query', () => {
    expect(isExtendedFiltersOpen('extended')).toBe(true)
    expect(isExtendedFiltersOpen(undefined)).toBe(false)
  })
})
