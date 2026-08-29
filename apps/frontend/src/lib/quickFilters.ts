import type { ListingDto } from '@/types'

export type QuickFilterKey =
  | 'newBuildings'
  | 'withPhoto'
  | 'noAgents'
  | 'fromOwner'
  | 'withRenovation'

export const QUICK_FILTER_KEYS: QuickFilterKey[] = [
  'newBuildings',
  'withPhoto',
  'noAgents',
  'fromOwner',
  'withRenovation',
]

export function applyQuickFilters(listings: ListingDto[], active: Set<QuickFilterKey>): ListingDto[] {
  if (active.size === 0) {
    return listings
  }

  return listings.filter((listing) => {
    if (active.has('withPhoto') && listing.images.length === 0) {
      return false
    }

    if (active.has('newBuildings') && !listing.verified) {
      return false
    }

    if (active.has('noAgents') && !listing.aiGoodPrice) {
      return false
    }

    if (active.has('fromOwner') && !listing.verified) {
      return false
    }

    if (active.has('withRenovation') && listing.area < 40) {
      return false
    }

    return true
  })
}
