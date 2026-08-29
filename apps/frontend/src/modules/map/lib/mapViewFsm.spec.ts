import { describe, expect, it } from 'vitest'
import {
  createCountryView,
  fitMaxZoomForView,
  shouldUseNationwideMarkers,
  viewAfterBack,
  viewAfterCityClick,
  viewAfterRegionClick,
  viewFromCitySlug,
  viewFromListingClick,
  viewFromRegionSlug,
} from '@/modules/map/lib/mapViewFsm'

describe('mapViewFsm', () => {
  it('opens minsk city area from minsk-city region', () => {
    expect(viewAfterRegionClick({
      slug: 'minsk-city',
      name: 'г. Минск',
      level: 'region',
      citySlug: 'minsk',
    })).toEqual({
      mode: 'cities',
      regionSlug: 'minsk-city',
      citySlug: null,
    })
  })

  it('opens oblast cities from regional click', () => {
    expect(viewAfterRegionClick({
      slug: 'gomel',
      name: 'Гомельская область',
      level: 'region',
    })).toEqual({
      mode: 'cities',
      regionSlug: 'gomel',
      citySlug: null,
    })
  })

  it('opens districts from minsk city polygon', () => {
    expect(viewAfterCityClick({
      slug: 'minsk',
      name: 'Минск',
      level: 'city',
      hasDistricts: true,
      regionSlug: 'minsk-city',
    })).toEqual({
      mode: 'districts',
      regionSlug: 'minsk-city',
      citySlug: 'minsk',
    })
  })

  it('returns null for regular city click', () => {
    expect(viewAfterCityClick({
      slug: 'borisov',
      name: 'Борисов',
      level: 'city',
      regionSlug: 'minsk-region',
    })).toBeNull()
  })

  it('goes back from minsk districts to minsk city area', () => {
    expect(viewAfterBack({ mode: 'districts', regionSlug: 'minsk-city', citySlug: 'minsk' }))
      .toEqual({ mode: 'cities', regionSlug: 'minsk-city', citySlug: null })
  })

  it('goes back from selected city to oblast overview', () => {
    expect(viewAfterBack({ mode: 'cities', regionSlug: 'gomel', citySlug: 'gomel-city' }))
      .toEqual({ mode: 'cities', regionSlug: 'gomel', citySlug: null })
  })

  it('goes back to country from oblast overview', () => {
    expect(viewAfterBack({ mode: 'districts', regionSlug: 'gomel', citySlug: 'gomel-city' }))
      .toEqual(createCountryView())
    expect(viewAfterBack({ mode: 'cities', regionSlug: 'gomel', citySlug: null }))
      .toEqual(createCountryView())
  })

  it('resolves city slug to view', () => {
    expect(viewFromCitySlug('minsk')).toEqual({
      mode: 'districts',
      regionSlug: 'minsk-city',
      citySlug: 'minsk',
    })
    expect(viewFromCitySlug('gomel-city')).toEqual({
      mode: 'cities',
      regionSlug: 'gomel',
      citySlug: 'gomel-city',
    })
  })

  it('resolves region slug to oblast cities view', () => {
    expect(viewFromRegionSlug('gomel')).toEqual({
      mode: 'cities',
      regionSlug: 'gomel',
      citySlug: null,
    })
    expect(viewFromRegionSlug('minsk-city')).toEqual({
      mode: 'cities',
      regionSlug: 'minsk-city',
      citySlug: null,
    })
    expect(viewFromRegionSlug('minsk-region')).toEqual({
      mode: 'cities',
      regionSlug: 'minsk-region',
      citySlug: null,
    })
  })

  it('opens listing drill-down into city from any map level', () => {
    expect(viewFromListingClick('country', 'gomel-city')).toEqual({
      mode: 'cities',
      regionSlug: 'gomel',
      citySlug: 'gomel-city',
    })
    expect(viewFromListingClick('cities', 'gomel-city')).toEqual({
      mode: 'cities',
      regionSlug: 'gomel',
      citySlug: 'gomel-city',
    })
    expect(viewFromListingClick('districts', 'minsk')).toEqual({
      mode: 'districts',
      regionSlug: 'minsk-city',
      citySlug: 'minsk',
    })
  })

  it('derives marker scope and fit zoom from view', () => {
    expect(shouldUseNationwideMarkers(createCountryView())).toBe(true)
    expect(shouldUseNationwideMarkers({ mode: 'cities', regionSlug: 'gomel', citySlug: null }))
      .toBe(false)
    expect(shouldUseNationwideMarkers({ mode: 'districts', regionSlug: 'minsk-city', citySlug: 'minsk' }))
      .toBe(false)
    expect(fitMaxZoomForView(createCountryView())).toBe(7)
    expect(fitMaxZoomForView({ mode: 'districts', regionSlug: 'minsk-city', citySlug: 'minsk' })).toBe(13)
  })
})
