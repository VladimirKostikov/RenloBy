import { isSafeHttpUrl } from '@/lib/safeLinks'

function normalizeHandle(value: string): string {
  return value.trim().replace(/^@/, '')
}

export function telegramProfileHref(raw: string | null | undefined): string | null {
  if (!raw?.trim()) {
    return null
  }
  const value = normalizeHandle(raw)
  if (isSafeHttpUrl(value)) {
    return value
  }
  if (value.startsWith('http://') || value.startsWith('https://')) {
    return null
  }
  const username = value
    .replace(/^https?:\/\/(www\.)?t\.me\//i, '')
    .replace(/\/$/, '')
    .split(/[/?#]/)[0]
  if (!username || /[^\w+]/.test(username)) {
    return null
  }
  return `https://t.me/${username}`
}

export function instagramProfileHref(raw: string | null | undefined): string | null {
  if (!raw?.trim()) {
    return null
  }
  const value = normalizeHandle(raw)
  if (isSafeHttpUrl(value)) {
    return value
  }
  if (value.startsWith('http://') || value.startsWith('https://')) {
    return null
  }
  const username = value
    .replace(/^https?:\/\/(www\.)?instagram\.com\//i, '')
    .replace(/\/$/, '')
    .split(/[/?#]/)[0]
  if (!username || /[^\w.]/.test(username)) {
    return null
  }
  return `https://instagram.com/${username}`
}

export function whatsappProfileHref(raw: string | null | undefined): string | null {
  if (!raw?.trim()) {
    return null
  }
  const digits = raw.replace(/\D/g, '')
  return digits ? `https://wa.me/${digits}` : null
}

export function viberProfileHref(raw: string | null | undefined): string | null {
  if (!raw?.trim()) {
    return null
  }
  const digits = raw.replace(/[^\d+]/g, '')
  return digits ? `viber://chat?number=${encodeURIComponent(digits)}` : null
}
