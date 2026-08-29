import { describe, expect, it } from 'vitest'
import { formatInfoUpdatedAt, slugToCategory } from '@/modules/info/lib/infoPageNav'

describe('infoPageNav', () => {
  it('maps slug to category', () => {
    expect(slugToCategory('deal-safety')).toBe('deal_safety')
    expect(slugToCategory('buyers')).toBe('buyers')
    expect(slugToCategory('personal-data')).toBe('personal_data')
    expect(slugToCategory('offer')).toBe('offer')
    expect(slugToCategory('privacy')).toBe('privacy')
    expect(slugToCategory('unknown')).toBeNull()
  })

  it('formats updated date for ru locale', () => {
    const formatted = formatInfoUpdatedAt('2025-05-20', 'ru')
    expect(formatted).toContain('2025')
    expect(formatted).toContain('20')
  })
})
