export function isSafeHttpUrl(url: string | null | undefined): boolean {
  if (!url) {
    return false
  }
  try {
    const parsed = new URL(url)
    return parsed.protocol === 'https:' || parsed.protocol === 'http:'
  } catch {
    return false
  }
}

export function isSafeTelHref(url: string | null | undefined): boolean {
  if (!url) {
    return false
  }
  return /^tel:\+?[0-9()[\]\-\s]+$/.test(url)
}
