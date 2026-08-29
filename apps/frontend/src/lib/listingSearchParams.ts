import type { DealType, ListingSearchParams, ListingType } from '@/types'
import type { RentTerm } from '@/lib/catalogListing'

export type ListingSortOption =
  | 'newest'
  | 'priceAsc'
  | 'priceDesc'
  | 'areaDesc'
  | 'viewsDesc'
  | 'random'

export type ListingSearchFilters = {
  dealType: DealType
  listingType?: ListingType
  cityId?: number
  regionSlug?: string
  districtId?: number
  rooms?: number
  floor?: number
  minArea?: number
  maxArea?: number
  minPrice?: number
  maxPrice?: number
  verifiedOnly: boolean
  searchQuery: string
  rentTerm: RentTerm
  rentDeposit: boolean
  rentUtilitiesIncluded: boolean
  rentNoCommission: boolean
  saleNoAgents: boolean
  saleFromOwner: boolean
  saleWithRenovation: boolean
  sort?: ListingSortOption
}

const SORT_MAP: Record<ListingSortOption, { sort: string; direction: 'ASC' | 'DESC' }> = {
  newest: { sort: 'publishedAt', direction: 'DESC' },
  priceAsc: { sort: 'price', direction: 'ASC' },
  priceDesc: { sort: 'price', direction: 'DESC' },
  areaDesc: { sort: 'area', direction: 'DESC' },
  viewsDesc: { sort: 'views', direction: 'DESC' },
  random: { sort: 'random', direction: 'ASC' },
}

export function buildListingSearchParams(
  filters: ListingSearchFilters,
  pageNum: number,
  pageLimit: number,
): ListingSearchParams {
  const trimmedQuery = filters.searchQuery.trim()
  const isRent = filters.dealType === 'rent'
  const isSale = filters.dealType === 'sale'
  const sortKey = filters.sort ?? 'newest'
  const sortConfig = SORT_MAP[sortKey] ?? SORT_MAP.newest

  return {
    dealType: filters.dealType,
    listingType: filters.listingType,
    cityId: filters.cityId,
    regionSlug: filters.cityId ? undefined : filters.regionSlug,
    districtId: filters.districtId,
    rooms: filters.rooms,
    floor: filters.floor,
    minArea: filters.minArea,
    maxArea: filters.maxArea,
    minPrice: filters.minPrice,
    maxPrice: filters.maxPrice,
    verified: filters.verifiedOnly ? true : undefined,
    rentTerm: isRent ? filters.rentTerm : undefined,
    hasDeposit: isRent && filters.rentDeposit ? true : undefined,
    utilitiesIncluded: isRent && filters.rentUtilitiesIncluded ? true : undefined,
    noCommission:
      (isRent && filters.rentNoCommission) || (isSale && filters.saleNoAgents) ? true : undefined,
    fromOwner: isSale && filters.saleFromOwner ? true : undefined,
    hasRenovation: isSale && filters.saleWithRenovation ? true : undefined,
    query: trimmedQuery !== '' ? trimmedQuery : undefined,
    page: pageNum,
    limit: pageLimit,
    sort: sortConfig.sort,
    direction: sortConfig.direction,
  }
}
