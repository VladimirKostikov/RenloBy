import type { SeoLocale } from './types'

export const SITE_NAME = 'Renlo'

export const LOGO_MARK_SVG = '/figma/logomark.svg'

export const LOGO_MARK_PNG = '/figma/logomark.png'

export function getSiteUrl(): string {
  const fromEnv = import.meta.env.VITE_SITE_URL?.trim()
  if (fromEnv) {
    return fromEnv.replace(/\/+$/, '')
  }

  if (typeof window !== 'undefined' && window.location.origin) {
    return window.location.origin
  }

  return 'https://renlo.by'
}

export function buildAbsoluteUrl(path: string, siteUrl = getSiteUrl()): string {
  if (/^https?:\/\//i.test(path)) {
    return path
  }

  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return `${siteUrl}${normalizedPath}`
}

export function getDefaultOgImage(siteUrl = getSiteUrl()): string {
  return `${siteUrl}${LOGO_MARK_PNG}`
}

export function localeToOgLocale(locale: SeoLocale): string {
  return locale === 'en' ? 'en_US' : 'ru_RU'
}

export function localeToHtmlLang(locale: SeoLocale): SeoLocale {
  return locale
}

export function buildHreflang(path: string, siteUrl = getSiteUrl()) {
  const href = buildAbsoluteUrl(path, siteUrl)
  return [
    { locale: 'ru' as const, href },
    { locale: 'en' as const, href },
    { locale: 'x-default' as const, href },
  ]
}
