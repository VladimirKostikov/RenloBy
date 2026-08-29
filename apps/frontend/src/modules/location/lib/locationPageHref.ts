import { cityPath, districtPath, regionPath } from '@/lib/fullPageNav'
import { isFilterRegionSlug } from '@/lib/filterRegions'

export function resolveLocationPageHref(input: {
  regionSlug?: string | null
  citySlug?: string | null
  districtSlug?: string | null
}): string | null {
  if (input.citySlug && input.districtSlug) {
    return districtPath(input.citySlug, input.districtSlug)
  }

  if (input.citySlug) {
    return cityPath(input.citySlug)
  }

  if (input.regionSlug && isFilterRegionSlug(input.regionSlug)) {
    return regionPath(input.regionSlug)
  }

  return null
}
