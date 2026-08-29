import { buildPageMeta, normalizePath } from './buildPageMeta'
import { getSiteUrl } from './siteConfig'
import type { SeoBuildInput, SeoLocale } from './types'

export function buildRoutePageMeta(
  path: string,
  locale: SeoLocale,
  extras: Omit<SeoBuildInput, 'locale' | 'path' | 'siteUrl'> = {},
) {
  return buildPageMeta({
    locale,
    path: normalizePath(path),
    siteUrl: getSiteUrl(),
    ...extras,
  })
}

export { buildPageMeta, getPageH1, normalizePath, resolvePageKind } from './buildPageMeta'
export { getSeoMessages } from './seoMessages'
export { getSiteUrl, buildAbsoluteUrl, getDefaultOgImage } from './siteConfig'
export { injectHeadIntoHtml, getBaseHeadLines, extractViteAssetTags } from './renderHeadHtml'
export { pageMetaToHead } from './pageMetaToHead'
export { usePageSeo } from './usePageSeo'
export type * from './types'
