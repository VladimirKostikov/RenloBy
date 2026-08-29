import apiClient from './client'

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

export async function fetchSiteSettings(): Promise<SiteSettingsDto> {
  const { data } = await apiClient.get<SiteSettingsDto>('/api/site-settings')
  return data
}
