import type { DealType, ListingType } from '@/types'

export type ListingOfferTypeInput = {
  dealType: DealType
  listingType: ListingType
}

export function listingDealTypeKey(dealType: DealType): string {
  return `dealType.${dealType}`
}

export function listingPropertyTypeKey(listingType: ListingType): string {
  return `listingType.${listingType}`
}

export function formatListingOfferType(
  listing: ListingOfferTypeInput,
  translate: (key: string) => string,
): string {
  return `${translate(listingDealTypeKey(listing.dealType))} · ${translate(listingPropertyTypeKey(listing.listingType))}`
}
