import { describe, expect, it } from 'vitest'
import { articleGalleryItems, formatArticleDate } from '@/modules/articles/lib/articlePage'

describe('articlePage helpers', () => {
  it('formats published date for ru locale', () => {
    expect(formatArticleDate('2026-07-10', 'ru')).toMatch(/10/)
    expect(formatArticleDate('2026-07-10', 'ru')).toMatch(/2026/)
  })

  it('returns original value for invalid date', () => {
    expect(formatArticleDate('not-a-date')).toBe('not-a-date')
  })

  it('filters unsafe urls and cover duplicates from gallery', () => {
    const cover = '/uploads/articles/cover.jpg'
    const items = articleGalleryItems(
      [
        { url: cover, type: 'image' },
        { url: '/uploads/articles/extra.jpg', type: 'image' },
        { url: 'javascript:alert(1)', type: 'image' },
        { url: '/uploads/articles/clip.mp4', type: 'video' },
      ],
      cover,
    )

    expect(items).toEqual([
      { url: '/uploads/articles/extra.jpg', type: 'image' },
      { url: '/uploads/articles/clip.mp4', type: 'video' },
    ])
  })
})
