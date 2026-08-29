import type { DealType } from '@/types'
import { REGION_CITY_SLUGS, MAP_GEO_URLS, DISTRICT_GEO_URLS } from '@/lib/mapManifest'

export type MapZoneLevel = 'region' | 'city' | 'district'

export interface MapZoneProperties {
  slug: string
  name: string
  level: MapZoneLevel
  regionSlug?: string
  citySlug?: string
  districtSlug?: string
  hasDistricts?: boolean
}

export interface MapZoneStats {
  count: number
  avgPrice: number
  avgPricePerSqm: number
}

export interface MapZoneStatsItem {
  id: number
  count: number
  avgPrice: number
  avgPricePerSqm: number
}

export interface MapStatsResponse {
  cities: MapZoneStatsItem[]
  districts: MapZoneStatsItem[]
}

export interface ZoneTooltipData {
  name: string
  count: number
  avgPrice: number
  avgPricePerSqm: number
  x: number
  y: number
}

export type MapViewMode = 'country' | 'cities' | 'districts'

export interface MapViewState {
  mode: MapViewMode
  regionSlug: string | null
  citySlug: string | null
}

export interface MapStatsParams {
  dealType?: DealType
}

export const BELARUS_BOUNDS: [[number, number], [number, number]] = [[51.2, 23.0], [56.3, 32.8]]

export { REGION_CITY_SLUGS, MAP_GEO_URLS, DISTRICT_GEO_URLS }
