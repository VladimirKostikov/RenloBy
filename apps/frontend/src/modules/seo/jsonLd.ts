import { roomsSeoLabel } from '@/lib/listingRooms'
import { SITE_NAME, buildAbsoluteUrl, getDefaultOgImage } from './siteConfig'
import type { ListingSeoContext, LocationSeoContext, SeoLocale } from './types'

export function buildOrganizationJsonLd(siteUrl: string, locale: SeoLocale) {
  const description = locale === 'en'
    ? 'Apartment buy, sell and rent aggregator in Belarus.'
    : 'Агрегатор покупки, продажи и аренды квартир в Беларуси.'

  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: SITE_NAME,
    url: siteUrl,
    logo: getDefaultOgImage(siteUrl),
    description,
  }
}

export function buildWebsiteJsonLd(siteUrl: string) {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: SITE_NAME,
    url: siteUrl,
    potentialAction: {
      '@type': 'SearchAction',
      target: `${siteUrl}/search?q={search_term_string}`,
      'query-input': 'required name=search_term_string',
    },
  }
}

export function buildBreadcrumbJsonLd(items: Array<{ name: string; path: string }>, siteUrl: string) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: buildAbsoluteUrl(item.path, siteUrl),
    })),
  }
}

export function buildRealEstateListingJsonLd(
  context: ListingSeoContext,
  siteUrl: string,
  path: string,
) {
  const { listing, cityName, districtName } = context
  const image = listing.images[0] ? buildAbsoluteUrl(listing.images[0], siteUrl) : getDefaultOgImage(siteUrl)

  return {
    '@context': 'https://schema.org',
    '@type': 'RealEstateListing',
    name: `${roomsSeoLabel(listing.rooms, 'en')} ${listing.listingType}`,
    description: listing.address,
    url: buildAbsoluteUrl(path, siteUrl),
    image,
    address: {
      '@type': 'PostalAddress',
      streetAddress: listing.address,
      addressLocality: cityName,
      addressRegion: districtName,
      addressCountry: 'BY',
    },
    offers: {
      '@type': 'Offer',
      price: listing.price,
      priceCurrency: 'USD',
    },
  }
}

export function buildLocationBreadcrumb(
  location: LocationSeoContext,
  locale: SeoLocale,
): Array<{ name: string; path: string }> {
  const homeLabel = locale === 'en' ? 'Home' : 'Главная'
  const items = [{ name: homeLabel, path: '/' }]

  if (location.regionSlug && location.regionName && !location.citySlug) {
    items.push({
      name: location.regionName,
      path: `/region/${location.regionSlug}`,
    })
    return items
  }

  if (location.regionSlug && location.regionName) {
    items.push({
      name: location.regionName,
      path: `/region/${location.regionSlug}`,
    })
  }

  if (location.cityName && location.citySlug) {
    items.push({
      name: location.cityName,
      path: `/city/${location.citySlug}`,
    })
  }

  if (location.districtName && location.districtSlug && location.citySlug) {
    items.push({
      name: location.districtName,
      path: `/city/${location.citySlug}/${location.districtSlug}`,
    })
  }

  return items
}
