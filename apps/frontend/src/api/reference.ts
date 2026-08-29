import apiClient from './client'
import type { CityDto, DistrictDto, MetroStationDto } from '@/types'

export async function fetchCities(): Promise<CityDto[]> {
  const { data } = await apiClient.get<CityDto[]>('/api/cities')
  return data
}

export async function fetchDistricts(cityId?: number): Promise<DistrictDto[]> {
  const { data } = await apiClient.get<DistrictDto[]>('/api/districts', {
    params: cityId ? { cityId } : undefined,
  })
  return data
}

export async function fetchMetroStations(cityId?: number): Promise<MetroStationDto[]> {
  const { data } = await apiClient.get<MetroStationDto[]>('/api/metro-stations', {
    params: cityId ? { cityId } : undefined,
  })
  return data
}
