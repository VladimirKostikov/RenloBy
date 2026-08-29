import apiClient from './client'

export type ExchangeRatesDto = {
  usdToByn: number
  usdToRub: number
  source: 'nbrb' | 'fallback' | string
  updatedAt: string | null
}

export async function fetchExchangeRates(): Promise<ExchangeRatesDto> {
  const { data } = await apiClient.get<ExchangeRatesDto>('/api/exchange-rates')
  return data
}
