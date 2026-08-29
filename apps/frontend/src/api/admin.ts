import apiClient from './client'
import type { PaginatedResponse } from '@/types'

export interface AdminCrudApi<T, CreatePayload = Partial<T>, UpdatePayload = Partial<T>> {
  list: (params?: Record<string, unknown>) => Promise<T[] | PaginatedResponse<T>>
  get: (id: number) => Promise<T>
  create: (payload: CreatePayload) => Promise<T>
  update: (id: number, payload: UpdatePayload) => Promise<T>
  remove: (id: number) => Promise<void>
}

function createAdminCrud<T, CreatePayload = Partial<T>, UpdatePayload = Partial<T>>(
  resource: string,
): AdminCrudApi<T, CreatePayload, UpdatePayload> {
  const base = `/admin/${resource}`

  return {
    async list(params) {
      const { data } = await apiClient.get<T[] | PaginatedResponse<T>>(base, { params })
      if (Array.isArray(data)) {
        return data
      }
      return data
    },
    async get(id) {
      const { data } = await apiClient.get<T>(`${base}/${id}`)
      return data
    },
    async create(payload) {
      const { data } = await apiClient.post<T>(base, payload)
      return data
    },
    async update(id, payload) {
      const { data } = await apiClient.patch<T>(`${base}/${id}`, payload)
      return data
    },
    async remove(id) {
      await apiClient.delete(`${base}/${id}`)
    },
  }
}

export const adminUsers = createAdminCrud<import('@/types').UserDto>('users')

export async function uploadAdminUserPhoto(userId: number, file: File): Promise<import('@/types').UserDto> {
  const body = new FormData()
  body.append('file', file)
  const { data } = await apiClient.post<import('@/types').UserDto>(`/admin/users/${userId}/photo`, body, {
    headers: {
      'Content-Type': undefined,
    },
  })
  return data
}
export const adminListings = createAdminCrud<import('@/types').ListingDto>('listings')
export const adminCities = createAdminCrud<{ id: number; name: string; slug: string; regionSlug: string }>('cities')
export const adminDistricts = createAdminCrud<{ id: number; name: string; slug: string; cityId: number }>('districts')
export const adminMetroStations = createAdminCrud<{ id: number; name: string; slug: string; lineColor: string; cityId: number }>('metro-stations')
export const adminFavorites = createAdminCrud<import('@/types').FavoriteDto>('favorites')
export const adminComparisons = createAdminCrud<import('@/types').ComparisonDto>('comparisons')
export const adminSavedSearches = createAdminCrud<{ id: number; userId: number; name: string; filters: Record<string, unknown> }>('saved-searches')
export const adminInfoPages = createAdminCrud<import('@/types/info').InfoPageDto>('info-pages')
export const adminArticles = createAdminCrud<import('@/types/article').ArticleDto>('articles')
export const adminSeoMeta = createAdminCrud<{
  id: number
  pageKey: string
  locale: string
  title: string
  description: string
  h1: string | null
  keywords: string | null
  isTest?: boolean
}>('seo-meta')
export const adminPaymentTransactions = createAdminCrud<{
  id: number
  userId: number
  amount: string
  currency: string
  status: string
  provider: string
  description: string | null
  isTest: boolean
  createdAt: string
}>('payment-transactions')

export type MediaFileDto = {
  id: number
  url: string
  type: string
  mimeType: string
  size: number
  context: string
  uploadedById: number | null
  uploadedByEmail: string | null
  originalName: string | null
  isTest: boolean
  createdAt: string
}

export const adminMediaFiles = createAdminCrud<MediaFileDto>('media-files')

export type ListingReportDto = {
  id: number
  listingId: number
  reason: string
  comment: string | null
  status: string
  createdAt: string
  isTest: boolean
  listingAddress?: string | null
}

export const adminListingReports = createAdminCrud<ListingReportDto, Partial<ListingReportDto>, { status?: string }>('listing-reports')

export type ListingRequestAdminDto = {
  id: number
  listingId: number
  name: string | null
  phone: string
  message: string
  status: string
  createdAt: string
  isTest: boolean
  listingAddress?: string | null
}

export const adminListingRequests = createAdminCrud<ListingRequestAdminDto, Partial<ListingRequestAdminDto>, { status?: string }>('listing-requests')

export type UserNotificationAdminDto = {
  id: number
  type: string
  payload: Record<string, unknown>
  isRead: boolean
  createdAt: string
  isTest: boolean
  userId?: number | null
}

export const adminUserNotifications = createAdminCrud<UserNotificationAdminDto>('user-notifications')

export type TariffDto = {
  id: number
  code: string
  priceUsd: string
  priceByn?: string
  priceRub?: string
  currency: string
  isPopular: boolean
  sortOrder: number
  isTest: boolean
}

export const adminTariffs = createAdminCrud<
  TariffDto,
  Partial<TariffDto>,
  Partial<Pick<TariffDto, 'priceUsd' | 'priceByn' | 'priceRub' | 'currency' | 'isPopular' | 'sortOrder' | 'isTest'>>
>('tariffs')

export type SiteSettingsDto = {
  id: number
  aboutText: string
  phoneDisplay: string
  phoneRaw: string
  email: string
  supportHours: string
  ownerName: string | null
  address: string | null
  offersText: string | null
  offersEmail: string | null
  telegramUrl: string | null
  whatsappUrl: string | null
  vkUrl: string | null
  isTest: boolean
}

export const adminSiteSettings = createAdminCrud<
  SiteSettingsDto,
  Partial<SiteSettingsDto>,
  Partial<Omit<SiteSettingsDto, 'id'>>
>('site-settings')

export type HeadSnippetDto = {
  id: number
  name: string
  code: string
  isEnabled: boolean
  sortOrder: number
  isTest: boolean
}

export const adminHeadSnippets = createAdminCrud<HeadSnippetDto>('head-snippets')

export type TelegramSubscriberDto = {
  id: number
  chatId: string
  username: string | null
  firstName: string | null
  isActive: boolean
  connectedAt: string
}

export type TelegramStatusDto = {
  configured: boolean
  botUsername: string
  connectUrl: string
  webhookUrl?: string
  webhookPendingUpdateCount?: number
  webhookLastError?: string | null
  subscribers: TelegramSubscriberDto[]
}

export type TelegramSyncResultDto = TelegramStatusDto & {
  processed: number
  connected: number
}

export async function fetchTelegramStatus(): Promise<TelegramStatusDto> {
  const { data } = await apiClient.get<TelegramStatusDto>('/admin/telegram/status')
  return data
}

export async function syncTelegramUpdates(): Promise<TelegramSyncResultDto> {
  const { data } = await apiClient.post<TelegramSyncResultDto>('/admin/telegram/sync')
  return data
}

export async function updateTelegramSubscriber(
  id: number,
  payload: { isActive: boolean },
): Promise<TelegramSubscriberDto> {
  const { data } = await apiClient.patch<TelegramSubscriberDto>(`/admin/telegram/subscribers/${id}`, payload)
  return data
}

export async function deleteTelegramSubscriber(id: number): Promise<void> {
  await apiClient.delete(`/admin/telegram/subscribers/${id}`)
}

export async function exportUserEmailsCsv(): Promise<Blob> {
  const { data } = await apiClient.get<Blob>('/admin/users/export-emails', {
    responseType: 'blob',
  })
  return data
}
