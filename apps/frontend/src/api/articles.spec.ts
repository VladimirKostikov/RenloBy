import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as articlesApi from '@/api/articles'

vi.mock('@/api/articles', () => ({
  fetchArticles: vi.fn(),
  fetchArticle: vi.fn(),
}))

describe('articles api', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads published articles list', async () => {
    vi.mocked(articlesApi.fetchArticles).mockResolvedValue([
      {
        id: 1,
        slug: 'test',
        title: 'Test',
        excerpt: 'Excerpt',
        body: 'Body',
        category: 'guides',
        coverImage: null,
        media: [],
        isPublished: true,
        publishedAt: '2026-07-01',
        metaTitle: null,
        metaDescription: null,
        updatedAt: '2026-07-01',
      },
    ])

    const articles = await articlesApi.fetchArticles()
    expect(articles).toHaveLength(1)
    expect(articles[0]?.slug).toBe('test')
  })
})
