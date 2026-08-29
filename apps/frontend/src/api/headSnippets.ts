import apiClient from './client'

export type PublicHeadSnippet = {
  code: string
}

export async function fetchHeadSnippets(): Promise<PublicHeadSnippet[]> {
  const { data } = await apiClient.get<PublicHeadSnippet[]>('/api/head-snippets')
  return Array.isArray(data) ? data : []
}
