import { isFilterRegionSlug, type FilterRegionSlug } from '@/lib/filterRegions'
import type { CityDto, DistrictDto } from '@/types'

export type LocationKind = 'region' | 'city' | 'district'

export type ResolvedLocation =
  | { kind: 'region'; regionSlug: FilterRegionSlug }
  | { kind: 'city'; city: CityDto }
  | { kind: 'district'; city: CityDto; district: DistrictDto }

export function resolveCityBySlug(cities: CityDto[], citySlug: string): CityDto | undefined {
  return cities.find((city) => city.slug === citySlug)
}

export function resolveDistrictBySlug(
  districts: DistrictDto[],
  cityId: number,
  districtSlug: string,
): DistrictDto | undefined {
  return districts.find((district) => district.slug === districtSlug && district.cityId === cityId)
}

export function resolveRegionBySlug(regionSlug: string): FilterRegionSlug | null {
  if (!isFilterRegionSlug(regionSlug)) {
    return null
  }

  return regionSlug
}

export function resolveLocation(
  cities: CityDto[],
  districts: DistrictDto[],
  citySlug: string,
  districtSlug?: string,
): ResolvedLocation | null {
  const city = resolveCityBySlug(cities, citySlug)
  if (!city) {
    return null
  }

  if (!districtSlug) {
    return { kind: 'city', city }
  }

  const district = resolveDistrictBySlug(districts, city.id, districtSlug)
  if (!district) {
    return null
  }

  return { kind: 'district', city, district }
}

export function resolveRegionLocation(regionSlug: string): ResolvedLocation | null {
  const resolved = resolveRegionBySlug(regionSlug)
  if (!resolved) {
    return null
  }

  return { kind: 'region', regionSlug: resolved }
}

export function cityHasDistricts(districts: DistrictDto[], cityId: number): boolean {
  return districts.some((district) => district.cityId === cityId)
}
