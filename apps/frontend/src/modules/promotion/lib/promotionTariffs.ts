import { fetchTariffs } from '@/api/tariffs'
import type { PaymentCurrencyCode, PromotionTariff, PromotionTariffId } from '@/types/promotion'

const USD_TO_BYN = Number(import.meta.env.VITE_USD_TO_BYN_RATE ?? 3.27)
const USD_TO_RUB = Number(import.meta.env.VITE_USD_TO_RUB_RATE ?? 93)

export const PAYMENT_CURRENCIES: PaymentCurrencyCode[] = ['byn', 'rub', 'usd']

export const PROMOTION_TARIFF_DEFAULTS: PromotionTariff[] = [
  {
    id: 'basic',
    nameKey: 'promotion.tariffs.basic.name',
    descriptionKey: 'promotion.tariffs.basic.description',
    priceUsd: 9.9,
    durationKey: 'promotion.tariffs.basic.duration',
    icon: '/figma/tariff-start.svg',
    featureKeys: [
      'promotion.features.searchBoost',
      'promotion.features.highlight',
    ],
  },
  {
    id: 'standard',
    nameKey: 'promotion.tariffs.standard.name',
    descriptionKey: 'promotion.tariffs.standard.description',
    priceUsd: 19.9,
    durationKey: 'promotion.tariffs.standard.duration',
    icon: '/figma/tariff-optimum.svg',
    featureKeys: [
      'promotion.features.searchBoost',
      'promotion.features.highlight',
      'promotion.features.pin',
      'promotion.features.topBadge',
      'promotion.features.mapPriority',
    ],
    popular: true,
  },
  {
    id: 'premium',
    nameKey: 'promotion.tariffs.premium.name',
    descriptionKey: 'promotion.tariffs.premium.description',
    priceUsd: 34.9,
    durationKey: 'promotion.tariffs.premium.duration',
    icon: '/figma/tariff-maximum.svg',
    featureKeys: [
      'promotion.features.searchBoost',
      'promotion.features.highlight',
      'promotion.features.pinWeek',
      'promotion.features.topBadge',
      'promotion.features.homepage',
      'promotion.features.analytics',
    ],
  },
]

/** @deprecated use PROMOTION_TARIFF_DEFAULTS or loadPromotionTariffs() */
export const PROMOTION_TARIFFS = PROMOTION_TARIFF_DEFAULTS

export function findPromotionTariff(
  id: PromotionTariffId,
  tariffs: PromotionTariff[] = PROMOTION_TARIFF_DEFAULTS,
): PromotionTariff | undefined {
  return tariffs.find((tariff) => tariff.id === id)
}

export function getPopularPromotionTariff(
  tariffs: PromotionTariff[] = PROMOTION_TARIFF_DEFAULTS,
): PromotionTariff | undefined {
  return tariffs.find((tariff) => tariff.popular)
}

export function convertTariffAmount(amountUsd: number, currency: PaymentCurrencyCode): number {
  if (currency === 'byn') {
    return Math.round(amountUsd * USD_TO_BYN)
  }
  if (currency === 'rub') {
    return Math.round((amountUsd * USD_TO_RUB) / 10) * 10
  }
  return Math.round(amountUsd * 100) / 100
}

export function formatTariffPrice(amountUsd: number, currency: PaymentCurrencyCode, tariff?: PromotionTariff): string {
  const value =
    currency === 'byn' && tariff?.priceByn != null
      ? tariff.priceByn
      : currency === 'rub' && tariff?.priceRub != null
        ? tariff.priceRub
        : convertTariffAmount(amountUsd, currency)

  const formatted = new Intl.NumberFormat('ru-RU', {
    maximumFractionDigits: currency === 'usd' ? 2 : 0,
    minimumFractionDigits: currency === 'usd' ? 2 : 0,
  })
    .format(value)
    .replace(/\u00a0/g, ' ')

  if (currency === 'usd') {
    return `${formatted} $`
  }
  if (currency === 'rub') {
    return `${formatted} ₽`
  }
  return `${formatted} BYN`
}

export function paymentCurrencyApiCode(currency: PaymentCurrencyCode): 'USD' | 'BYN' | 'RUB' {
  return currency.toUpperCase() as 'USD' | 'BYN' | 'RUB'
}

export function mergeTariffPrices(
  defaults: PromotionTariff[],
  remote: Array<{
    code: string
    priceUsd: string
    priceByn?: string
    priceRub?: string
    isPopular: boolean
  }>,
): PromotionTariff[] {
  return defaults.map((tariff) => {
    const match = remote.find((item) => item.code === tariff.id)
    if (!match) {
      return tariff
    }
    const price = Number.parseFloat(match.priceUsd)
    const priceByn = match.priceByn != null ? Number.parseFloat(match.priceByn) : undefined
    const priceRub = match.priceRub != null ? Number.parseFloat(match.priceRub) : undefined
    return {
      ...tariff,
      priceUsd: Number.isFinite(price) ? price : tariff.priceUsd,
      priceByn: priceByn != null && Number.isFinite(priceByn) ? priceByn : tariff.priceByn,
      priceRub: priceRub != null && Number.isFinite(priceRub) ? priceRub : tariff.priceRub,
      popular: match.isPopular,
    }
  })
}

export async function loadPromotionTariffs(): Promise<PromotionTariff[]> {
  try {
    const remote = await fetchTariffs()
    return mergeTariffPrices(PROMOTION_TARIFF_DEFAULTS, remote)
  } catch {
    return [...PROMOTION_TARIFF_DEFAULTS]
  }
}
