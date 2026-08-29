import type { ListingDto } from '@/types'
import { displayOptionalValue, formatFloorShort } from '@/lib/listingOptionalFields'

export interface ListingCharacteristicRow {
  label: string
  value: string
}

export function resolveCharacteristicText(
  value: string,
  translate: (key: string) => string,
): string {
  if (
    value.startsWith('listingDetail.') ||
    value.startsWith('listing.') ||
    value.startsWith('listingType.')
  ) {
    return translate(value)
  }
  return value
}

export interface ListingInfrastructureRow {
  icon: 'metro' | 'school' | 'shop' | 'park'
  label: string
  minutes: number
}

export interface ListingDetailExtras {
  description: string
  characteristics: ListingCharacteristicRow[]
  conditions: ListingCharacteristicRow[]
  infrastructure: ListingInfrastructureRow[]
  securityCheckedDaysAgo: number
  responseMinutes: number
}

const NOT_SPECIFIED = '-'

function pushIfPresent(
  rows: ListingCharacteristicRow[],
  label: string,
  value: string | number | null | undefined,
  format?: (raw: string | number) => string,
): void {
  if (value === null || value === undefined) {
    return
  }
  if (typeof value === 'string' && value.trim() === '') {
    return
  }
  rows.push({
    label,
    value: format ? format(value) : displayOptionalValue(value, NOT_SPECIFIED),
  })
}

function yesNo(value: boolean): string {
  return value ? 'listingDetail.yes' : 'listingDetail.no'
}

export function buildListingDealConditions(listing: ListingDto): ListingCharacteristicRow[] {
  const rows: ListingCharacteristicRow[] = [
    {
      label: listing.dealType === 'rent' ? 'listingDetail.landlordType' : 'listingDetail.ownerType',
      value: listing.fromOwner
        ? 'listingDetail.ownerTypeOwner'
        : 'listingDetail.ownerTypeAgent',
    },
    {
      label: 'listingDetail.commission',
      value: listing.noCommission
        ? 'listingDetail.commissionNone'
        : 'listingDetail.commissionYes',
    },
    {
      label: 'listingDetail.renovation',
      value: yesNo(listing.hasRenovation),
    },
    {
      label: 'listingDetail.priceNegotiable',
      value: yesNo(listing.priceNegotiable),
    },
  ]

  if (listing.dealType === 'rent') {
    if (listing.rentTerm) {
      rows.push({
        label: 'listingDetail.rentTerm',
        value: `listingDetail.rentTerms.${listing.rentTerm}`,
      })
    }
    rows.push(
      {
        label: 'listingDetail.deposit',
        value: yesNo(listing.hasDeposit),
      },
      {
        label: 'listingDetail.utilitiesIncluded',
        value: yesNo(listing.utilitiesIncluded),
      },
    )
  }

  return rows
}

export function buildListingDetailExtras(listing: ListingDto): ListingDetailExtras {
  const characteristics: ListingCharacteristicRow[] = []

  pushIfPresent(characteristics, 'listingDetail.rooms', listing.rooms, (raw) =>
    Number(raw) === 0 ? 'listing.studio' : String(raw),
  )
  if (listing.floor !== null || listing.totalFloors !== null) {
    characteristics.push({
      label: 'listingDetail.floor',
      value: formatFloorShort(listing.floor, listing.totalFloors, NOT_SPECIFIED),
    })
  }
  pushIfPresent(characteristics, 'listingDetail.totalArea', listing.area, (raw) => `${raw} м²`)

  characteristics.push({
    label: 'listingDetail.objectType',
    value: `listingType.${listing.listingType}`,
  })

  return {
    description: '',
    characteristics,
    conditions: buildListingDealConditions(listing),
    infrastructure: [],
    securityCheckedDaysAgo: 1 + (listing.id % 4),
    responseMinutes: 15,
  }
}

export const SIMILAR_LISTINGS_LIMIT = 4

export function findSimilarListings(
  listing: ListingDto,
  pool: ListingDto[],
  limit = SIMILAR_LISTINGS_LIMIT,
): ListingDto[] {
  const target = Math.max(SIMILAR_LISTINGS_LIMIT, limit)
  const candidates = pool.filter((item) => item.id !== listing.id)
  const seen = new Set<number>()
  const result: ListingDto[] = []

  const pushFrom = (items: ListingDto[]) => {
    for (const item of items) {
      if (result.length >= target) {
        return
      }
      if (seen.has(item.id)) {
        continue
      }
      seen.add(item.id)
      result.push(item)
    }
  }

  pushFrom(
    candidates.filter(
      (item) =>
        item.cityId === listing.cityId &&
        item.rooms === listing.rooms &&
        item.dealType === listing.dealType,
    ),
  )
  pushFrom(
    candidates.filter(
      (item) => item.cityId === listing.cityId && item.dealType === listing.dealType,
    ),
  )
  pushFrom(candidates.filter((item) => item.dealType === listing.dealType))
  pushFrom(candidates.filter((item) => item.cityId === listing.cityId))
  pushFrom(candidates)

  return result
}
