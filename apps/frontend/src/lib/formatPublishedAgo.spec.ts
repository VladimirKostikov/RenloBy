import { describe, expect, it } from 'vitest'
import { formatPublishedAgo } from '@/lib/formatPublishedAgo'

describe('formatPublishedAgo', () => {
  it('formats minutes ago', () => {
    const now = Date.parse('2026-07-14T12:00:00.000Z')
    const published = '2026-07-14T11:45:00.000Z'
    expect(formatPublishedAgo(published, now)).toBe('15 мин. назад')
  })

  it('formats hours ago', () => {
    const now = Date.parse('2026-07-14T12:00:00.000Z')
    const published = '2026-07-14T10:00:00.000Z'
    expect(formatPublishedAgo(published, now)).toBe('2 часа назад')
  })

  it('returns empty string for invalid date', () => {
    expect(formatPublishedAgo('invalid')).toBe('')
  })
})
