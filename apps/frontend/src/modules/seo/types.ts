export type SeoLocale = 'ru' | 'en'

export type SeoRobotsDirective = 'index,follow' | 'noindex,nofollow'

export interface PageMetaHreflang {
  locale: SeoLocale | 'x-default'
  href: string
}

export interface PageMeta {
  title: string
  description: string
  keywords?: string | null
  canonical: string
  robots: SeoRobotsDirective
  ogType: 'website' | 'product'
  ogTitle: string
  ogDescription: string
  ogUrl: string
  ogImage: string
  ogLocale: string
  ogSiteName: string
  twitterCard: 'summary_large_image'
  twitterTitle: string
  twitterDescription: string
  twitterImage: string
  hreflang: PageMetaHreflang[]
  jsonLd: Record<string, unknown>[]
  htmlLang: SeoLocale
}

export interface ListingSeoContext {
  listing: {
    id: number
    dealType: 'sale' | 'rent'
    listingType: 'apartment' | 'house' | 'room' | 'commercial'
    price: number
    rooms: number
    area: number
    address: string
    images: string[]
    metaTitle?: string | null
    metaDescription?: string | null
    metaKeywords?: string | null
  }
  cityName: string
  districtName: string
}

export interface InfoPageSeoContext {
  slug: string
  title: string
  body: string
  metaTitle?: string | null
  metaDescription?: string | null
}

export interface ArticleSeoContext {
  slug: string
  title: string
  excerpt: string
  body: string
  metaTitle?: string | null
  metaDescription?: string | null
  coverImage?: string | null
  publishedAt?: string
}

export interface LocationSeoContext {
  cityName?: string
  citySlug?: string
  districtName?: string
  districtSlug?: string
  regionName?: string
  regionSlug?: string
}

export interface SeoBuildInput {
  locale: SeoLocale
  path: string
  siteUrl: string
  listing?: ListingSeoContext
  infoPage?: InfoPageSeoContext
  article?: ArticleSeoContext
  location?: LocationSeoContext
  noindex?: boolean
  seoOverrides?: Record<string, { title: string; description: string; h1?: string | null; keywords?: string | null }>
}
