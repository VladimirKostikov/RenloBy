import { describe, expect, it } from 'vitest'
import { resolveLocationPageHref } from '@/modules/location/lib/locationPageHref'

describe('resolveLocationPageHref', () => {
  it('prefers district page when city and district are set', () => {
    expect(
      resolveLocationPageHref({
        regionSlug: 'minsk-city',
        citySlug: 'minsk',
        districtSlug: 'centralny',
      }),
    ).toBe('/city/minsk/centralny')
  })

  it('returns city page when only city is set', () => {
    expect(
      resolveLocationPageHref({
        regionSlug: 'gomel',
        citySlug: 'gomel-city',
      }),
    ).toBe('/city/gomel-city')
  })

  it('returns region page when only region is set', () => {
    expect(resolveLocationPageHref({ regionSlug: 'brest' })).toBe('/region/brest')
  })

  it('returns null without selection', () => {
    expect(resolveLocationPageHref({})).toBeNull()
  })

  it('returns null for unknown region slug', () => {
    expect(resolveLocationPageHref({ regionSlug: 'unknown' })).toBeNull()
  })
})
