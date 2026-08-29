import apiClient from './client'
import type { ListingMarketSnapshotDto } from '@/lib/listingMarket'
import type { ListingDto, ListingSearchParams, PaginatedResponse } from '@/types'

function cleanSearchParams(params: ListingSearchParams): ListingSearchParams {
  const cleaned: ListingSearchParams = { ...params }

  if (cleaned.cityId === undefined || Number.isNaN(cleaned.cityId)) {
    delete cleaned.cityId
  }
  if (cleaned.districtId === undefined || Number.isNaN(cleaned.districtId)) {
    delete cleaned.districtId
  }
  if (cleaned.rooms === undefined || Number.isNaN(cleaned.rooms)) {
    delete cleaned.rooms
  }
  if (cleaned.floor === undefined || Number.isNaN(cleaned.floor)) {
    delete cleaned.floor
  }
  if (cleaned.minArea === undefined || Number.isNaN(cleaned.minArea)) {
    delete cleaned.minArea
  }
  if (cleaned.maxArea === undefined || Number.isNaN(cleaned.maxArea)) {
    delete cleaned.maxArea
  }
  if (cleaned.minPrice === undefined || Number.isNaN(cleaned.minPrice)) {
    delete cleaned.minPrice
  }
  if (cleaned.maxPrice === undefined || Number.isNaN(cleaned.maxPrice)) {
    delete cleaned.maxPrice
  }
  if (cleaned.verified === undefined) {
    delete cleaned.verified
  }
  if (cleaned.rentTerm === undefined) {
    delete cleaned.rentTerm
  }
  if (cleaned.hasDeposit !== true) {
    delete cleaned.hasDeposit
  }
  if (cleaned.utilitiesIncluded !== true) {
    delete cleaned.utilitiesIncluded
  }
  if (cleaned.noCommission !== true) {
    delete cleaned.noCommission
  }
  if (cleaned.fromOwner !== true) {
    delete cleaned.fromOwner
  }
  if (cleaned.hasRenovation !== true) {
    delete cleaned.hasRenovation
  }
  if (cleaned.query === undefined || cleaned.query.trim() === '') {
    delete cleaned.query
  }
  if (cleaned.listingType === undefined) {
    delete cleaned.listingType
  }

  return cleaned
}

export async function fetchListings(params: ListingSearchParams): Promise<PaginatedResponse<ListingDto>> {
  const { data } = await apiClient.get<PaginatedResponse<ListingDto>>('/api/listings', {
    params: cleanSearchParams(params),
  })
  return data
}

export async function fetchListing(id: number): Promise<ListingDto> {
  const { data } = await apiClient.get<ListingDto>(`/api/listings/${id}`)
  return data
}

export async function fetchListingMarket(id: number): Promise<ListingMarketSnapshotDto> {
  const { data } = await apiClient.get<ListingMarketSnapshotDto>(`/api/listings/${id}/market`)
  return data
}

export type AddressSuggestDto = {
  id: string
  kind: 'street' | 'district' | 'metro' | 'city'
  label: string
  subtitle?: string | null
  query: string
  cityId?: number | null
  districtId?: number | null
  metroStationId?: number | null
}

export async function fetchAddressSuggestions(
  query: string,
  limit = 10,
): Promise<AddressSuggestDto[]> {
  const trimmed = query.trim()
  if (trimmed.length < 2) {
    return []
  }

  const { data } = await apiClient.get<AddressSuggestDto[]>('/api/listings/address-suggest', {
    params: { q: trimmed, limit },
  })

  return Array.isArray(data) ? data : []
}

