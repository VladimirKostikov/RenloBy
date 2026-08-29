import type { ListingDto } from '@/types'
import type { ListingSeoContext } from './types'

export function parseDistrictCityLabel(label: string): { districtName: string; cityName: string } | null {
  const parts = label.split(', ').map((part) => part.trim()).filter(Boolean)
  if (parts.length < 2) {
    return null
  }

  return {
    districtName: parts[0] ?? '',
    cityName: parts[1] ?? '',
  }
}

export function listingToSeoContext(
  listing: ListingDto,
  cityName: string,
  districtName: string,
): ListingSeoContext {
  return {
    listing: {
      id: listing.id,
      dealType: listing.dealType,
      listingType: listing.listingType,
      price: listing.price,
      rooms: listing.rooms,
      area: listing.area,
      address: listing.address,
      images: listing.images,
      metaTitle: listing.metaTitle ?? null,
      metaDescription: listing.metaDescription ?? null,
      metaKeywords: listing.metaKeywords ?? null,
    },
    cityName,
    districtName,
  }
}

export function listingSeoFromDistrictLabel(listing: ListingDto, districtLabel: string | undefined) {
  if (!districtLabel) {
    return null
  }

  const parsed = parseDistrictCityLabel(districtLabel)
  if (!parsed) {
    return null
  }

  return listingToSeoContext(listing, parsed.cityName, parsed.districtName)
}
