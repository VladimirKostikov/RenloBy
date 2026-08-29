import type { MapViewMode } from '@/types/map'
import { shouldKeepZonesAboveMarkers } from '@/modules/map/lib/mapLayerOrder'

export interface ZoneFillStyle {
  fillColor: string
  fillOpacity: number
}

export interface ZoneBorderStyle {
  color: string
  weight: number
  opacity: number
}

export function createZoneFillStyle(
  mode: MapViewMode,
  slug: string,
  hoveredSlug: string | null,
  selectedSlug: string | null,
): ZoneFillStyle {
  if (mode === 'cities') {
    const isActive = selectedSlug === slug || hoveredSlug === slug
    return {
      fillColor: isActive ? '#ef4444' : '#2563eb',
      fillOpacity: 0,
    }
  }

  if (selectedSlug === slug) {
    return {
      fillColor: '#ef4444',
      fillOpacity: 0,
    }
  }

  if (mode === 'districts' && selectedSlug !== null) {
    return {
      fillColor: '#2563eb',
      fillOpacity: 0,
    }
  }

  const isHovered = hoveredSlug === slug

  return {
    fillColor: isHovered ? '#ef4444' : '#2563eb',
    fillOpacity: isHovered ? 0.28 : mode === 'districts' ? 0.18 : 0.16,
  }
}

export function createZoneBorderStyle(
  mode: MapViewMode,
  slug: string,
  hoveredSlug: string | null,
  selectedSlug: string | null,
): ZoneBorderStyle {
  if (selectedSlug === slug) {
    return {
      color: '#dc2626',
      weight: 3,
      opacity: 1,
    }
  }

  if (mode === 'districts' && selectedSlug !== null) {
    return {
      color: '#1e3a8a',
      weight: 1.5,
      opacity: 0,
    }
  }

  const isHovered = hoveredSlug === slug

  return {
    color: isHovered ? '#dc2626' : '#1e3a8a',
    weight: isHovered ? 2.5 : mode === 'districts' ? 1.5 : 2.5,
    opacity: 1,
  }
}

export const cityOutlineStyle: ZoneBorderStyle = {
  color: '#1e40af',
  weight: 2,
  opacity: 0.85,
}

export function createRegionOutlineStyle(): ZoneFillStyle & ZoneBorderStyle {
  return {
    fillColor: '#2563eb',
    fillOpacity: 0,
    color: '#dc2626',
    weight: 3,
    opacity: 1,
  }
}

export function shouldShowZonePolygons(
  mode: MapViewMode,
  citySlug: string | null,
  _selectedDistrictSlug: string | null = null,
): boolean {
  if (mode === 'cities' && citySlug !== null) {
    return false
  }

  return true
}

export function shouldShowListingMarkers(
  mode: MapViewMode,
  cityId: number | undefined,
  selectedDistrictSlug: string | null = null,
): boolean {
  if (mode === 'districts') {
    return selectedDistrictSlug !== null || cityId !== undefined
  }

  if (mode === 'cities') {
    return true
  }

  if (mode === 'country' && cityId === undefined) {
    return true
  }

  return false
}

export function zoneZIndexForView(
  mode: MapViewMode,
  selectedCitySlug: string | null,
  selectedDistrictSlug: string | null = null,
): number {
  return shouldKeepZonesAboveMarkers(mode, selectedCitySlug, selectedDistrictSlug) ? 700 : 200
}

export function markerZIndexForView(
  mode: MapViewMode,
  selectedCitySlug: string | null,
  selectedDistrictSlug: string | null = null,
): number {
  return shouldKeepZonesAboveMarkers(mode, selectedCitySlug, selectedDistrictSlug) ? 400 : 700
}

export function clusterOptionsForView(
  mode: MapViewMode,
  selectedSlug: string | null,
): { gridSize: number } {
  if (mode === 'districts') {
    return {
      gridSize: selectedSlug ? 32 : 64,
    }
  }

  return { gridSize: 64 }
}
