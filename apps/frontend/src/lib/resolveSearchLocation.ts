import { FILTER_REGION_SLUGS, type FilterRegionSlug } from '@/lib/filterRegions'

export type SearchLocationCity = {
  id: number
  name: string
  slug: string
  regionSlug: string
}

export type SearchLocationDistrict = {
  id: number
  name: string
  slug: string
  cityId: number
}

export type ResolvedSearchLocation =
  | { kind: 'region'; regionSlug: FilterRegionSlug; label: string }
  | { kind: 'city'; cityId: number; regionSlug: string; label: string }
  | { kind: 'district'; cityId: number; districtId: number; regionSlug: string; label: string }

function normalizeQuery(value: string): string {
  return value
    .trim()
    .toLocaleLowerCase('ru-RU')
    .replace(/ё/g, 'е')
    .replace(/\s+/g, ' ')
}

function isExactMatch(query: string, label: string): boolean {
  return normalizeQuery(query) === normalizeQuery(label)
}

function isPrefixMatch(query: string, label: string): boolean {
  const normalizedQuery = normalizeQuery(query)
  const normalizedLabel = normalizeQuery(label)
  if (!normalizedQuery || !normalizedLabel) {
    return false
  }

  return normalizedLabel.startsWith(normalizedQuery)
}

export function resolveSearchLocation(input: {
  query: string
  cities: SearchLocationCity[]
  districts?: SearchLocationDistrict[]
  regionLabel: (slug: FilterRegionSlug) => string
}): ResolvedSearchLocation | null {
  const query = input.query.trim()
  if (query.length < 2) {
    return null
  }

  const exactCity = input.cities.find(
    (item) => isExactMatch(query, item.name) || isExactMatch(query, item.slug),
  )
  if (exactCity) {
    return {
      kind: 'city',
      cityId: exactCity.id,
      regionSlug: exactCity.regionSlug,
      label: exactCity.name,
    }
  }

  for (const slug of FILTER_REGION_SLUGS) {
    const label = input.regionLabel(slug)
    if (isExactMatch(query, label) || isExactMatch(query, slug)) {
      return { kind: 'region', regionSlug: slug, label }
    }
  }

  const prefixCity = input.cities.find(
    (item) => isPrefixMatch(query, item.name) || isPrefixMatch(query, item.slug),
  )
  if (prefixCity) {
    return {
      kind: 'city',
      cityId: prefixCity.id,
      regionSlug: prefixCity.regionSlug,
      label: prefixCity.name,
    }
  }

  for (const slug of FILTER_REGION_SLUGS) {
    const label = input.regionLabel(slug)
    if (isPrefixMatch(query, label) || isPrefixMatch(query, slug)) {
      return { kind: 'region', regionSlug: slug, label }
    }
  }

  const district = (input.districts ?? []).find((item) => isPrefixMatch(query, item.name))
  if (district) {
    const parentCity = input.cities.find((item) => item.id === district.cityId)
    if (parentCity) {
      return {
        kind: 'district',
        cityId: parentCity.id,
        districtId: district.id,
        regionSlug: parentCity.regionSlug,
        label: district.name,
      }
    }
  }

  return null
}
