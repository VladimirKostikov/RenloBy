export type ClusterListingRef = {
  cityId: number
  districtId: number | null
}

export type ClusterCityRef = {
  slug: string
  regionSlug: string
}

export type ClusterLocationFocus =
  | { kind: 'district'; cityId: number; citySlug: string; districtId: number; regionSlug: string }
  | { kind: 'city'; cityId: number; citySlug: string; regionSlug: string }
  | { kind: 'region'; regionSlug: string }
  | { kind: 'bounds' }

function uniqueNumbers(values: Array<number | null | undefined>): number[] {
  return Array.from(new Set(values.filter((value): value is number => typeof value === 'number')))
}

export function resolveClusterLocationFocus(
  items: ClusterListingRef[],
  citiesById: Map<number, ClusterCityRef>,
): ClusterLocationFocus {
  if (items.length === 0) {
    return { kind: 'bounds' }
  }

  const cityIds = uniqueNumbers(items.map((item) => item.cityId))
  if (cityIds.length === 1) {
    const cityId = cityIds[0]!
    const city = citiesById.get(cityId)
    if (!city) {
      return { kind: 'bounds' }
    }

    const districtIds = uniqueNumbers(items.map((item) => item.districtId))
    if (districtIds.length === 1) {
      return {
        kind: 'district',
        cityId,
        citySlug: city.slug,
        districtId: districtIds[0]!,
        regionSlug: city.regionSlug,
      }
    }

    return {
      kind: 'city',
      cityId,
      citySlug: city.slug,
      regionSlug: city.regionSlug,
    }
  }

  const regionSlugs = Array.from(
    new Set(
      cityIds
        .map((cityId) => citiesById.get(cityId)?.regionSlug)
        .filter((slug): slug is string => Boolean(slug)),
    ),
  )

  if (regionSlugs.length === 1) {
    return { kind: 'region', regionSlug: regionSlugs[0]! }
  }

  return { kind: 'bounds' }
}
