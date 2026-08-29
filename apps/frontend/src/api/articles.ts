import apiClient from '@/api/client'
import type { ArticleDto } from '@/types/article'

export async function fetchArticles(): Promise<ArticleDto[]> {
  const { data } = await apiClient.get<ArticleDto[]>('/api/articles')
  return data
}

export async function fetchArticle(slug: string): Promise<ArticleDto> {
  const { data } = await apiClient.get<ArticleDto>(`/api/articles/${slug}`)
  return data
}
