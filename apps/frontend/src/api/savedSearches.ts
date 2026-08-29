import apiClient from './client'
import type { SavedSearchDto } from '@/types'

export interface CreateSavedSearchPayload {
  name: string
  filters: Record<string, unknown>
}

export async function fetchSavedSearches(): Promise<SavedSearchDto[]> {
  const { data } = await apiClient.get<SavedSearchDto[]>('/api/saved-searches')
  return data
}

export async function createSavedSearch(payload: CreateSavedSearchPayload): Promise<SavedSearchDto> {
  const { data } = await apiClient.post<SavedSearchDto>('/api/saved-searches', payload)
  return data
}

export async function deleteSavedSearch(id: number): Promise<void> {
  await apiClient.delete(`/api/saved-searches/${id}`)
}
