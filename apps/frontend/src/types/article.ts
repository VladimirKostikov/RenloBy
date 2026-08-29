export type ArticleCategory = 'guides' | 'market' | 'tips' | 'law'

export type ArticleMediaType = 'image' | 'video'

export interface ArticleMediaItem {
  url: string
  type: ArticleMediaType
}

export interface ArticleDto {
  id: number
  slug: string
  title: string
  excerpt: string
  body: string
  category: ArticleCategory
  coverImage: string | null
  media: ArticleMediaItem[]
  isPublished: boolean
  publishedAt: string
  metaTitle: string | null
  metaDescription: string | null
  updatedAt: string
  isTest?: boolean
}

export interface MediaUploadResult {
  url: string
  type: ArticleMediaType
  mimeType: string
  size: number
}
