import { describe, expect, it } from 'vitest'
import { formatRegisteredAt, resolveLastSeen } from '@/lib/resolveLastSeen'

describe('resolveLastSeen', () => {
  const now = Date.parse('2026-07-16T12:00:00.000Z')

  it('marks recent activity as online', () => {
    expect(resolveLastSeen('2026-07-16T11:57:00.000Z', now)).toEqual({ kind: 'online' })
  })

  it('returns minutes and hours buckets', () => {
    expect(resolveLastSeen('2026-07-16T11:40:00.000Z', now)).toEqual({ kind: 'minutes', value: 20 })
    expect(resolveLastSeen('2026-07-16T09:00:00.000Z', now)).toEqual({ kind: 'hours', value: 3 })
  })

  it('returns days for recent inactivity under a month', () => {
    expect(resolveLastSeen('2026-07-06T12:00:00.000Z', now)).toEqual({ kind: 'days', value: 10 })
  })

  it('marks long absence as unknown', () => {
    expect(resolveLastSeen('2026-05-01T12:00:00.000Z', now)).toEqual({ kind: 'unknown' })
  })

  it('handles missing date', () => {
    expect(resolveLastSeen(null, now)).toEqual({ kind: 'unknown' })
  })
})

describe('formatRegisteredAt', () => {
  it('formats registration date for ru locale', () => {
    expect(formatRegisteredAt('2025-03-12T10:00:00.000Z', 'ru')).toMatch(/2025/)
    expect(formatRegisteredAt('2025-03-12T10:00:00.000Z', 'ru')).toMatch(/12/)
  })

  it('returns null for invalid dates', () => {
    expect(formatRegisteredAt(null)).toBeNull()
    expect(formatRegisteredAt('not-a-date')).toBeNull()
  })
})
