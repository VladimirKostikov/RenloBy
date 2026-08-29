import apiClient from './client'
import type { ListingStatus } from '@/types'

export type UserNotificationType = 'listing_status_changed' | 'listing_contact_request_created'

export interface ListingStatusChangedPayload {
  listingId: number | null
  address: string
  previousStatus: ListingStatus | string
  status: ListingStatus | string
}

export interface ListingContactRequestPayload {
  listingId: number | null
  address: string
  requestId: number | null
  phone: string
  name?: string | null
  message: string
}

export interface UserNotificationDto {
  id: number
  type: UserNotificationType | string
  payload: ListingStatusChangedPayload | ListingContactRequestPayload | Record<string, unknown>
  isRead: boolean
  createdAt: string
  isTest: boolean
  userId?: number | null
}

export interface UnreadCountDto {
  count: number
}

export async function fetchNotifications(limit = 50): Promise<UserNotificationDto[]> {
  const { data } = await apiClient.get<UserNotificationDto[]>('/api/me/notifications', {
    params: { limit },
  })
  return data
}

export async function fetchUnreadNotificationCount(): Promise<number> {
  const { data } = await apiClient.get<UnreadCountDto>('/api/me/notifications/unread-count')
  return data.count
}

export async function markNotificationRead(id: number): Promise<UserNotificationDto> {
  const { data } = await apiClient.post<UserNotificationDto>(`/api/me/notifications/${id}/read`)
  return data
}

export async function markAllNotificationsRead(): Promise<number> {
  const { data } = await apiClient.post<UnreadCountDto>('/api/me/notifications/read-all')
  return data.count
}
