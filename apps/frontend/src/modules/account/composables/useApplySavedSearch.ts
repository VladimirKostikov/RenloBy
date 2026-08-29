import { useRouter } from 'vue-router'
import { useListingsStore } from '@/stores/listings'
import type { DealType, ListingType } from '@/types'

function resolveCatalogRoute(dealType: DealType, listingType?: ListingType): string {
  if (listingType === 'commercial') {
    return '/commercial'
  }
  if (dealType === 'rent') {
    return '/rent'
  }
  if (dealType === 'sale') {
    return '/sale'
  }
  return '/'
}

export function useApplySavedSearch() {
  const router = useRouter()
  const listings = useListingsStore()

  async function applySavedSearch(filters: Record<string, unknown>) {
    const listingType = filters.listingType as ListingType | undefined
    const rawDeal = filters.dealType
    const dealType: DealType =
      rawDeal === 'rent' || rawDeal === 'sale'
        ? rawDeal
        : 'sale'

    listings.setDealType(dealType)
    listings.listingType = listingType
    listings.cityId = typeof filters.cityId === 'number' ? filters.cityId : listings.cityId
    listings.districtId = typeof filters.districtId === 'number' ? filters.districtId : undefined
    listings.rooms = typeof filters.rooms === 'number' ? filters.rooms : undefined
    listings.minArea = typeof filters.minArea === 'number' ? filters.minArea : undefined
    listings.maxArea = typeof filters.maxArea === 'number' ? filters.maxArea : undefined
    listings.minPrice = typeof filters.minPrice === 'number' ? filters.minPrice : undefined
    listings.maxPrice = typeof filters.maxPrice === 'number' ? filters.maxPrice : undefined
    listings.verifiedOnly = filters.verifiedOnly === true
    listings.floor = typeof filters.floor === 'number' ? filters.floor : undefined
    listings.searchQuery = typeof filters.searchQuery === 'string' ? filters.searchQuery : ''
    listings.catalogCategory = typeof filters.catalogCategory === 'string'
      ? filters.catalogCategory as typeof listings.catalogCategory
      : listingType === 'commercial' ? 'commercial' : 'all'
    listings.rentTerm = filters.rentTerm as typeof listings.rentTerm

    if (!listings.cities.length) {
      await listings.loadReferenceData()
    }
    if (listings.cityId) {
      await listings.loadDistricts(listings.cityId)
    }
    await listings.search()
    await router.push(resolveCatalogRoute(dealType, listingType))
  }

  return { applySavedSearch }
}
