import { describe, expect, it } from 'vitest'
import { shouldKeepZonesAboveMarkers } from '@/modules/map/lib/mapLayerOrder'

describe('shouldKeepZonesAboveMarkers', () => {
  it('keeps overview clusters above country and oblast polygons', () => {
    expect(shouldKeepZonesAboveMarkers('country', null, null)).toBe(false)
    expect(shouldKeepZonesAboveMarkers('cities', null, null)).toBe(false)
    expect(shouldKeepZonesAboveMarkers('districts', 'minsk', null)).toBe(true)
  })

  it('keeps markers above zones after drilling into a city or district', () => {
    expect(shouldKeepZonesAboveMarkers('cities', 'gomel-city', null)).toBe(false)
    expect(shouldKeepZonesAboveMarkers('districts', 'minsk', 'centralny')).toBe(false)
  })
})
