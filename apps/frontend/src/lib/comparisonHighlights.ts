import type { ListingDto } from '@/types'

export type ComparisonRowKey =
  | 'price'
  | 'pricePerSqm'
  | 'rooms'
  | 'area'
  | 'floor'
  | 'address'
  | 'dealType'

export type ComparisonHighlightMode = 'min' | 'max' | null

const HIGHLIGHT_MODE: Record<ComparisonRowKey, ComparisonHighlightMode> = {
  price: 'min',
  pricePerSqm: 'min',
  rooms: 'max',
  area: 'max',
  floor: null,
  address: null,
  dealType: null,
}

function metricValue(listing: ListingDto, key: ComparisonRowKey): number | null {
  if (key === 'price') {
    return listing.price
  }
  if (key === 'pricePerSqm') {
    return listing.pricePerSqm
  }
  if (key === 'rooms') {
    return listing.rooms
  }
  if (key === 'area') {
    return listing.area
  }
  return null
}

export function comparisonHighlightMode(key: ComparisonRowKey): ComparisonHighlightMode {
  return HIGHLIGHT_MODE[key]
}

export function findBestComparisonIndexes(
  listings: ListingDto[],
  key: ComparisonRowKey,
): Set<number> {
  const mode = comparisonHighlightMode(key)
  if (!mode || listings.length < 2) {
    return new Set()
  }

  const values = listings.map((listing) => metricValue(listing, key))
  const numeric = values.filter((value): value is number => value !== null && Number.isFinite(value))
  if (numeric.length < 2) {
    return new Set()
  }

  const best = mode === 'min' ? Math.min(...numeric) : Math.max(...numeric)
  const indexes = new Set<number>()
  values.forEach((value, index) => {
    if (value === best) {
      indexes.add(index)
    }
  })

  if (indexes.size === listings.length) {
    return new Set()
  }

  return indexes
}

export function isBestComparisonValue(
  listings: ListingDto[],
  key: ComparisonRowKey,
  index: number,
): boolean {
  return findBestComparisonIndexes(listings, key).has(index)
}
