import apiClient from './client'
import type { MapStatsParams, MapStatsResponse } from '@/types/map'

export async function fetchMapStats(params: MapStatsParams = {}): Promise<MapStatsResponse> {
  const { data } = await apiClient.get<MapStatsResponse>('/api/map/stats', { params })
  return data
}
