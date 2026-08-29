import type { MapViewState, MapZoneProperties } from '@/types/map'
import { CITY_TO_REGION } from '@/lib/mapManifest'

export function createCountryView(): MapViewState {
  return { mode: 'country', regionSlug: null, citySlug: null }
}

export function mapViewsEqual(a: MapViewState, b: MapViewState): boolean {
  return a.mode === b.mode && a.regionSlug === b.regionSlug && a.citySlug === b.citySlug
}

export function isMinskCityZone(props: MapZoneProperties): boolean {
  return props.slug === 'minsk-city' || props.citySlug === 'minsk' || props.slug === 'minsk'
}

export function viewAfterRegionClick(props: MapZoneProperties): MapViewState {
  if (isMinskCityZone(props)) {
    return { mode: 'cities', regionSlug: 'minsk-city', citySlug: null }
  }

  return { mode: 'cities', regionSlug: props.slug, citySlug: null }
}

export function viewAfterCityClick(props: MapZoneProperties): MapViewState | null {
  const citySlug = props.citySlug ?? props.slug

  if (props.hasDistricts || citySlug === 'minsk') {
    return { mode: 'districts', regionSlug: 'minsk-city', citySlug: 'minsk' }
  }

  return null
}

export function viewAfterBack(current: MapViewState): MapViewState {
  if (current.mode === 'districts' && current.regionSlug === 'minsk-city') {
    return { mode: 'cities', regionSlug: 'minsk-city', citySlug: null }
  }

  if (current.mode === 'cities' && current.citySlug && current.regionSlug) {
    return { mode: 'cities', regionSlug: current.regionSlug, citySlug: null }
  }

  if (current.mode === 'districts' || current.mode === 'cities') {
    return createCountryView()
  }

  return current
}

export function viewFromCitySlug(citySlug: string): MapViewState | null {
  if (citySlug === 'minsk') {
    return { mode: 'districts', regionSlug: 'minsk-city', citySlug: 'minsk' }
  }

  const regionSlug = CITY_TO_REGION[citySlug]
  if (!regionSlug) {
    return null
  }

  return { mode: 'cities', regionSlug, citySlug }
}

export function viewFromRegionSlug(regionSlug: string): MapViewState {
  if (regionSlug === 'minsk-city') {
    return { mode: 'cities', regionSlug: 'minsk-city', citySlug: null }
  }

  return { mode: 'cities', regionSlug, citySlug: null }
}

export function viewFromListingClick(_currentMode: MapViewState['mode'], citySlug: string): MapViewState | null {
  return viewFromCitySlug(citySlug)
}

export function shouldUseNationwideMarkers(view: MapViewState): boolean {
  return view.mode === 'country'
}

export function fitMaxZoomForView(view: MapViewState): number {
  if (view.mode === 'districts') {
    return 13
  }

  if (view.mode === 'cities') {
    return 11
  }

  return 7
}

export function breadcrumbKey(view: MapViewState): string {
  if (view.mode === 'districts') {
    return 'map.breadcrumb.districts'
  }

  if (view.mode === 'cities' && view.regionSlug) {
    return `map.regions.${view.regionSlug}`
  }

  return 'map.breadcrumb.belarus'
}
