export function isSafeMediaUrl(url: string): boolean {
  const value = url.trim()
  if (value === '') {
    return false
  }

  if (value.startsWith('/uploads/')) {
    return !value.includes('..') && /^\/uploads\/[a-z0-9/._-]+$/i.test(value)
  }

  try {
    const parsed = new URL(value)
    return parsed.protocol === 'http:' || parsed.protocol === 'https:'
  } catch {
    return false
  }
}
