import apiClient from './client'
import { isMediaFileWithinLimit } from '@/lib/mediaUploadLimits'
import type {
  DealType,
  ListingDto,
  ListingStatus,
  ListingType,
  PaginatedResponse,
  RentTerm,
  UserDto,
} from '@/types'

export interface AccountSummaryDto {
  listingsCount: number
  favoritesCount: number
  comparisonsCount: number
  savedSearchesCount: number
}

export interface UpdateProfilePayload {
  lastName?: string | null
  firstName?: string | null
  patronymic?: string | null
  phone?: string | null
  photo?: string | null
  instagram?: string | null
  telegram?: string | null
  whatsapp?: string | null
  viber?: string | null
}

export interface CreateSellerListingPayload {
  dealType: DealType
  listingType: ListingType
  price: number
  rooms: number
  area: number
  floor?: number | null
  totalFloors?: number | null
  address: string
  latitude: number
  longitude: number
  city: string
  district?: string | null
  metro?: string | null
  metroLineColor?: string | null
  metroMinutes?: number | null
  rentTerm?: RentTerm | null
  hasDeposit?: boolean
  utilitiesIncluded?: boolean
  noCommission?: boolean
  fromOwner?: boolean
  hasRenovation?: boolean
  priceNegotiable?: boolean
  images?: string[]
  status?: ListingStatus
  metaTitle?: string | null
  metaDescription?: string | null
  metaKeywords?: string | null
}

export interface SellerAnalyticsDto {
  listingsCount: number
  publishedCount: number
  draftCount: number
  archivedCount: number
  totalViews: number
  avgViews: number
  byDealType: Record<string, number>
  byStatus: Record<string, number>
  topListings: Array<{
    id: number
    address: string
    views: number
    price: number
    dealType: string
    status: string
  }>
}

export async function fetchAccountSummary(): Promise<AccountSummaryDto> {
  const { data } = await apiClient.get<AccountSummaryDto>('/api/me/summary')
  return data
}

export async function fetchMyListings(params?: {
  page?: number
  limit?: number
  status?: ListingStatus
}): Promise<PaginatedResponse<ListingDto>> {
  const { data } = await apiClient.get<PaginatedResponse<ListingDto>>('/api/me/listings', { params })
  return data
}

export async function createMyListing(payload: CreateSellerListingPayload): Promise<ListingDto> {
  const { data } = await apiClient.post<ListingDto>('/api/me/listings', payload)
  return data
}

export async function updateMyListing(
  id: number,
  payload: Partial<CreateSellerListingPayload>,
): Promise<ListingDto> {
  const { data } = await apiClient.patch<ListingDto>(`/api/me/listings/${id}`, payload)
  return data
}

export async function publishMyListing(id: number): Promise<ListingDto> {
  const { data } = await apiClient.post<ListingDto>(`/api/me/listings/${id}/publish`)
  return data
}

export async function archiveMyListing(id: number): Promise<ListingDto> {
  const { data } = await apiClient.post<ListingDto>(`/api/me/listings/${id}/archive`)
  return data
}

export async function deleteMyDraftListing(id: number): Promise<void> {
  await apiClient.delete(`/api/me/listings/${id}`)
}

export type SellerListingReportDto = {
  id: number
  listingId: number
  reason: string
  comment: string | null
  status: string
  createdAt: string
  listingAddress?: string | null
}

export async function fetchMyListingReports(): Promise<SellerListingReportDto[]> {
  const { data } = await apiClient.get<SellerListingReportDto[]>('/api/me/listing-reports')
  return data
}

export type SellerListingRequestDto = {
  id: number
  listingId: number
  name: string | null
  phone: string
  message: string
  status: string
  createdAt: string
  listingAddress?: string | null
}

export async function fetchMyListingRequests(): Promise<SellerListingRequestDto[]> {
  const { data } = await apiClient.get<SellerListingRequestDto[]>('/api/me/listing-requests')
  return data
}

export async function deleteMyListingRequest(id: number): Promise<void> {
  await apiClient.delete(`/api/me/listing-requests/${id}`)
}

export interface ListingAnalyticsOptionDto {
  id: number
  title: string
  address: string
  image: string | null
  rooms: number
  area: number
  status: string
  views: number
}

export interface ListingAnalyticsDetailDto {
  listing: ListingAnalyticsOptionDto
  updatedAt: string
  views: {
    day: number
    week: number
    month: number
    dayChangePct: number
    weekChangePct: number
    monthChangePct: number
  }
  contactOpensWeek: number
  contactOpensChangePct: number
  messagesWeek: number
  messagesChangePct: number
  conversionPct: number
  conversionChangePct: number
  viewsSeries: Array<{ date: string; views: number; average: number }>
  funnel: {
    views: number
    contacts: number
    messages: number
    viewToContactPct: number
    contactToMessagePct: number
  }
  promotion: {
    active: boolean
    tariff: string | null
    rows: Array<{ metric: string; before: number; after: number; growthPct: number }>
  }
  engagement: {
    contactsTotal: number
    messagesTotal: number
    contactsAvg: number
    contactsPeak: number
    series: Array<{ date: string; contacts: number; messages: number }>
  }
}

export async function fetchSellerAnalytics(): Promise<SellerAnalyticsDto> {
  const { data } = await apiClient.get<SellerAnalyticsDto>('/api/me/analytics')
  return data
}

export async function fetchListingAnalyticsOptions(params?: {
  page?: number
  limit?: number
  q?: string
}): Promise<PaginatedResponse<ListingAnalyticsOptionDto>> {
  const { data } = await apiClient.get<PaginatedResponse<ListingAnalyticsOptionDto>>(
    '/api/me/analytics/listings',
    { params },
  )
  return {
    items: Array.isArray(data?.items) ? data.items : [],
    total: typeof data?.total === 'number' ? data.total : 0,
    page: typeof data?.page === 'number' ? data.page : 1,
    limit: typeof data?.limit === 'number' ? data.limit : 20,
  }
}

export async function fetchListingAnalyticsDetail(
  id: number,
  range: 'day' | 'week' | 'month' = 'week',
): Promise<ListingAnalyticsDetailDto> {
  const { data } = await apiClient.get<ListingAnalyticsDetailDto>(`/api/me/analytics/listings/${id}`, {
    params: { range },
  })
  return data
}

export async function recordListingContactEvent(
  id: number,
  type: 'contact' | 'message' = 'contact',
): Promise<void> {
  await apiClient.post(`/api/listings/${id}/events/contact`, { type })
}

export async function updateProfile(payload: UpdateProfilePayload): Promise<UserDto> {
  const { data } = await apiClient.patch<UserDto>('/api/auth/me', payload)
  return data
}

export async function uploadProfilePhoto(file: File): Promise<UserDto> {
  const body = new FormData()
  body.append('file', file)
  const { data } = await apiClient.post<UserDto>('/api/auth/me/photo', body, {
    headers: {
      'Content-Type': undefined,
    },
  })
  return data
}

export class ListingMediaTooLargeError extends Error {
  constructor() {
    super('validation.media_file_too_large')
    this.name = 'ListingMediaTooLargeError'
  }
}

export type ListingMediaUploadResult = {
  url: string
  type: 'image' | 'video'
  mimeType: string
  size: number
}

export async function uploadListingMedia(file: File): Promise<ListingMediaUploadResult> {
  if (!isMediaFileWithinLimit(file)) {
    throw new ListingMediaTooLargeError()
  }

  const body = new FormData()
  body.append('file', file)
  const { data } = await apiClient.post<ListingMediaUploadResult>('/api/me/media/upload', body, {
    headers: {
      'Content-Type': undefined,
    },
  })
  return data
}

export interface SellerTelegramStatusDto {
  configured: boolean
  connected: boolean
  botUsername: string
  connectUrl: string
  username: string | null
  connectedAt: string | null
}

export async function fetchSellerTelegramStatus(): Promise<SellerTelegramStatusDto> {
  const { data } = await apiClient.get<SellerTelegramStatusDto>('/api/me/telegram')
  return data
}

export async function disconnectSellerTelegram(): Promise<SellerTelegramStatusDto> {
  const { data } = await apiClient.post<SellerTelegramStatusDto>('/api/me/telegram/disconnect')
  return data
}
