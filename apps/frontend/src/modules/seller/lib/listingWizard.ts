import type { DealType, ListingStatus, ListingType, RentTerm } from '@/types'
import { convertFromUsd, convertToUsd } from '@/lib/formatPrice'
import { getMapCenter } from '@/lib/mapConfig'
import { normalizeMetroLineColor } from '@/lib/metroLineColor'

export type WizardStepId =
  | 'deal'
  | 'object'
  | 'location'
  | 'details'
  | 'price'
  | 'photos'
  | 'review'

export type WizardAbsentFieldKey =
  | 'district'
  | 'metro'
  | 'street'
  | 'house'
  | 'entrance'
  | 'apartmentNumber'
  | 'floor'
  | 'totalFloors'

export type WizardLocationFieldKey = WizardAbsentFieldKey

export type WizardLocationAbsent = Record<WizardAbsentFieldKey, boolean>

export interface ListingWizardDraft {
  dealType: DealType
  listingType: ListingType
  region: string
  city: string
  district: string
  metro: string
  metroLineColor: string
  street: string
  house: string
  entrance: string
  apartmentNumber: string
  absent: WizardLocationAbsent
  latitude: number
  longitude: number
  metroMinutes: number | null
  rooms: number | null
  area: number | null
  floor: number | null
  totalFloors: number | null
  price: number | null
  priceByn: number | null
  rentTerm: RentTerm | null
  hasDeposit: boolean
  utilitiesIncluded: boolean
  noCommission: boolean
  fromOwner: boolean | null
  hasRenovation: boolean
  priceNegotiable: boolean
  images: string[]
}

export const WIZARD_STEPS: WizardStepId[] = [
  'deal',
  'object',
  'location',
  'details',
  'price',
  'photos',
  'review',
]

export const WIZARD_DRAFT_STORAGE_KEY = 'donmap-listing-wizard-draft'

export function createEmptyLocationAbsent(): WizardLocationAbsent {
  return {
    district: false,
    metro: false,
    street: false,
    house: false,
    entrance: false,
    apartmentNumber: false,
    floor: false,
    totalFloors: false,
  }
}

export function createEmptyWizardDraft(): ListingWizardDraft {
  const [latitude, longitude] = getMapCenter()

  return {
    dealType: 'sale',
    listingType: 'apartment',
    region: '',
    city: '',
    district: '',
    metro: '',
    metroLineColor: normalizeMetroLineColor(null),
    street: '',
    house: '',
    entrance: '',
    apartmentNumber: '',
    absent: createEmptyLocationAbsent(),
    latitude,
    longitude,
    metroMinutes: null,
    rooms: null,
    area: null,
    floor: null,
    totalFloors: null,
    price: null,
    priceByn: null,
    rentTerm: null,
    hasDeposit: false,
    utilitiesIncluded: false,
    noCommission: false,
    fromOwner: null,
    hasRenovation: false,
    priceNegotiable: false,
    images: [],
  }
}

export function composeListingAddress(draft: ListingWizardDraft): string {
  const street = draft.absent.street ? '' : draft.street.trim()
  const house = draft.absent.house ? '' : draft.house.trim()
  const streetHouse = [street, house].filter(Boolean).join(', ')
  const parts = [streetHouse]
  const entrance = draft.absent.entrance ? '' : draft.entrance.trim()
  const apartment = draft.absent.apartmentNumber ? '' : draft.apartmentNumber.trim()
  if (entrance) {
    parts.push(`подъезд ${entrance}`)
  }
  if (apartment) {
    parts.push(`кв. ${apartment}`)
  }
  const composed = parts.filter(Boolean).join(', ')
  if (composed) {
    return composed
  }
  return draft.city.trim() || draft.region.trim() || 'н/д'
}

export function syncWizardPriceFromUsd(draft: ListingWizardDraft, priceUsd: number | null): void {
  if (priceUsd === null || !Number.isFinite(priceUsd) || priceUsd <= 0) {
    draft.price = null
    draft.priceByn = null
    return
  }
  const usd = Math.round(priceUsd)
  draft.price = usd
  draft.priceByn = convertFromUsd(usd, 'byn')
}

export function syncWizardPriceFromByn(draft: ListingWizardDraft, priceByn: number | null): void {
  if (priceByn === null || !Number.isFinite(priceByn) || priceByn <= 0) {
    draft.price = null
    draft.priceByn = null
    return
  }
  const byn = Math.round(priceByn)
  draft.priceByn = byn
  draft.price = convertToUsd(byn, 'byn')
}

export function parseOptionalNumber(raw: string | number | null | undefined): number | null {
  if (raw === null || raw === undefined) {
    return null
  }
  const text = String(raw).trim()
  if (text === '') {
    return null
  }
  const value = Number(text)
  return Number.isFinite(value) ? value : null
}

export function validateWizardField(field: string, draft: ListingWizardDraft): string | null {
  switch (field) {
    case 'region':
      return draft.region.trim() ? null : 'region'
    case 'city':
      return draft.city.trim() ? null : 'city'
    case 'district':
      return null
    case 'street':
      if (draft.absent.street) {
        return null
      }
      return draft.street.trim() ? null : 'street'
    case 'house':
      if (draft.absent.house) {
        return null
      }
      return draft.house.trim() ? null : 'house'
    case 'price':
      return draft.price !== null && draft.price > 0 ? null : 'price'
    case 'priceByn':
      return draft.priceByn !== null && draft.priceByn > 0 ? null : 'priceByn'
    case 'rooms':
      return draft.rooms !== null && draft.rooms >= 0 && draft.rooms <= 20 ? null : 'rooms'
    case 'area':
      return draft.area !== null && draft.area > 0 ? null : 'area'
    case 'floor':
      if (draft.absent.floor || draft.floor === null) return null
      if (draft.floor < 0) return 'floor'
      if (!draft.absent.totalFloors && draft.totalFloors !== null && draft.floor > draft.totalFloors) {
        return 'floor'
      }
      return null
    case 'totalFloors':
      if (draft.absent.totalFloors || draft.totalFloors === null) return null
      return draft.totalFloors >= 1 ? null : 'totalFloors'
    case 'rentTerm':
      return draft.dealType === 'rent' && !draft.rentTerm ? 'rentTerm' : null
    case 'fromOwner':
      return draft.fromOwner === null ? 'fromOwner' : null
    default:
      return null
  }
}

export function validateWizardStep(step: WizardStepId, draft: ListingWizardDraft): string[] {
  const fieldsByStep: Record<WizardStepId, string[]> = {
    deal: [],
    object: [],
    location: ['region', 'city', 'street', 'house'],
    details: ['rooms', 'area', 'rentTerm', 'fromOwner'],
    price: ['price', 'priceByn'],
    photos: [],
    review: [],
  }

  return fieldsByStep[step]
    .map((field) => validateWizardField(field, draft))
    .filter((error): error is string => error !== null)
}

export function toCreatePayload(draft: ListingWizardDraft, status: ListingStatus) {
  const metro = draft.absent.metro ? '' : draft.metro.trim()
  const district = draft.absent.district ? '' : draft.district.trim()

  return {
    dealType: draft.dealType,
    listingType: draft.listingType,
    price: draft.price ?? 0,
    rooms: draft.rooms ?? 0,
    area: draft.area ?? 0,
    floor: draft.absent.floor ? null : draft.floor,
    totalFloors: draft.absent.totalFloors ? null : draft.totalFloors,
    address: composeListingAddress(draft),
    latitude: draft.latitude,
    longitude: draft.longitude,
    city: draft.city.trim(),
    district: district || null,
    metro: metro !== '' ? metro : null,
    metroLineColor: metro !== '' ? normalizeMetroLineColor(draft.metroLineColor) : null,
    metroMinutes: draft.metroMinutes,
    rentTerm: draft.dealType === 'rent' ? draft.rentTerm : null,
    hasDeposit: draft.hasDeposit,
    utilitiesIncluded: draft.utilitiesIncluded,
    noCommission: draft.noCommission,
    fromOwner: draft.fromOwner === true,
    hasRenovation: draft.hasRenovation,
    priceNegotiable: draft.priceNegotiable,
    images: draft.images,
    status,
  }
}

export function saveWizardDraftLocal(draft: ListingWizardDraft, stepIndex: number): void {
  const payload = JSON.stringify({ draft, stepIndex, savedAt: Date.now() })
  localStorage.setItem(WIZARD_DRAFT_STORAGE_KEY, payload)
}

export function loadWizardDraftLocal(): { draft: ListingWizardDraft, stepIndex: number } | null {
  const raw = localStorage.getItem(WIZARD_DRAFT_STORAGE_KEY)
  if (!raw) {
    return null
  }

  try {
    const parsed = JSON.parse(raw) as { draft?: Partial<ListingWizardDraft>, stepIndex?: number }
    if (!parsed.draft || typeof parsed.draft !== 'object') {
      return null
    }

    const rawDraft = parsed.draft as Partial<ListingWizardDraft> & {
      address?: string
    }
    const legacyDealType = (parsed.draft as { dealType?: unknown }).dealType
    const { address: legacyAddress, ...rest } = rawDraft
    const draft = { ...createEmptyWizardDraft(), ...rest }
    draft.absent = { ...createEmptyLocationAbsent(), ...(rest.absent ?? {}) }
    delete (draft.absent as Record<string, boolean>).city
    delete (draft.absent as Record<string, boolean>).region
    if (legacyDealType === 'commercial') {
      draft.dealType = 'sale'
      draft.listingType = 'commercial'
    }
    draft.metroLineColor = normalizeMetroLineColor(draft.metroLineColor)
    if (typeof draft.fromOwner !== 'boolean') {
      draft.fromOwner = null
    }
    if (!draft.street.trim() && legacyAddress) {
      draft.street = String(legacyAddress).trim()
    }
    if (draft.price !== null && draft.price > 0 && (draft.priceByn === null || draft.priceByn <= 0)) {
      draft.priceByn = convertFromUsd(draft.price, 'byn')
    } else if (draft.priceByn !== null && draft.priceByn > 0 && (draft.price === null || draft.price <= 0)) {
      draft.price = convertToUsd(draft.priceByn, 'byn')
    }
    let stepIndex = Math.max(0, Number(parsed.stepIndex) || 0)
    if (stepIndex >= WIZARD_STEPS.length) {
      stepIndex = WIZARD_STEPS.length - 1
    }

    return { draft, stepIndex }
  } catch {
    return null
  }
}

export function clearWizardDraftLocal(): void {
  localStorage.removeItem(WIZARD_DRAFT_STORAGE_KEY)
}

export function isWizardDraftEmpty(draft: ListingWizardDraft): boolean {
  const empty = createEmptyWizardDraft()
  return (
    draft.dealType === empty.dealType
    && draft.listingType === empty.listingType
    && draft.region.trim() === ''
    && draft.city.trim() === ''
    && draft.district.trim() === ''
    && draft.metro.trim() === ''
    && draft.street.trim() === ''
    && draft.house.trim() === ''
    && draft.entrance.trim() === ''
    && draft.apartmentNumber.trim() === ''
    && draft.rooms === null
    && draft.area === null
    && draft.floor === null
    && draft.totalFloors === null
    && draft.price === null
    && draft.priceByn === null
    && draft.images.length === 0
  )
}

export function resolveRegionLabel(
  regionSlug: string | undefined,
  translate: (key: string) => string,
  fallback = '',
): string {
  if (!regionSlug) {
    return fallback
  }
  const key = `map.regions.${regionSlug}`
  const translated = translate(key)
  return translated !== key ? translated : fallback
}

export function filterSuggestOptions(options: string[], query: string, limit = 8): string[] {
  const normalized = query.trim().toLocaleLowerCase('ru')
  if (!normalized) {
    return options.slice(0, limit)
  }

  return options
    .filter((option) => option.toLocaleLowerCase('ru').includes(normalized))
    .slice(0, limit)
}

export function matchClosestOption(options: string[], value: string): string {
  const normalized = value.trim().toLocaleLowerCase('ru')
  if (!normalized) {
    return ''
  }

  const exact = options.find((option) => option.toLocaleLowerCase('ru') === normalized)
  if (exact) {
    return exact
  }

  const partial = options.find((option) => {
    const optionNorm = option.toLocaleLowerCase('ru')
    return optionNorm.includes(normalized) || normalized.includes(optionNorm)
  })

  return partial ?? value.trim()
}
