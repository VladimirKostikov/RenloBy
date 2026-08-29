import apiClient from './client'
import type { TariffDto } from './admin'

export type PublicTariffDto = Pick<
  TariffDto,
  'id' | 'code' | 'priceUsd' | 'currency' | 'isPopular' | 'sortOrder'
> & {
  priceByn?: string
  priceRub?: string
}

export async function fetchTariffs(): Promise<PublicTariffDto[]> {
  const { data } = await apiClient.get<PublicTariffDto[]>('/api/tariffs')
  return Array.isArray(data) ? data : []
}
