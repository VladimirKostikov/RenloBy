import type { MapViewMode } from '@/types/map'

export function shouldKeepZonesAboveMarkers(
  mode: MapViewMode,
  _citySlug: string | null,
  selectedDistrictSlug: string | null,
): boolean {
  if (mode === 'districts' && !selectedDistrictSlug) {
    return true
  }

  return false
}
