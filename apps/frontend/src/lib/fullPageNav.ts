import type { DealType } from '@/types'

export type PublicCatalogKind = DealType | 'commercial'

export function catalogPath(kind: PublicCatalogKind): string {
  if (kind === 'sale') {
    return '/sale'
  }
  if (kind === 'rent') {
    return '/rent'
  }
  return '/commercial'
}

export function listingPath(id: number | string): string {
  return `/listings/${id}`
}

export function listingDetailPath(
  id: number | string,
  options: {
    detailRouteName?: string
    citySlug?: string
    districtSlug?: string
    regionSlug?: string
  } = {},
): string {
  const listingId = String(id)

  if (options.detailRouteName === 'search-listing-detail') {
    return `/search/listings/${listingId}`
  }

  if (options.detailRouteName === 'sale-listing-detail') {
    return `/sale/listings/${listingId}`
  }

  if (options.detailRouteName === 'rent-listing-detail') {
    return `/rent/listings/${listingId}`
  }

  if (options.detailRouteName === 'commercial-listing-detail') {
    return `/commercial/listings/${listingId}`
  }

  if (options.detailRouteName === 'region-listing-detail' && options.regionSlug) {
    return `/region/${options.regionSlug}/listings/${listingId}`
  }

  if (options.detailRouteName === 'city-listing-detail' && options.citySlug) {
    return `/city/${options.citySlug}/listings/${listingId}`
  }

  if (
    options.detailRouteName === 'district-listing-detail'
    && options.citySlug
    && options.districtSlug
  ) {
    return `/city/${options.citySlug}/${options.districtSlug}/listings/${listingId}`
  }

  return listingPath(listingId)
}

export function infoPagePath(slug: string): string {
  return `/info/${slug}`
}

export function articlesPath(): string {
  return '/articles'
}

export function articlePath(slug: string): string {
  return `/articles/${slug}`
}

export function cityPath(citySlug: string): string {
  return `/city/${citySlug}`
}

export function districtPath(citySlug: string, districtSlug: string): string {
  return `/city/${citySlug}/${districtSlug}`
}

export function regionPath(regionSlug: string): string {
  return `/region/${regionSlug}`
}

export function navigateTo(href: string): void {
  window.location.assign(href)
}

export function catalogPathFromRouteName(routeName: string): string {
  switch (routeName) {
    case 'sale-catalog':
    case 'sale-listing-detail':
      return catalogPath('sale')
    case 'rent-catalog':
    case 'rent-listing-detail':
      return catalogPath('rent')
    case 'commercial-catalog':
    case 'commercial-listing-detail':
      return catalogPath('commercial')
    case 'search-map':
      return '/search'
    default:
      return '/'
  }
}
