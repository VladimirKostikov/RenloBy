import type { DealType, ListingType } from '@/types'

export function listingDetailRouteName(
  dealType: DealType,
  listingType?: ListingType,
): string {
  if (listingType === 'commercial') {
    return 'commercial-listing-detail'
  }
  if (dealType === 'sale') {
    return 'sale-listing-detail'
  }
  if (dealType === 'rent') {
    return 'rent-listing-detail'
  }
  return 'listing-detail'
}
