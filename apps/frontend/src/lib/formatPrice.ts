import type { CurrencyCode } from '@/types'

const DEFAULT_USD_TO_BYN = Number(import.meta.env.VITE_USD_TO_BYN_RATE ?? 3.27)

let usdToBynRate = Number.isFinite(DEFAULT_USD_TO_BYN) && DEFAULT_USD_TO_BYN > 0
  ? DEFAULT_USD_TO_BYN
  : 3.27

export function getUsdToBynRate(): number {
  return usdToBynRate
}

export function setUsdToBynRate(rate: number): void {
  if (!Number.isFinite(rate) || rate <= 0) {
    return
  }
  usdToBynRate = rate
}

export function resetUsdToBynRate(): void {
  usdToBynRate = Number.isFinite(DEFAULT_USD_TO_BYN) && DEFAULT_USD_TO_BYN > 0
    ? DEFAULT_USD_TO_BYN
    : 3.27
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(value).replace(/\u00a0/g, ' ')
}

function withCurrencySuffix(formattedValue: string, suffix: string): string {
  return `${formattedValue}\u00a0${suffix}`
}

export function formatRate(rate: number): string {
  return new Intl.NumberFormat('ru-RU', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 4,
  }).format(rate).replace(/\u00a0/g, ' ')
}

export function convertFromUsd(amountUsd: number, currency: CurrencyCode = 'byn'): number {
  if (currency === 'byn') {
    return Math.round(amountUsd * usdToBynRate)
  }
  return amountUsd
}

export function convertToUsd(amount: number, currency: CurrencyCode = 'byn'): number {
  if (currency === 'byn') {
    return Math.round(amount / usdToBynRate)
  }
  return Math.round(amount)
}

export function formatListingPrice(amountUsd: number, currency: CurrencyCode = 'byn'): string {
  const value = convertFromUsd(amountUsd, currency)
  const suffix = currency === 'usd' ? '$' : 'BYN'
  return withCurrencySuffix(formatNumber(value), suffix)
}

export function formatListingPriceDetailed(amountUsd: number, currency: CurrencyCode = 'byn'): string {
  return formatListingPrice(amountUsd, currency)
}

export function formatListingPricePerSqm(amountUsd: number, currency: CurrencyCode = 'byn'): string {
  const value = convertFromUsd(amountUsd, currency)
  const suffix = currency === 'usd' ? '$/м²' : 'BYN/м²'
  return withCurrencySuffix(formatNumber(value), suffix)
}

export function formatMarkerPrice(amountUsd: number): string {
  return formatListingPrice(amountUsd, 'byn')
}

export function formatUsdPrice(price: number): string {
  return formatListingPrice(price, 'usd')
}

export function formatUsdPerSqm(price: number): string {
  return formatListingPricePerSqm(price, 'usd')
}

export function formatFoundCount(count: number): string {
  return formatNumber(count)
}
