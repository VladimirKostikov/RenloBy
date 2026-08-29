import apiClient from './client'
import type {
  CollectionListResponse,
  CollectionToggleResponse,
  FavoriteItemDto,
} from '@/types'

export async function fetchFavorites(): Promise<FavoriteItemDto[]> {
  const { data } = await apiClient.get<CollectionListResponse<FavoriteItemDto>>('/api/favorites')
  return data.items
}

export async function toggleFavorite(listingId: number): Promise<CollectionToggleResponse> {
  const { data } = await apiClient.post<CollectionToggleResponse>('/api/favorites/toggle', { listingId })
  return data
}

export async function removeFavorite(id: number): Promise<void> {
  await apiClient.delete(`/api/favorites/${id}`)
}
