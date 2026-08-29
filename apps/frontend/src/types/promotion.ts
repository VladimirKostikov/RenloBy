export type PromotionTariffId = 'basic' | 'standard' | 'premium'
export type PaymentCurrencyCode = 'usd' | 'byn' | 'rub'

export interface PromotionTariff {
  id: PromotionTariffId
  nameKey: string
  descriptionKey: string
  priceUsd: number
  priceByn?: number
  priceRub?: number
  durationKey: string
  featureKeys: string[]
  icon: string
  popular?: boolean
}
