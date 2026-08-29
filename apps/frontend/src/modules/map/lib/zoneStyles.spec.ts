import { describe, expect, it } from 'vitest'
import {
  createRegionOutlineStyle,
  clusterOptionsForView,
  createZoneBorderStyle,
  createZoneFillStyle,
  markerZIndexForView,
  shouldShowListingMarkers,
  shouldShowZonePolygons,
  zoneZIndexForView,
} from '@/modules/map/lib/zoneStyles'

describe('shouldShowZonePolygons', () => {
  it('shows zones on country and oblast overview', () => {
    expect(shouldShowZonePolygons('country', null, null)).toBe(true)
    expect(shouldShowZonePolygons('cities', null, null)).toBe(true)
    expect(shouldShowZonePolygons('districts', 'minsk', null)).toBe(true)
  })

  it('hides custom city polygons when a city is selected', () => {
    expect(shouldShowZonePolygons('cities', 'gomel-city', null)).toBe(false)
  })

  it('keeps selected district outline after zoom', () => {
    expect(shouldShowZonePolygons('districts', 'minsk', 'centralny')).toBe(true)
  })
})

describe('clusterOptionsForView', () => {
  it('always returns a power-of-two Yandex grid size', () => {
    const options = [
      clusterOptionsForView('country', null),
      clusterOptionsForView('cities', null),
      clusterOptionsForView('districts', null),
      clusterOptionsForView('districts', 'centralny'),
    ]

    for (const { gridSize } of options) {
      expect(gridSize > 0 && (gridSize & (gridSize - 1)) === 0).toBe(true)
    }
  })
})

describe('selected district outline styles', () => {
  it('shows district borders before interaction', () => {
    const fill = createZoneFillStyle('districts', 'centralny', null, null)
    const border = createZoneBorderStyle('districts', 'centralny', null, null)
    expect(fill.fillOpacity).toBeGreaterThan(0)
    expect(border.opacity).toBe(1)
  })

  it('does not fill the selected district', () => {
    const fill = createZoneFillStyle('districts', 'centralny', null, 'centralny')
    expect(fill.fillOpacity).toBe(0)
  })

  it('uses a visible border for the selected district', () => {
    const border = createZoneBorderStyle('districts', 'centralny', null, 'centralny')
    expect(border.opacity).toBe(1)
    expect(border.weight).toBeGreaterThanOrEqual(3)
  })

  it('hides fill and stroke for non-selected districts after selection', () => {
    const fill = createZoneFillStyle('districts', 'sovetsky', null, 'centralny')
    const border = createZoneBorderStyle('districts', 'sovetsky', null, 'centralny')
    expect(fill.fillOpacity).toBe(0)
    expect(border.opacity).toBe(0)
  })
})

describe('city zone styles', () => {
  it('uses a visible border for cities on oblast overview', () => {
    const border = createZoneBorderStyle('cities', 'gomel-city', null, null)
    expect(border.weight).toBeGreaterThanOrEqual(2.5)
    expect(border.opacity).toBe(1)
  })

  it('does not fill cities on oblast overview', () => {
    const fill = createZoneFillStyle('cities', 'gomel-city', null, null)
    expect(fill.fillOpacity).toBe(0)
  })

  it('highlights the selected city with border only', () => {
    const fill = createZoneFillStyle('cities', 'gomel-city', null, 'gomel-city')
    const border = createZoneBorderStyle('cities', 'gomel-city', null, 'gomel-city')
    expect(fill.fillOpacity).toBe(0)
    expect(border.weight).toBeGreaterThanOrEqual(3)
    expect(border.opacity).toBe(1)
  })
})

describe('region outline styles', () => {
  it('highlights the selected oblast with border only', () => {
    const style = createRegionOutlineStyle()
    expect(style.fillOpacity).toBe(0)
    expect(style.opacity).toBe(1)
    expect(style.weight).toBeGreaterThanOrEqual(3)
  })
})

describe('shouldShowListingMarkers', () => {
  it('shows nationwide markers on country view without city filter', () => {
    expect(shouldShowListingMarkers('country', undefined)).toBe(true)
  })

  it('shows markers on oblast view without city filter', () => {
    expect(shouldShowListingMarkers('cities', undefined)).toBe(true)
  })

  it('shows markers in districts mode when city is selected without district', () => {
    expect(shouldShowListingMarkers('districts', 1, null)).toBe(true)
  })

  it('shows markers in districts mode after district is selected', () => {
    expect(shouldShowListingMarkers('districts', 1, 'centralny')).toBe(true)
  })

  it('hides markers in districts mode without city or district', () => {
    expect(shouldShowListingMarkers('districts', undefined, null)).toBe(false)
  })

  it('hides markers on country view when city filter is active', () => {
    expect(shouldShowListingMarkers('country', 1)).toBe(false)
  })
})

describe('map layer z-index', () => {
  it('keeps markers above zones on country and oblast overview', () => {
    expect(markerZIndexForView('country', null)).toBeGreaterThan(zoneZIndexForView('country', null))
    expect(markerZIndexForView('cities', null)).toBeGreaterThan(zoneZIndexForView('cities', null))
  })

  it('keeps zones above markers on minsk districts overview', () => {
    expect(zoneZIndexForView('districts', 'minsk', null))
      .toBeGreaterThan(markerZIndexForView('districts', 'minsk', null))
  })

  it('keeps markers above zones when district is selected', () => {
    expect(markerZIndexForView('districts', 'minsk', 'centralny'))
      .toBeGreaterThan(zoneZIndexForView('districts', 'minsk', 'centralny'))
  })

  it('keeps markers above zones when city is selected in oblast view', () => {
    expect(markerZIndexForView('cities', 'gomel-city'))
      .toBeGreaterThan(zoneZIndexForView('cities', 'gomel-city'))
  })
})
