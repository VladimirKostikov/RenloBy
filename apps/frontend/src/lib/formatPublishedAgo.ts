const MINUTE = 60_000
const HOUR = 3_600_000
const DAY = 86_400_000

export function formatPublishedAgo(isoDate: string, now = Date.now()): string {
  const published = Date.parse(isoDate)
  if (!Number.isFinite(published)) {
    return ''
  }

  const diff = Math.max(0, now - published)

  if (diff < HOUR) {
    const minutes = Math.max(1, Math.floor(diff / MINUTE))
    return `${minutes} мин. назад`
  }

  if (diff < DAY) {
    const hours = Math.max(1, Math.floor(diff / HOUR))
    return `${hours} ${pluralHours(hours)} назад`
  }

  const days = Math.max(1, Math.floor(diff / DAY))
  return `${days} ${pluralDays(days)} назад`
}

function pluralHours(count: number): string {
  const mod10 = count % 10
  const mod100 = count % 100
  if (mod10 === 1 && mod100 !== 11) {
    return 'час'
  }
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) {
    return 'часа'
  }
  return 'часов'
}

function pluralDays(count: number): string {
  const mod10 = count % 10
  const mod100 = count % 100
  if (mod10 === 1 && mod100 !== 11) {
    return 'день'
  }
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) {
    return 'дня'
  }
  return 'дней'
}
