import apiClient from './client'
import type { ListingDto, PaginatedResponse } from '@/types'

export type SellerProfileDto = {
  id: number
  name: string
  photo: string | null
  phone: string | null
  instagram: string | null
  telegram: string | null
  whatsapp: string | null
  viber: string | null
  lastSeenAt: string | null
  registeredAt: string
  listingsCount: number
}

export async function fetchSellerProfile(id: number): Promise<SellerProfileDto> {
  const { data } = await apiClient.get<SellerProfileDto>(`/api/sellers/${id}`)
  return data
}

export async function fetchSellerListings(
  id: number,
  params: { page?: number; limit?: number } = {},
): Promise<PaginatedResponse<ListingDto>> {
  const { data } = await apiClient.get<PaginatedResponse<ListingDto>>(`/api/sellers/${id}/listings`, {
    params: {
      page: params.page ?? 1,
      limit: params.limit ?? 12,
    },
  })
  return data
}
