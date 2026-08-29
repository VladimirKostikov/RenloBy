import { roomsSeoLabel } from '@/lib/listingRooms'
import {
  buildBreadcrumbJsonLd,
  buildLocationBreadcrumb,
  buildOrganizationJsonLd,
  buildRealEstateListingJsonLd,
  buildWebsiteJsonLd,
} from './jsonLd'
import { fillSeoTemplate, getSeoMessages } from './seoMessages'
import {
  SITE_NAME,
  buildAbsoluteUrl,
  buildHreflang,
  getDefaultOgImage,
  localeToHtmlLang,
  localeToOgLocale,
} from './siteConfig'
import { truncateText } from './truncateText'
import type { PageMeta, SeoBuildInput } from './types'

function normalizePath(path: string): string {
  if (!path || path === '/') {
    return '/'
  }

  const withoutQuery = path.split('?')[0]?.split('#')[0] ?? path
  return withoutQuery.endsWith('/') ? withoutQuery.slice(0, -1) : withoutQuery
}

function listingTypeLabel(type: string, locale: 'ru' | 'en'): string {
  const labels: Record<string, Record<'ru' | 'en', string>> = {
    apartment: { ru: 'квартира', en: 'apartment' },
    house: { ru: 'дом', en: 'house' },
    room: { ru: 'комната', en: 'room' },
    commercial: { ru: 'бизнес', en: 'commercial' },
  }

  return labels[type]?.[locale] ?? type
}

function dealTypeLabel(dealType: string, locale: 'ru' | 'en'): string {
  const labels: Record<string, Record<'ru' | 'en', string>> = {
    sale: { ru: 'Продажа', en: 'Sale' },
    rent: { ru: 'Аренда', en: 'Rent' },
  }

  return labels[dealType]?.[locale] ?? dealType
}

function buildBaseMeta(
  input: SeoBuildInput,
  title: string,
  description: string,
  options: {
    path: string
    ogType: 'website' | 'product'
    robots: PageMeta['robots']
    jsonLd: Record<string, unknown>[]
    keywords?: string | null
  },
): PageMeta {
  const canonical = buildAbsoluteUrl(options.path, input.siteUrl)
  const ogImage = getDefaultOgImage(input.siteUrl)
  const safeTitle = truncateText(title, 60)
  const safeDescription = truncateText(description, 160)
  const keywords = options.keywords?.trim() || null

  return {
    title: safeTitle,
    description: safeDescription,
    keywords,
    canonical,
    robots: options.robots,
    ogType: options.ogType,
    ogTitle: safeTitle,
    ogDescription: safeDescription,
    ogUrl: canonical,
    ogImage,
    ogLocale: localeToOgLocale(input.locale),
    ogSiteName: SITE_NAME,
    twitterCard: 'summary_large_image',
    twitterTitle: safeTitle,
    twitterDescription: safeDescription,
    twitterImage: ogImage,
    hreflang: buildHreflang(options.path, input.siteUrl),
    jsonLd: options.jsonLd,
    htmlLang: localeToHtmlLang(input.locale),
  }
}

function buildListingMeta(input: SeoBuildInput): PageMeta {
  if (!input.listing) {
    throw new Error('Listing context is required')
  }

  const messages = getSeoMessages(input.locale)
  const { listing, cityName, districtName } = input.listing
  const canonicalPath = `/listings/${listing.id}`
  const titleTemplate = listing.dealType === 'rent' ? messages.listing.titleRent : messages.listing.titleSale

  const generatedTitle = fillSeoTemplate(titleTemplate, {
    roomsLabel: roomsSeoLabel(listing.rooms, input.locale),
    area: listing.area,
    district: districtName,
    price: listing.price,
  })

  const generatedDescription = fillSeoTemplate(messages.listing.description, {
    dealType: dealTypeLabel(listing.dealType, input.locale),
    roomsLabel: roomsSeoLabel(listing.rooms, input.locale),
    listingType: listingTypeLabel(listing.listingType, input.locale),
    area: listing.area,
    address: listing.address,
    district: districtName,
    city: cityName,
    price: listing.dealType === 'rent' ? `${listing.price} $/мес` : `${listing.price} $`,
  })

  const title = listing.metaTitle?.trim() || generatedTitle
  const description = listing.metaDescription?.trim() || generatedDescription

  const ogImage = listing.images[0]
    ? buildAbsoluteUrl(listing.images[0], input.siteUrl)
    : getDefaultOgImage(input.siteUrl)

  const meta = buildBaseMeta(input, title, description, {
    path: canonicalPath,
    ogType: 'product',
    robots: 'index,follow',
    keywords: listing.metaKeywords?.trim() || null,
    jsonLd: [
      buildOrganizationJsonLd(input.siteUrl, input.locale),
      buildRealEstateListingJsonLd(input.listing, input.siteUrl, canonicalPath),
    ],
  })

  return { ...meta, ogImage, twitterImage: ogImage }
}

function buildLocationMeta(input: SeoBuildInput): PageMeta {
  if (!input.location) {
    throw new Error('Location context is required')
  }

  const messages = getSeoMessages(input.locale)
  const { location } = input
  const isRegion = Boolean(location.regionSlug && location.regionName && !location.citySlug)
  const isDistrict = Boolean(location.districtName && location.districtSlug && location.citySlug)

  const path = isRegion
    ? `/region/${location.regionSlug}`
    : isDistrict
      ? `/city/${location.citySlug}/${location.districtSlug}`
      : `/city/${location.citySlug}`

  const title = isRegion
    ? fillSeoTemplate(messages.location.regionTitle, { region: location.regionName ?? '' })
    : isDistrict
      ? fillSeoTemplate(messages.location.districtTitle, {
          district: location.districtName ?? '',
          city: location.cityName ?? '',
        })
      : fillSeoTemplate(messages.location.cityTitle, { city: location.cityName ?? '' })

  const description = isRegion
    ? fillSeoTemplate(messages.location.regionDescription, { region: location.regionName ?? '' })
    : isDistrict
      ? fillSeoTemplate(messages.location.districtDescription, {
          district: location.districtName ?? '',
          city: location.cityName ?? '',
        })
      : fillSeoTemplate(messages.location.cityDescription, { city: location.cityName ?? '' })

  return buildBaseMeta(input, title, description, {
    path,
    ogType: 'website',
    robots: 'index,follow',
    jsonLd: [
      buildOrganizationJsonLd(input.siteUrl, input.locale),
      buildBreadcrumbJsonLd(buildLocationBreadcrumb(location, input.locale), input.siteUrl),
    ],
  })
}

function buildInfoPageFallbackMeta(input: SeoBuildInput, path: string): PageMeta {
  const messages = getSeoMessages(input.locale)
  const infoLabel = input.locale === 'en' ? 'Information' : 'Информация'
  const title = `${infoLabel} - ${SITE_NAME}`
  const description = fillSeoTemplate(messages.info.description, { title: infoLabel })

  return buildBaseMeta(input, title, description, {
    path,
    ogType: 'website',
    robots: 'index,follow',
    jsonLd: [buildOrganizationJsonLd(input.siteUrl, input.locale)],
  })
}

function buildInfoMeta(input: SeoBuildInput): PageMeta {
  if (!input.infoPage) {
    throw new Error('Info page context is required')
  }

  const messages = getSeoMessages(input.locale)
  const path = `/info/${input.infoPage.slug}`
  const title = input.infoPage.metaTitle?.trim()
    || `${input.infoPage.title} - ${SITE_NAME}`
  const description = input.infoPage.metaDescription?.trim()
    || fillSeoTemplate(messages.info.description, { title: input.infoPage.title })
  const homeLabel = input.locale === 'en' ? 'Home' : 'Главная'
  const infoLabel = input.locale === 'en' ? 'Information' : 'Информация'

  return buildBaseMeta(input, title, description, {
    path,
    ogType: 'website',
    robots: 'index,follow',
    jsonLd: [
      buildOrganizationJsonLd(input.siteUrl, input.locale),
      buildBreadcrumbJsonLd(
        [
          { name: homeLabel, path: '/' },
          { name: infoLabel, path: '/info/deal-safety' },
          { name: input.infoPage.title, path },
        ],
        input.siteUrl,
      ),
    ],
  })
}

function buildArticlesListMeta(input: SeoBuildInput): PageMeta {
  const messages = getSeoMessages(input.locale)
  const homeLabel = input.locale === 'en' ? 'Home' : 'Главная'

  return buildBaseMeta(input, messages.articles.title, messages.articles.description, {
    path: '/articles',
    ogType: 'website',
    robots: 'index,follow',
    jsonLd: [
      buildOrganizationJsonLd(input.siteUrl, input.locale),
      buildBreadcrumbJsonLd(
        [
          { name: homeLabel, path: '/' },
          { name: messages.articles.h1, path: '/articles' },
        ],
        input.siteUrl,
      ),
    ],
  })
}

function buildArticleFallbackMeta(input: SeoBuildInput, path: string): PageMeta {
  const messages = getSeoMessages(input.locale)

  return buildBaseMeta(input, messages.articles.title, messages.articles.description, {
    path,
    ogType: 'website',
    robots: 'index,follow',
    jsonLd: [buildOrganizationJsonLd(input.siteUrl, input.locale)],
  })
}

function buildArticleMeta(input: SeoBuildInput): PageMeta {
  if (!input.article) {
    throw new Error('Article context is required')
  }

  const messages = getSeoMessages(input.locale)
  const path = `/articles/${input.article.slug}`
  const title = input.article.metaTitle?.trim()
    || `${input.article.title} - ${SITE_NAME}`
  const description = input.article.metaDescription?.trim()
    || input.article.excerpt.trim()
    || fillSeoTemplate(messages.articles.itemDescription, { title: input.article.title })
  const homeLabel = input.locale === 'en' ? 'Home' : 'Главная'
  const ogImage = input.article.coverImage
    ? buildAbsoluteUrl(input.article.coverImage, input.siteUrl)
    : getDefaultOgImage(input.siteUrl)

  const meta = buildBaseMeta(input, title, description, {
    path,
    ogType: 'website',
    robots: 'index,follow',
    jsonLd: [
      buildOrganizationJsonLd(input.siteUrl, input.locale),
      buildBreadcrumbJsonLd(
        [
          { name: homeLabel, path: '/' },
          { name: messages.articles.h1, path: '/articles' },
          { name: input.article.title, path },
        ],
        input.siteUrl,
      ),
      {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: input.article.title,
        description,
        datePublished: input.article.publishedAt,
        image: ogImage,
        mainEntityOfPage: buildAbsoluteUrl(path, input.siteUrl),
      },
    ],
  })

  return {
    ...meta,
    ogImage,
    twitterImage: ogImage,
  }
}

export function resolvePageKind(path: string): string {
  const normalized = normalizePath(path)

  if (normalized === '/') return 'home'
  if (normalized === '/rent') return 'rent-catalog'
  if (normalized === '/sale') return 'sale-catalog'
  if (normalized === '/commercial') return 'commercial-catalog'
  if (normalized === '/search') return 'search-map'
  if (normalized === '/login') return 'login'
  if (normalized === '/favorites') return 'favorites'
  if (normalized === '/compare') return 'compare'
  if (normalized === '/account/user/favorites') return 'favorites'
  if (normalized === '/account/user/compare') return 'compare'
  if (normalized === '/account/seller/promotion') return 'promotion'
  if (normalized === '/promotion/payment') return 'promotion'
  if (normalized.startsWith('/admin')) return 'admin'
  if (normalized === '/articles') return 'articles'
  if (/^\/articles\/[^/]+$/.test(normalized)) return 'article'
  if (/^\/listings\/\d+$/.test(normalized)) return 'listing'
  if (/^\/(rent|sale|commercial|search)\/listings\/\d+$/.test(normalized)) return 'listing'
  if (/^\/region\/[^/]+\/listings\/\d+$/.test(normalized)) return 'listing'
  if (/^\/region\/[^/]+$/.test(normalized)) return 'region-location'
  if (/^\/city\/[^/]+\/[^/]+\/listings\/\d+$/.test(normalized)) return 'listing'
  if (/^\/city\/[^/]+\/listings\/\d+$/.test(normalized)) return 'listing'
  if (/^\/city\/[^/]+\/[^/]+$/.test(normalized)) return 'district-location'
  if (/^\/city\/[^/]+$/.test(normalized)) return 'city-location'
  if (/^\/info\/[^/]+$/.test(normalized)) return 'info-page'

  return 'home'
}

export function buildPageMeta(input: SeoBuildInput): PageMeta {
  const path = normalizePath(input.path)
  const kind = resolvePageKind(path)
  const messages = getSeoMessages(input.locale)

  if (input.noindex || kind === 'admin' || kind === 'login' || kind === 'promotion' || kind === 'favorites' || kind === 'compare') {
    const titleKey = kind === 'login'
      ? messages.login
      : kind === 'promotion'
        ? messages.promotion
        : kind === 'favorites'
          ? messages.favorites
          : kind === 'compare'
            ? messages.compare
            : kind === 'admin'
              ? messages.admin
              : messages.home

    return buildBaseMeta(input, titleKey.title, titleKey.description, {
      path,
      ogType: 'website',
      robots: 'noindex,nofollow',
      jsonLd: [buildOrganizationJsonLd(input.siteUrl, input.locale)],
    })
  }

  if (input.listing) {
    return buildListingMeta({ ...input, path })
  }

  if (kind === 'listing') {
    return buildBaseMeta(input, messages.home.title, messages.home.description, {
      path,
      ogType: 'website',
      robots: 'noindex,nofollow',
      jsonLd: [buildOrganizationJsonLd(input.siteUrl, input.locale)],
    })
  }

  if (input.location) {
    return buildLocationMeta({ ...input, path })
  }

  if (input.infoPage) {
    return buildInfoMeta({ ...input, path })
  }

  if (kind === 'info-page') {
    return buildInfoPageFallbackMeta(input, path)
  }

  if (input.article) {
    return buildArticleMeta({ ...input, path })
  }

  if (kind === 'article') {
    return buildArticleFallbackMeta(input, path)
  }

  if (kind === 'articles') {
    return buildArticlesListMeta(input)
  }

  if (kind === 'rent-catalog') {
    const override = input.seoOverrides?.rentCatalog
    return buildBaseMeta(
      input,
      override?.title || messages.rentCatalog.title,
      override?.description || messages.rentCatalog.description,
      {
        path: '/rent',
        ogType: 'website',
        robots: 'index,follow',
        keywords: override?.keywords || messages.rentCatalog.keywords,
        jsonLd: [buildOrganizationJsonLd(input.siteUrl, input.locale)],
      },
    )
  }

  if (kind === 'sale-catalog') {
    const override = input.seoOverrides?.saleCatalog
    return buildBaseMeta(
      input,
      override?.title || messages.saleCatalog.title,
      override?.description || messages.saleCatalog.description,
      {
        path: '/sale',
        ogType: 'website',
        robots: 'index,follow',
        keywords: override?.keywords || messages.saleCatalog.keywords,
        jsonLd: [buildOrganizationJsonLd(input.siteUrl, input.locale)],
      },
    )
  }

  if (kind === 'commercial-catalog') {
    const override = input.seoOverrides?.commercialCatalog
    return buildBaseMeta(
      input,
      override?.title || messages.commercialCatalog.title,
      override?.description || messages.commercialCatalog.description,
      {
        path: '/commercial',
        ogType: 'website',
        robots: 'index,follow',
        keywords: override?.keywords || messages.commercialCatalog.keywords,
        jsonLd: [buildOrganizationJsonLd(input.siteUrl, input.locale)],
      },
    )
  }

  if (kind === 'search-map') {
    const override = input.seoOverrides?.searchMap
    return buildBaseMeta(
      input,
      override?.title || messages.searchMap.title,
      override?.description || messages.searchMap.description,
      {
        path: '/search',
        ogType: 'website',
        robots: 'index,follow',
        keywords: override?.keywords || messages.searchMap.keywords,
        jsonLd: [
          buildOrganizationJsonLd(input.siteUrl, input.locale),
          buildWebsiteJsonLd(input.siteUrl),
        ],
      },
    )
  }

  const homeOverride = input.seoOverrides?.home
  return buildBaseMeta(
    input,
    homeOverride?.title || messages.home.title,
    homeOverride?.description || messages.home.description,
    {
      path: '/',
      ogType: 'website',
      robots: 'index,follow',
      keywords: homeOverride?.keywords || messages.home.keywords,
      jsonLd: [
        buildOrganizationJsonLd(input.siteUrl, input.locale),
        buildWebsiteJsonLd(input.siteUrl),
      ],
    },
  )
}

export function getPageH1(
  path: string,
  locale: 'ru' | 'en',
  seoOverrides?: SeoBuildInput['seoOverrides'],
): string {
  const kind = resolvePageKind(path)
  const messages = getSeoMessages(locale)

  if (kind === 'rent-catalog') return seoOverrides?.rentCatalog?.h1 || messages.rentCatalog.h1
  if (kind === 'sale-catalog') return seoOverrides?.saleCatalog?.h1 || messages.saleCatalog.h1
  if (kind === 'commercial-catalog') {
    return seoOverrides?.commercialCatalog?.h1 || messages.commercialCatalog.h1
  }
  if (kind === 'search-map') return seoOverrides?.searchMap?.h1 || messages.searchMap.h1
  if (kind === 'articles') return messages.articles.h1
  if (kind === 'home') return seoOverrides?.home?.h1 || messages.home.h1

  return seoOverrides?.home?.h1 || messages.home.h1
}

export { normalizePath }
