import apiClient from './client'
import type { ListingReportDto } from './admin'

export async function createListingReport(
  listingId: number,
  payload: { reason: string; comment?: string | null },
): Promise<ListingReportDto> {
  const { data } = await apiClient.post<ListingReportDto>(`/api/listings/${listingId}/reports`, {
    reason: payload.reason,
    comment: payload.comment && payload.comment.trim() !== '' ? payload.comment.trim() : null,
  })
  return data
}
