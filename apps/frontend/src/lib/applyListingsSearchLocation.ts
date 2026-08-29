import type { ResolvedSearchLocation } from '@/lib/resolveSearchLocation'
import type { useListingsStore } from '@/stores/listings'

type ListingsStore = ReturnType<typeof useListingsStore>

export async function applyListingsSearchLocation(
  listings: ListingsStore,
  location: ResolvedSearchLocation,
): Promise<void> {
  if (location.kind === 'region') {
    listings.regionSlug = location.regionSlug
    listings.cityId = undefined
    listings.districtId = undefined
    listings.searchQuery = ''
  } else if (location.kind === 'city') {
    listings.cityId = location.cityId
    listings.regionSlug = location.regionSlug
    listings.districtId = undefined
    listings.searchQuery = ''
    await listings.loadDistricts(location.cityId)
  } else {
    listings.cityId = location.cityId
    listings.districtId = location.districtId
    listings.regionSlug = location.regionSlug
    listings.searchQuery = ''
    await listings.loadDistricts(location.cityId)
  }

  await listings.search()
}
