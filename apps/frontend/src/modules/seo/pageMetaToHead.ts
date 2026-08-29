import type { PageMeta } from './types'

export function pageMetaToHead(meta: PageMeta | null | undefined) {
  if (!meta) {
    return {}
  }

  return {
    htmlAttrs: {
      lang: meta.htmlLang,
    },
    title: meta.title,
    meta: [
      { name: 'description', content: meta.description },
      ...(meta.keywords?.trim()
        ? [{ name: 'keywords', content: meta.keywords.trim() }]
        : []),
      { name: 'robots', content: meta.robots },
      { property: 'og:title', content: meta.ogTitle },
      { property: 'og:description', content: meta.ogDescription },
      { property: 'og:url', content: meta.ogUrl },
      { property: 'og:type', content: meta.ogType },
      { property: 'og:image', content: meta.ogImage },
      { property: 'og:locale', content: meta.ogLocale },
      { property: 'og:site_name', content: meta.ogSiteName },
      { name: 'twitter:card', content: meta.twitterCard },
      { name: 'twitter:title', content: meta.twitterTitle },
      { name: 'twitter:description', content: meta.twitterDescription },
      { name: 'twitter:image', content: meta.twitterImage },
    ],
    link: [
      { rel: 'canonical' as const, href: meta.canonical },
      ...meta.hreflang.map((item) => ({
        rel: 'alternate' as const,
        hreflang: item.locale,
        href: item.href,
      })),
    ],
    script: meta.jsonLd.map((schema) => ({
      type: 'application/ld+json' as const,
      innerHTML: JSON.stringify(schema),
    })),
  }
}
