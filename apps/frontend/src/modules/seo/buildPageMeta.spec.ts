import { describe, expect, it } from 'vitest'
import { buildPageMeta, resolvePageKind } from '@/modules/seo/buildPageMeta'

const siteUrl = 'https://renlo.by'

describe('buildPageMeta', () => {
  it('builds home meta with website schema', () => {
    const meta = buildPageMeta({ locale: 'ru', path: '/', siteUrl })

    expect(meta.title).toContain('Renlo')
    expect(meta.robots).toBe('index,follow')
    expect(meta.canonical).toBe('https://renlo.by/')
    expect(meta.ogType).toBe('website')
    expect(meta.hreflang).toHaveLength(3)
    expect(meta.jsonLd.some((item) => item['@type'] === 'WebSite')).toBe(true)
  })

  it('marks login as noindex', () => {
    const meta = buildPageMeta({ locale: 'ru', path: '/login', siteUrl })

    expect(meta.robots).toBe('noindex,nofollow')
  })

  it('builds listing meta with canonical /listings/:id', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/rent/listings/12',
      siteUrl,
      listing: {
        listing: {
          id: 12,
          dealType: 'rent',
          listingType: 'apartment',
          price: 500,
          rooms: 2,
          area: 54,
          address: 'ул. Ленина, 10',
          images: ['/uploads/listing-12.jpg'],
        },
        cityName: 'Минск',
        districtName: 'Центральный',
      },
    })

    expect(meta.canonical).toBe('https://renlo.by/listings/12')
    expect(meta.ogType).toBe('product')
    expect(meta.title).toContain('2')
    expect(meta.jsonLd.some((item) => item['@type'] === 'RealEstateListing')).toBe(true)
  })

  it('builds listing meta with studio rooms label', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/listings/15',
      siteUrl,
      listing: {
        listing: {
          id: 15,
          dealType: 'sale',
          listingType: 'apartment',
          price: 90000,
          rooms: 0,
          area: 28,
          address: 'ул. Ленина, 1',
          images: [],
        },
        cityName: 'Минск',
        districtName: 'Центральный',
      },
    })

    expect(meta.title).toContain('Студия')
    expect(meta.description).toContain('Студия')
  })

  it('uses listing SEO overrides when provided', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/listings/20',
      siteUrl,
      listing: {
        listing: {
          id: 20,
          dealType: 'sale',
          listingType: 'apartment',
          price: 120000,
          rooms: 3,
          area: 80,
          address: 'ул. Ленина, 20',
          images: ['/uploads/a.jpg'],
          metaTitle: 'Свой title для объявления',
          metaDescription: 'Своё описание для поисковиков и соцсетей',
          metaKeywords: 'минск, квартира, продажа',
        },
        cityName: 'Минск',
        districtName: 'Центральный',
      },
    })

    expect(meta.title).toBe('Свой title для объявления')
    expect(meta.description).toBe('Своё описание для поисковиков и соцсетей')
    expect(meta.keywords).toBe('минск, квартира, продажа')
    expect(meta.ogTitle).toBe('Свой title для объявления')
  })

  it('builds sale catalog meta', () => {
    const meta = buildPageMeta({ locale: 'ru', path: '/sale', siteUrl })

    expect(meta.title).toContain('Продажа')
    expect(meta.canonical).toBe('https://renlo.by/sale')
    expect(meta.robots).toBe('index,follow')
  })

  it('builds commercial catalog meta', () => {
    const meta = buildPageMeta({ locale: 'ru', path: '/commercial', siteUrl })

    expect(meta.title).toContain('Коммерческая')
    expect(meta.canonical).toBe('https://renlo.by/commercial')
    expect(meta.robots).toBe('index,follow')
  })

  it('builds district location breadcrumbs', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/city/minsk/centralny',
      siteUrl,
      location: {
        cityName: 'Минск',
        citySlug: 'minsk',
        districtName: 'Центральный',
        districtSlug: 'centralny',
      },
    })

    expect(meta.title).toContain('Центральный')
    expect(meta.jsonLd.some((item) => item['@type'] === 'BreadcrumbList')).toBe(true)
  })

  it('builds region location meta', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/region/gomel',
      siteUrl,
      location: {
        regionName: 'Гомельская область',
        regionSlug: 'gomel',
      },
    })

    expect(meta.canonical).toBe('https://renlo.by/region/gomel')
    expect(meta.title).toContain('Гомельская область')
    expect(meta.robots).toBe('index,follow')
    expect(resolvePageKind('/region/gomel')).toBe('region-location')
  })

  it('builds fallback meta for info page before context loads', () => {
    const meta = buildPageMeta({ locale: 'ru', path: '/info/sellers', siteUrl })

    expect(meta.canonical).toBe('https://renlo.by/info/sellers')
    expect(meta.robots).toBe('index,follow')
    expect(meta.title).toContain('Renlo')
  })

  it('builds info page meta with context', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/info/sellers',
      siteUrl,
      infoPage: {
        slug: 'sellers',
        title: 'Продавцам',
        body: 'Текст страницы',
      },
    })

    expect(meta.title).toContain('Продавцам')
    expect(meta.canonical).toBe('https://renlo.by/info/sellers')
    expect(meta.jsonLd.some((item) => item['@type'] === 'BreadcrumbList')).toBe(true)
  })

  it('builds articles list meta', () => {
    const meta = buildPageMeta({ locale: 'ru', path: '/articles', siteUrl })

    expect(meta.canonical).toBe('https://renlo.by/articles')
    expect(meta.robots).toBe('index,follow')
    expect(meta.title).toContain('Статьи')
  })

  it('builds article meta with context and Article json-ld', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/articles/kak-vybrat-kvartiru-v-minske',
      siteUrl,
      article: {
        slug: 'kak-vybrat-kvartiru-v-minske',
        title: 'Как выбрать квартиру в Минске',
        excerpt: 'Гид по выбору',
        body: 'Текст',
        metaTitle: 'Как выбрать квартиру в Минске - Renlo',
        metaDescription: 'Гид по выбору квартиры в Минске',
        publishedAt: '2026-06-01',
      },
    })

    expect(meta.title).toBe('Как выбрать квартиру в Минске - Renlo')
    expect(meta.description).toContain('Гид по выбору квартиры')
    expect(meta.canonical).toBe('https://renlo.by/articles/kak-vybrat-kvartiru-v-minske')
    expect(meta.jsonLd.some((item) => item['@type'] === 'Article')).toBe(true)
  })
})

describe('resolvePageKind', () => {
  it('detects listing routes', () => {
    expect(resolvePageKind('/listings/5')).toBe('listing')
    expect(resolvePageKind('/rent/listings/5')).toBe('listing')
    expect(resolvePageKind('/commercial/listings/5')).toBe('listing')
    expect(resolvePageKind('/city/minsk/listings/5')).toBe('listing')
  })

  it('detects articles routes', () => {
    expect(resolvePageKind('/articles')).toBe('articles')
    expect(resolvePageKind('/articles/kak-vybrat-kvartiru-v-minske')).toBe('article')
  })

  it('detects commercial catalog route', () => {
    expect(resolvePageKind('/commercial')).toBe('commercial-catalog')
  })
})
