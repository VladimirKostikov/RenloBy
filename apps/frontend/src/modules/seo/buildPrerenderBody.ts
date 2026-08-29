import { parseInfoBody } from '@/modules/info/lib/parseInfoBody'
import { roomsSeoLabel } from '@/lib/listingRooms'

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

function renderInfoBodyHtml(body: string): string {
  return parseInfoBody(body)
    .map((block) => {
      if (block.type === 'heading') {
        return `<h2>${escapeHtml(block.text)}</h2>`
      }

      if (block.type === 'list') {
        const items = block.items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')
        return `<ul>${items}</ul>`
      }

      return `<p>${escapeHtml(block.text)}</p>`
    })
    .join('')
}

export interface PrerenderInfoPagePayload {
  kind: 'info-page'
  page: {
    slug: string
    title: string
    body: string
  }
}

export interface PrerenderArticlePayload {
  kind: 'article'
  article: {
    slug: string
    title: string
    excerpt: string
    body: string
    publishedAt: string
    coverImage?: string | null
    media?: Array<{ url: string; type: string }>
  }
}

export interface PrerenderListingPayload {
  kind: 'listing'
  listing: {
    id: number
    title: string
    address: string
    price: number
    rooms: number
    area: number
  }
}

export type PrerenderPayload = PrerenderInfoPagePayload | PrerenderArticlePayload | PrerenderListingPayload

export function buildPrerenderPayload(path: string, context: Record<string, unknown>): PrerenderPayload | null {
  if (context.infoPage && typeof context.infoPage === 'object') {
    const page = context.infoPage as { slug: string; title: string; body: string }
    return {
      kind: 'info-page',
      page,
    }
  }

  if (context.article && typeof context.article === 'object') {
    const article = context.article as {
      slug: string
      title: string
      excerpt: string
      body: string
      publishedAt: string
    }
    return {
      kind: 'article',
      article,
    }
  }

  if (context.listing && typeof context.listing === 'object') {
    const payload = context.listing as {
      listing: { id: number; address: string; price: number; rooms: number; area: number }
      cityName: string
      districtName: string
    }
    const { listing, cityName, districtName } = payload
    return {
      kind: 'listing',
      listing: {
        id: listing.id,
        title: `${roomsSeoLabel(listing.rooms, 'ru')}, ${listing.area} м², ${cityName}`,
        address: `${listing.address}, ${districtName}`,
        price: listing.price,
        rooms: listing.rooms,
        area: listing.area,
      },
    }
  }

  if (path === '/' || path === '/rent' || path === '/sale' || path === '/commercial' || path === '/search' || path === '/articles') {
    return null
  }

  return null
}

export function buildPrerenderBodyHtml(payload: PrerenderPayload | null): string {
  if (!payload) {
    return ''
  }

  if (payload.kind === 'info-page') {
    return `
<article class="prerender-page prerender-page--info">
  <h1>${escapeHtml(payload.page.title)}</h1>
  <div class="prerender-page__body">${renderInfoBodyHtml(payload.page.body)}</div>
</article>`.trim()
  }

  if (payload.kind === 'article') {
    return `
<article class="prerender-page prerender-page--article">
  <h1>${escapeHtml(payload.article.title)}</h1>
  <p>${escapeHtml(payload.article.excerpt)}</p>
  <div class="prerender-page__body">${renderInfoBodyHtml(payload.article.body)}</div>
</article>`.trim()
  }

  return `
<article class="prerender-page prerender-page--listing">
  <h1>${escapeHtml(payload.listing.title)}</h1>
  <p class="prerender-page__address">${escapeHtml(payload.listing.address)}</p>
  <p class="prerender-page__price">${escapeHtml(String(payload.listing.price))}</p>
</article>`.trim()
}

export function buildPrerenderScript(payload: PrerenderPayload | null): string {
  if (!payload) {
    return ''
  }

  return `<script type="application/json" id="renlo-prerender">${JSON.stringify(payload)}</script>`
}
