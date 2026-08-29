export type SearchMapQuery = {
  panel?: 'extended'
}

export function buildSearchMapLocation(query?: SearchMapQuery): string {
  if (query?.panel === 'extended') {
    return '/search?panel=extended'
  }

  return '/search'
}

export function isExtendedFiltersOpen(panel: unknown): boolean {
  return panel === 'extended'
}
