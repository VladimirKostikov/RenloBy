import apiClient from '@/api/client'
import type { ListingDto } from '@/types'

export interface AiPreferenceDto {
  id: number
  userId: number | null
  guestSessionHash: string | null
  answers: Record<string, unknown>
  filters: Record<string, unknown>
  recommendedListingIds: number[]
  summary: string | null
  highlights: string[]
  listings: ListingDto[]
  isTest: boolean
  createdAt: string
  updatedAt: string
}

export async function createAiPreference(answers: Record<string, unknown>): Promise<AiPreferenceDto> {
  const { data } = await apiClient.post<AiPreferenceDto>('/api/ai-preferences', { answers })
  return data
}

export async function fetchLatestAiPreference(): Promise<AiPreferenceDto | null> {
  const { data } = await apiClient.get<{ item: AiPreferenceDto | null }>('/api/ai-preferences/latest')
  return data.item
}

export async function deleteAiPreference(id: number): Promise<void> {
  await apiClient.delete(`/api/ai-preferences/${id}`)
}
