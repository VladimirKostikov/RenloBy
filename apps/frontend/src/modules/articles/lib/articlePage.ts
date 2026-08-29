import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'
import type { ArticleMediaItem } from '@/types/article'

export function formatArticleDate(value: string, locale = 'ru'): string {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

export function articleGalleryItems(
  media: ArticleMediaItem[] | null | undefined,
  coverImage: string | null | undefined,
): ArticleMediaItem[] {
  const cover = coverImage?.trim() ?? ''
  const items = media ?? []

  return items.filter((item) => {
    if (!item.url || !isSafeMediaUrl(item.url)) {
      return false
    }
    if (cover && item.url === cover) {
      return false
    }
    return item.type === 'image' || item.type === 'video'
  })
}
