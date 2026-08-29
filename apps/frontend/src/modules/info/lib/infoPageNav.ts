import type { InfoPageCategory } from '@/types/info'

export const INFO_PAGE_CATEGORIES: InfoPageCategory[] = [
  'buyers',
  'sellers',
  'renters',
  'deal_safety',
  'faq',
  'support',
  'offer',
  'privacy',
  'personal_data',
]

export function categoryToSlug(category: InfoPageCategory): string {
  return category.replace(/_/g, '-')
}

export function slugToCategory(slug: string): InfoPageCategory | null {
  const normalized = slug.replace(/-/g, '_')
  if (INFO_PAGE_CATEGORIES.includes(normalized as InfoPageCategory)) {
    return normalized as InfoPageCategory
  }

  return null
}

export function formatInfoUpdatedAt(date: string, locale: string): string {
  const parsed = new Date(`${date}T00:00:00`)
  if (Number.isNaN(parsed.getTime())) {
    return date
  }

  return new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(parsed)
}
