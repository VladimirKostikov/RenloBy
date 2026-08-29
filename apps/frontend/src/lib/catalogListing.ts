import type { DealType, ListingDto, ListingType } from '@/types'

export type RentTerm = 'daily' | 'long'
export type CatalogCategory = 'all' | ListingType

export function isCatalogTopListing(listing: ListingDto): boolean {
  return listing.verified || listing.aiGoodPrice
}

export function resolveCategoryListingType(category: CatalogCategory): ListingType | undefined {
  if (category === 'all') {
    return undefined
  }
  return category
}

export function resolveCategoryDealType(
  category: CatalogCategory,
  baseDealType: DealType = 'rent',
): DealType {
  void category
  return baseDealType
}
