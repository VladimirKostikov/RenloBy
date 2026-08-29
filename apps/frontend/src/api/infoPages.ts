import apiClient from './client'
import type { InfoPageDto } from '@/types/info'

export async function fetchInfoPages(): Promise<InfoPageDto[]> {
  const { data } = await apiClient.get<InfoPageDto[]>('/api/info-pages')
  return data
}

export async function fetchInfoPage(slug: string): Promise<InfoPageDto> {
  const { data } = await apiClient.get<InfoPageDto>(`/api/info-pages/${slug}`)
  return data
}
