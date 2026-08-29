import apiClient from './client'

export interface SeoMetaDto {
  id: number
  pageKey: string
  locale: string
  title: string
  description: string
  h1: string | null
  keywords: string | null
}

export async function fetchSeoMeta(locale: string): Promise<SeoMetaDto[]> {
  const { data } = await apiClient.get<SeoMetaDto[]>('/api/seo-meta', {
    params: { locale },
  })
  return Array.isArray(data) ? data : []
}
