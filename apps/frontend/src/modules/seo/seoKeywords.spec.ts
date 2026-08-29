import { describe, expect, it } from 'vitest'
import { renderHeadInnerHtml } from '@/modules/seo/renderHeadHtml'
import { pageMetaToHead } from '@/modules/seo/pageMetaToHead'
import type { PageMeta } from '@/modules/seo/types'
import { seoMetaListToMap } from '@/modules/seo/seoOverrides'
import { buildPageMeta } from '@/modules/seo/buildPageMeta'

function sampleMeta(overrides: Partial<PageMeta> = {}): PageMeta {
  return {
    title: 'Renlo',
    description: 'Desc',
    keywords: 'недвижимость, квартиры',
    canonical: 'https://renlo.by/',
    robots: 'index,follow',
    ogType: 'website',
    ogTitle: 'Renlo',
    ogDescription: 'Desc',
    ogUrl: 'https://renlo.by/',
    ogImage: 'https://renlo.by/og.png',
    ogLocale: 'ru_RU',
    ogSiteName: 'Renlo',
    twitterCard: 'summary_large_image',
    twitterTitle: 'Renlo',
    twitterDescription: 'Desc',
    twitterImage: 'https://renlo.by/og.png',
    hreflang: [],
    jsonLd: [],
    htmlLang: 'ru',
    ...overrides,
  }
}

describe('SEO keywords', () => {
  it('renders meta keywords in head html', () => {
    const html = renderHeadInnerHtml(sampleMeta())
    expect(html).toContain('<meta name="keywords" content="недвижимость, квартиры">')
  })

  it('omits keywords tag when empty', () => {
    const html = renderHeadInnerHtml(sampleMeta({ keywords: '  ' }))
    expect(html).not.toContain('name="keywords"')
  })

  it('includes keywords in client head payload', () => {
    const head = pageMetaToHead(sampleMeta())
    const keywords = (head.meta as { name?: string; content?: string }[]).find(
      (item) => item.name === 'keywords',
    )
    expect(keywords?.content).toBe('недвижимость, квартиры')
  })

  it('maps keywords from seo meta dto', () => {
    const map = seoMetaListToMap([
      {
        id: 1,
        pageKey: 'home',
        locale: 'ru',
        title: 'T',
        description: 'D',
        h1: 'H',
        keywords: 'a, b',
      },
    ])
    expect(map.home.keywords).toBe('a, b')
  })

  it('applies home keywords from overrides and defaults', () => {
    const withOverride = buildPageMeta({
      locale: 'ru',
      path: '/',
      siteUrl: 'https://renlo.by',
      seoOverrides: {
        home: {
          title: 'Custom',
          description: 'Custom desc',
          h1: 'H',
          keywords: 'custom, keywords',
        },
      },
    })
    expect(withOverride.keywords).toBe('custom, keywords')

    const withDefault = buildPageMeta({
      locale: 'ru',
      path: '/',
      siteUrl: 'https://renlo.by',
    })
    expect(withDefault.keywords).toContain('недвижимость')
  })
})
