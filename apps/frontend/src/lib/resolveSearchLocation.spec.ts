import { describe, expect, it } from 'vitest'
import { resolveSearchLocation } from '@/lib/resolveSearchLocation'

describe('resolveSearchLocation', () => {
  const cities = [
    { id: 1, name: 'Минск', slug: 'minsk', regionSlug: 'minsk-city' },
    { id: 2, name: 'Гомель', slug: 'gomel-city', regionSlug: 'gomel' },
  ]

  const regionLabel = (slug: string) => {
    if (slug === 'gomel') {
      return 'Гомельская область'
    }
    if (slug === 'minsk-city') {
      return 'г. Минск'
    }
    return slug
  }

  it('resolves region by label', () => {
    expect(resolveSearchLocation({
      query: 'Гомельская',
      cities,
      regionLabel,
    })).toEqual({
      kind: 'region',
      regionSlug: 'gomel',
      label: 'Гомельская область',
    })
  })

  it('resolves city by name', () => {
    expect(resolveSearchLocation({
      query: 'минск',
      cities,
      regionLabel,
    })).toEqual({
      kind: 'city',
      cityId: 1,
      regionSlug: 'minsk-city',
      label: 'Минск',
    })
  })

  it('resolves district when provided', () => {
    expect(resolveSearchLocation({
      query: 'Центральный',
      cities,
      districts: [{ id: 10, name: 'Центральный', slug: 'central', cityId: 1 }],
      regionLabel,
    })).toEqual({
      kind: 'district',
      cityId: 1,
      districtId: 10,
      regionSlug: 'minsk-city',
      label: 'Центральный',
    })
  })

  it('returns null for unknown query', () => {
    expect(resolveSearchLocation({
      query: 'улица Независимости 10',
      cities,
      regionLabel,
    })).toBeNull()
  })
})
