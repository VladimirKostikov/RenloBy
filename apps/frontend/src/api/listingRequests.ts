import apiClient from './client'

export type ListingRequestDto = {
  id: number
  listingId: number
  name: string | null
  phone: string
  message: string
  status: string
  createdAt: string
  isTest: boolean
  listingAddress?: string | null
}

export async function createListingRequest(
  listingId: number,
  payload: { phone: string; message: string; name?: string | null },
): Promise<ListingRequestDto> {
  const { data } = await apiClient.post<ListingRequestDto>(`/api/listings/${listingId}/requests`, {
    phone: payload.phone.trim(),
    message: payload.message.trim(),
    name: payload.name && payload.name.trim() !== '' ? payload.name.trim() : null,
  })
  return data
}
