const MINUTE = 60_000
const HOUR = 3_600_000
const DAY = 86_400_000
const UNKNOWN_AFTER_DAYS = 30

export type LastSeenKind = 'online' | 'minutes' | 'hours' | 'days' | 'unknown'

export type LastSeenInfo = {
  kind: LastSeenKind
  value?: number
}

export function resolveLastSeen(isoDate: string | null | undefined, now = Date.now()): LastSeenInfo {
  if (!isoDate) {
    return { kind: 'unknown' }
  }

  const seen = Date.parse(isoDate)
  if (!Number.isFinite(seen)) {
    return { kind: 'unknown' }
  }

  const diff = Math.max(0, now - seen)

  if (diff < 5 * MINUTE) {
    return { kind: 'online' }
  }

  if (diff < HOUR) {
    return { kind: 'minutes', value: Math.max(1, Math.floor(diff / MINUTE)) }
  }

  if (diff < DAY) {
    return { kind: 'hours', value: Math.max(1, Math.floor(diff / HOUR)) }
  }

  const days = Math.max(1, Math.floor(diff / DAY))
  if (days >= UNKNOWN_AFTER_DAYS) {
    return { kind: 'unknown' }
  }

  return { kind: 'days', value: days }
}

export function formatRegisteredAt(isoDate: string | null | undefined, locale = 'ru'): string | null {
  if (!isoDate) {
    return null
  }

  const date = new Date(isoDate)
  if (Number.isNaN(date.getTime())) {
    return null
  }

  return new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}
