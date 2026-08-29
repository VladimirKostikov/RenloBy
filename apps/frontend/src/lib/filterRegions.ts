export const FILTER_REGION_SLUGS = [
  'minsk-city',
  'minsk-region',
  'brest',
  'vitebsk',
  'gomel',
  'grodno',
  'mogilev',
] as const

export type FilterRegionSlug = (typeof FILTER_REGION_SLUGS)[number]

export function isFilterRegionSlug(value: string): value is FilterRegionSlug {
  return (FILTER_REGION_SLUGS as readonly string[]).includes(value)
}

export function filterCitiesByRegionSlug<T extends { regionSlug: string }>(
  cities: T[],
  regionSlug: string | undefined,
): T[] {
  if (!regionSlug) {
    return cities
  }

  return cities.filter((city) => city.regionSlug === regionSlug)
}
