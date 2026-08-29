import type { PageMeta } from './types'
import { LOGO_MARK_PNG, LOGO_MARK_SVG } from './siteConfig'

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

export function renderHeadInnerHtml(meta: PageMeta): string {
  const tags: string[] = [
    `<title>${escapeHtml(meta.title)}</title>`,
    `<meta name="description" content="${escapeHtml(meta.description)}">`,
  ]

  const keywords = meta.keywords?.trim()
  if (keywords) {
    tags.push(`<meta name="keywords" content="${escapeHtml(keywords)}">`)
  }

  tags.push(
    `<meta name="robots" content="${escapeHtml(meta.robots)}">`,
    `<link rel="canonical" href="${escapeHtml(meta.canonical)}">`,
    `<meta property="og:title" content="${escapeHtml(meta.ogTitle)}">`,
    `<meta property="og:description" content="${escapeHtml(meta.ogDescription)}">`,
    `<meta property="og:url" content="${escapeHtml(meta.ogUrl)}">`,
    `<meta property="og:type" content="${escapeHtml(meta.ogType)}">`,
    `<meta property="og:image" content="${escapeHtml(meta.ogImage)}">`,
    `<meta property="og:locale" content="${escapeHtml(meta.ogLocale)}">`,
    `<meta property="og:site_name" content="${escapeHtml(meta.ogSiteName)}">`,
    `<meta name="twitter:card" content="${escapeHtml(meta.twitterCard)}">`,
    `<meta name="twitter:title" content="${escapeHtml(meta.twitterTitle)}">`,
    `<meta name="twitter:description" content="${escapeHtml(meta.twitterDescription)}">`,
    `<meta name="twitter:image" content="${escapeHtml(meta.twitterImage)}">`,
  )

  for (const alternate of meta.hreflang) {
    tags.push(
      `<link rel="alternate" hreflang="${escapeHtml(alternate.locale)}" href="${escapeHtml(alternate.href)}">`,
    )
  }

  for (const schema of meta.jsonLd) {
    tags.push(`<script type="application/ld+json">${JSON.stringify(schema)}</script>`)
  }

  return tags.join('\n    ')
}

export function extractViteAssetTags(html: string): string[] {
  const headMatch = html.match(/<head>([\s\S]*?)<\/head>/i)
  if (!headMatch?.[1]) {
    return []
  }

  const tags: string[] = []
  const scriptMatches = headMatch[1].matchAll(/<script type="module"[^>]*><\/script>/g)
  const linkMatches = headMatch[1].matchAll(/<link rel="stylesheet"[^>]*>/g)

  for (const match of scriptMatches) {
    tags.push(match[0])
  }

  for (const match of linkMatches) {
    tags.push(match[0])
  }

  return tags
}

export function injectHeadIntoHtml(
  template: string,
  meta: PageMeta,
  baseHeadLines: string[],
  assetTags: string[] = [],
): string {
  const mergedHead = [...baseHeadLines, renderHeadInnerHtml(meta), ...assetTags].join('\n    ')
  return template
    .replace(/<html lang="[^"]*">/, `<html lang="${meta.htmlLang}">`)
    .replace(/<head>[\s\S]*?<\/head>/i, `<head>\n    ${mergedHead}\n  </head>`)
}

export function injectBodyIntoHtml(template: string, bodyHtml: string, prerenderScript: string): string {
  const appContent = [bodyHtml, prerenderScript].filter(Boolean).join('\n    ')
  if (!appContent) {
    return template
  }

  return template.replace(
    /<div id="app"><\/div>/,
    `<div id="app">\n    ${appContent}\n  </div>`,
  )
}

export function getBaseHeadLines(): string[] {
  return [
    '<meta charset="UTF-8">',
    '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
    '<meta name="theme-color" content="#ffffff">',
    `<link rel="icon" type="image/svg+xml" href="${LOGO_MARK_SVG}">`,
    `<link rel="icon" type="image/png" href="${LOGO_MARK_PNG}">`,
    `<link rel="apple-touch-icon" href="${LOGO_MARK_PNG}">`,
  ]
}
