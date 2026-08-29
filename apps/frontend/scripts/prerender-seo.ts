import { mkdir, readFile, writeFile } from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { FILTER_REGION_SLUGS } from '../src/lib/filterRegions.ts'
import { buildPageMeta, normalizePath, resolvePageKind } from '../src/modules/seo/buildPageMeta.ts'
import { buildPrerenderBodyHtml, buildPrerenderPayload, buildPrerenderScript } from '../src/modules/seo/buildPrerenderBody.ts'
import { extractViteAssetTags, getBaseHeadLines, injectBodyIntoHtml, injectHeadIntoHtml } from '../src/modules/seo/renderHeadHtml.ts'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const distDir = path.resolve(__dirname, '../dist')
const siteUrl = (process.env.VITE_SITE_URL ?? 'https://renlo.by').replace(/\/+$/, '')
const apiBaseUrl = (process.env.VITE_API_BASE_URL ?? process.env.PRERENDER_API_URL ?? 'http://localhost:8080').replace(/\/+$/, '')

interface CityDto {
  id: number
  name: string
  slug: string
  regionSlug: string
}

interface DistrictDto {
  id: number
  name: string
  slug: string
  cityId: number
}

interface ListingDto {
  id: number
  dealType: 'sale' | 'rent'
  listingType: 'apartment' | 'house' | 'room' | 'commercial'
  price: number
  rooms: number
  area: number
  address: string
  images: string[]
  cityId: number
  districtId: number
}

interface InfoPageDto {
  slug: string
  title: string
  body: string
}

interface ArticleDto {
  slug: string
  title: string
  excerpt: string
  body: string
  publishedAt: string
  metaTitle?: string | null
  metaDescription?: string | null
  coverImage?: string | null
}

async function fetchJson<T>(url: string): Promise<T> {
  const response = await fetch(url)
  if (!response.ok) {
    throw new Error(`Request failed: ${response.status} ${url}`)
  }

  return response.json() as Promise<T>
}

async function fetchAllListings(): Promise<ListingDto[]> {
  const collected: ListingDto[] = []
  let page = 1
  const limit = 100

  while (true) {
    const payload = await fetchJson<{ items: ListingDto[]; total: number }>(
      `${apiBaseUrl}/api/listings?page=${page}&limit=${limit}`,
    )
    collected.push(...payload.items)

    if (collected.length >= payload.total || payload.items.length === 0) {
      break
    }

    page += 1
  }

  return collected
}

function listingPaths(listingId: number): string[] {
  return [
    `/listings/${listingId}`,
    `/rent/listings/${listingId}`,
    `/sale/listings/${listingId}`,
    `/commercial/listings/${listingId}`,
    `/search/listings/${listingId}`,
  ]
}

function buildPaths(
  cities: CityDto[],
  districts: DistrictDto[],
  infoPages: InfoPageDto[],
  articles: ArticleDto[],
  listings: ListingDto[],
): string[] {
  const paths = new Set<string>([
    '/',
    '/rent',
    '/sale',
    '/commercial',
    '/search',
    '/articles',
    '/login',
    '/promotion/payment',
  ])

  for (const page of infoPages) {
    paths.add(`/info/${page.slug}`)
  }

  for (const article of articles) {
    paths.add(`/articles/${article.slug}`)
  }

  for (const city of cities) {
    paths.add(`/city/${city.slug}`)
    if (city.regionSlug) {
      paths.add(`/region/${city.regionSlug}`)
    }
  }

  for (const regionSlug of FILTER_REGION_SLUGS) {
    paths.add(`/region/${regionSlug}`)
  }

  for (const district of districts) {
    const city = cities.find((item) => item.id === district.cityId)
    if (city) {
      paths.add(`/city/${city.slug}/${district.slug}`)
    }
  }

  for (const listing of listings) {
    for (const listingPath of listingPaths(listing.id)) {
      paths.add(listingPath)
    }

    const city = cities.find((item) => item.id === listing.cityId)
    const district = districts.find((item) => item.id === listing.districtId)
    if (city && district) {
      paths.add(`/city/${city.slug}/listings/${listing.id}`)
      paths.add(`/city/${city.slug}/${district.slug}/listings/${listing.id}`)
    }
    if (city?.regionSlug) {
      paths.add(`/region/${city.regionSlug}/listings/${listing.id}`)
    }
  }

  return [...paths]
}

function buildContextForPath(
  pathValue: string,
  cities: CityDto[],
  districts: DistrictDto[],
  infoPages: InfoPageDto[],
  articles: ArticleDto[],
  listings: ListingDto[],
) {
  const kind = resolvePageKind(pathValue)

  if (kind === 'listing') {
    const match = pathValue.match(/\/listings\/(\d+)$/)
    const listingId = Number(match?.[1])
    const listing = listings.find((item) => item.id === listingId)
    if (!listing) {
      return {}
    }

    const city = cities.find((item) => item.id === listing.cityId)
    const district = districts.find((item) => item.id === listing.districtId)
    if (!city || !district) {
      return {}
    }

    return {
      listing: {
        listing,
        cityName: city.name,
        districtName: district.name,
      },
    }
  }

  if (kind === 'info-page') {
    const slug = pathValue.replace('/info/', '')
    const page = infoPages.find((item) => item.slug === slug)
    if (!page) {
      return {}
    }

    return { infoPage: page }
  }

  if (kind === 'article') {
    const slug = pathValue.replace('/articles/', '')
    const article = articles.find((item) => item.slug === slug)
    if (!article) {
      return {}
    }

    return { article }
  }

  if (kind === 'region-location') {
    const regionSlug = pathValue.replace('/region/', '')
    const regionNames: Record<string, string> = {
      brest: 'Брестская область',
      vitebsk: 'Витебская область',
      gomel: 'Гомельская область',
      grodno: 'Гродненская область',
      mogilev: 'Могилёвская область',
      'minsk-region': 'Минская область',
      'minsk-city': 'г. Минск',
    }
    const regionName = regionNames[regionSlug]
    if (!regionName) {
      return { noindex: true }
    }

    return {
      location: {
        regionName,
        regionSlug,
      },
    }
  }

  if (kind === 'city-location' || kind === 'district-location') {
    const segments = pathValue.split('/').filter(Boolean)
    const citySlug = segments[1]
    const city = cities.find((item) => item.slug === citySlug)
    if (!city) {
      return { noindex: true }
    }

    if (kind === 'district-location') {
      const districtSlug = segments[2]
      const district = districts.find((item) => item.slug === districtSlug && item.cityId === city.id)
      if (!district) {
        return { noindex: true }
      }

      return {
        location: {
          cityName: city.name,
          citySlug: city.slug,
          districtName: district.name,
          districtSlug: district.slug,
        },
      }
    }

    return {
      location: {
        cityName: city.name,
        citySlug: city.slug,
      },
    }
  }

  if (kind === 'login' || kind === 'promotion' || kind === 'admin') {
    return { noindex: true }
  }

  return {}
}

async function writeHtmlForPath(
  template: string,
  assetTags: string[],
  pathValue: string,
  context: Record<string, unknown>,
) {
  const normalized = normalizePath(pathValue)
  const meta = buildPageMeta({
    locale: 'ru',
    path: normalized,
    siteUrl,
    ...context,
  })

  const payload = buildPrerenderPayload(normalized, context)
  const html = injectBodyIntoHtml(
    injectHeadIntoHtml(template, meta, getBaseHeadLines(), assetTags),
    buildPrerenderBodyHtml(payload),
    buildPrerenderScript(payload),
  )
  const relative = normalized === '/' ? 'index.html' : `${normalized.slice(1)}/index.html`
  const target = path.join(distDir, relative)
  await mkdir(path.dirname(target), { recursive: true })
  await writeFile(target, html, 'utf8')
}

async function main() {
  const template = await readFile(path.join(distDir, 'index.html'), 'utf8')
  const assetTags = extractViteAssetTags(template)

  let cities: CityDto[] = []
  let districts: DistrictDto[] = []
  let infoPages: InfoPageDto[] = []
  let articles: ArticleDto[] = []
  let listings: ListingDto[] = []

  try {
    ;[cities, districts, infoPages, articles, listings] = await Promise.all([
      fetchJson<CityDto[]>(`${apiBaseUrl}/api/cities`),
      fetchJson<DistrictDto[]>(`${apiBaseUrl}/api/districts`),
      fetchJson<InfoPageDto[]>(`${apiBaseUrl}/api/info-pages`),
      fetchJson<ArticleDto[]>(`${apiBaseUrl}/api/articles`),
      fetchAllListings(),
    ])
  } catch (error) {
    console.warn('[prerender-seo] API unavailable, prerendering static routes only.', error)
    infoPages = [
      { slug: 'buyers', title: 'Покупателям', body: '' },
      { slug: 'sellers', title: 'Продавцам', body: '' },
      { slug: 'renters', title: 'Арендаторам', body: '' },
      { slug: 'deal-safety', title: 'Руководство по безопасной сделке с недвижимостью', body: '' },
      { slug: 'faq', title: 'FAQ', body: '' },
      { slug: 'support', title: 'Поддержка', body: '' },
    ]
    articles = []
  }

  const paths = buildPaths(cities, districts, infoPages, articles, listings)

  for (const pathValue of paths) {
    const context = buildContextForPath(pathValue, cities, districts, infoPages, articles, listings)
    await writeHtmlForPath(template, assetTags, pathValue, context)
  }

  console.log(`[prerender-seo] Generated ${paths.length} HTML pages`)
}

void main()
