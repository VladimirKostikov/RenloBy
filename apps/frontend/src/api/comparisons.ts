import apiClient from './client'
import type {
  CollectionListResponse,
  CollectionToggleResponse,
  ComparisonItemDto,
} from '@/types'

export async function fetchComparisons(): Promise<ComparisonItemDto[]> {
  const { data } = await apiClient.get<CollectionListResponse<ComparisonItemDto>>('/api/comparisons')
  return data.items
}

export async function toggleComparison(listingId: number): Promise<CollectionToggleResponse> {
  const { data } = await apiClient.post<CollectionToggleResponse>('/api/comparisons/toggle', { listingId })
  return data
}

export async function removeComparison(id: number): Promise<void> {
  await apiClient.delete(`/api/comparisons/${id}`)
}
