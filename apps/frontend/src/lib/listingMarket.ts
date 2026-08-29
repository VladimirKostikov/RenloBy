export type ListingMarketMetric = 'price' | 'price_per_sqm'

export interface ListingMarketSnapshotDto {
  metric: ListingMarketMetric
  current: number
  avg: number
  min: number
  max: number
  rank: number
  similarCount: number
  changePct: number
  aiGoodPrice: boolean
}

export function formatMarketChangePct(changePct: number): string {
  if (!Number.isFinite(changePct) || changePct === 0) {
    return '0%'
  }

  const rounded = Math.round(changePct * 10) / 10
  const sign = rounded > 0 ? '+' : ''
  return `${sign}${rounded}%`
}

export function marketRankPercent(rank: number, similarCount: number): number {
  const total = Math.max(1, similarCount)
  return Math.min(100, Math.max(4, Math.round((rank / total) * 100)))
}
