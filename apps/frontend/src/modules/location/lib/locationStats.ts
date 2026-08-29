import { buildCityStatsMap, buildDistrictStatsMap, emptyStats, aggregateStats } from '@/lib/mapZones'
import { filterCitiesByRegionSlug } from '@/lib/filterRegions'
import type { CityDto, DistrictDto } from '@/types'
import type { MapStatsResponse, MapZoneStats } from '@/types/map'

export function getCityStatsFromResponse(cityId: number, response: MapStatsResponse): MapZoneStats {
  const map = buildCityStatsMap(response.cities)
  return map.get(cityId) ?? emptyStats()
}

export function getDistrictStatsFromResponse(
  districtId: number,
  response: MapStatsResponse,
): MapZoneStats {
  const map = buildDistrictStatsMap(response.districts)
  return map.get(districtId) ?? emptyStats()
}

export function getRegionStatsFromResponse(
  cities: CityDto[],
  regionSlug: string,
  response: MapStatsResponse,
): MapZoneStats {
  const statsMap = buildCityStatsMap(response.cities)
  const regionCities = filterCitiesByRegionSlug(cities, regionSlug)
  const rows = regionCities.map((city) => statsMap.get(city.id) ?? emptyStats())

  return aggregateStats(rows)
}

export interface DistrictWithStats {
  district: DistrictDto
  stats: MapZoneStats
}

export interface CityWithStats {
  city: CityDto
  stats: MapZoneStats
}

export function buildDistrictCards(
  districts: DistrictDto[],
  cityId: number,
  response: MapStatsResponse,
): DistrictWithStats[] {
  const statsMap = buildDistrictStatsMap(response.districts)

  return districts
    .filter((district) => district.cityId === cityId)
    .map((district) => ({
      district,
      stats: statsMap.get(district.id) ?? emptyStats(),
    }))
    .sort((left, right) => left.district.name.localeCompare(right.district.name, 'ru'))
}

export function buildCityCards(
  cities: CityDto[],
  regionSlug: string,
  response: MapStatsResponse,
): CityWithStats[] {
  const statsMap = buildCityStatsMap(response.cities)

  return filterCitiesByRegionSlug(cities, regionSlug)
    .map((city) => ({
      city,
      stats: statsMap.get(city.id) ?? emptyStats(),
    }))
    .sort((left, right) => left.city.name.localeCompare(right.city.name, 'ru'))
}
