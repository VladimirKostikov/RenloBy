export type DealType = 'sale' | 'rent'
export type ListingType = 'apartment' | 'house' | 'room' | 'commercial'
export type RentTerm = 'daily' | 'long'
export type ListingStatus = 'draft' | 'pending' | 'published' | 'rejected' | 'archived'

export interface PaginatedResponse<T> {
  items: T[]
  total: number
  page: number
  limit: number
}

export interface ListingSellerDto {
  id: number
  name: string
  photo: string | null
  phone: string | null
  instagram?: string | null
  telegram: string | null
  whatsapp: string | null
  viber: string | null
}

export interface ListingDto {
  id: number
  dealType: DealType
  listingType: ListingType
  status: ListingStatus
  price: number
  pricePerSqm: number
  rooms: number
  area: number
  floor: number | null
  totalFloors: number | null
  address: string
  latitude: number
  longitude: number
  metroMinutes: number | null
  verified: boolean
  aiGoodPrice: boolean
  rentTerm: RentTerm | null
  hasDeposit: boolean
  utilitiesIncluded: boolean
  noCommission: boolean
  fromOwner: boolean
  hasRenovation: boolean
  priceNegotiable: boolean
  views: number
  images: string[]
  metaTitle?: string | null
  metaDescription?: string | null
  metaKeywords?: string | null
  publishedAt: string
  userId: number
  cityId: number
  districtId: number | null
  metroStationId: number | null
  cityName?: string
  districtName?: string
  metroStationName?: string | null
  isTest?: boolean
  seller?: ListingSellerDto | null
}

export interface ListingSearchParams {
  dealType?: DealType
  listingType?: ListingType
  cityId?: number
  regionSlug?: string
  districtId?: number
  rooms?: number
  floor?: number
  minArea?: number
  maxArea?: number
  minPrice?: number
  maxPrice?: number
  verified?: boolean
  rentTerm?: RentTerm
  hasDeposit?: boolean
  utilitiesIncluded?: boolean
  noCommission?: boolean
  fromOwner?: boolean
  hasRenovation?: boolean
  query?: string
  sort?: string
  direction?: 'ASC' | 'DESC'
  page?: number
  limit?: number
}

export interface CityDto {
  id: number
  name: string
  slug: string
  regionSlug: string
}

export interface DistrictDto {
  id: number
  name: string
  slug: string
  cityId: number
}

export interface MetroStationDto {
  id: number
  name: string
  slug: string
  lineColor: string
  cityId: number
}

export interface UserDto {
  id: number
  email: string
  name: string
  roles: string[]
  lastName?: string | null
  firstName?: string | null
  patronymic?: string | null
  photo?: string | null
  phone?: string | null
  instagram?: string | null
  telegram?: string | null
  whatsapp?: string | null
  viber?: string | null
  isTest?: boolean
}

export interface LoginRequest {
  email: string
  password: string
}

export interface RegisterRequest {
  email: string
  password: string
}

export interface FavoriteDto {
  id: number
  userId: number | null
  listingId: number
}

export interface ComparisonDto {
  id: number
  userId: number | null
  listingId: number
}

export interface FavoriteItemDto {
  id: number
  userId: number | null
  listingId: number
  listing: ListingDto
}

export interface ComparisonItemDto {
  id: number
  userId: number | null
  listingId: number
  listing: ListingDto
}

export interface CollectionToggleResponse {
  active: boolean
  item?: FavoriteDto | ComparisonDto
}

export interface CollectionListResponse<T> {
  items: T[]
}

export interface SavedSearchDto {
  id: number
  userId: number
  name: string
  filters: Record<string, unknown>
}

export type { ThemeMode, PaletteId } from '@/modules/theme/lib/palettes'
export type CurrencyCode = 'usd' | 'byn'
