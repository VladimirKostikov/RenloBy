import { fetchInfrastructurePois } from '@/api/infrastructure'
import type { InfrastructureBbox, InfrastructurePoi, InfrastructureType } from '@/types/infrastructure'
import type { ListingDto, MetroStationDto } from '@/types'

export interface ListingInfrastructureItem {
  icon: 'metro' | 'school' | 'shop' | 'park' | 'pharmacy'
  label: string
  minutes: number | null
  poi?: InfrastructurePoi
}

const WALKING_SPEED_M_PER_MIN = 80
const SUMMARY_TYPES: InfrastructureType[] = ['shop', 'school', 'park', 'pharmacy']
const CACHE_TTL_MS = 5 * 60 * 1000
export const INFRASTRUCTURE_SUMMARY_LIMIT = 5

type CacheEntry = {
  expiresAt: number
  promise?: Promise<InfrastructurePoi[]>
  pois?: InfrastructurePoi[]
}

const poiCache = new Map<string, CacheEntry>()

export function walkingMinutes(
  fromLat: number,
  fromLng: number,
  toLat: number,
  toLng: number,
): number {
  const latRad = ((toLat - fromLat) * Math.PI) / 180
  const lngRad = ((toLng - fromLng) * Math.PI) / 180
  const a =
    Math.sin(latRad / 2) ** 2 +
    Math.cos((fromLat * Math.PI) / 180) *
      Math.cos((toLat * Math.PI) / 180) *
      Math.sin(lngRad / 2) ** 2
  const meters = 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return Math.max(1, Math.round(meters / WALKING_SPEED_M_PER_MIN))
}

export function buildListingInfrastructureBbox(listing: ListingDto): InfrastructureBbox {
  const span = 0.012
  return {
    south: listing.latitude - span,
    north: listing.latitude + span,
    west: listing.longitude - span,
    east: listing.longitude + span,
    zoom: 15,
  }
}

function cacheKey(listing: ListingDto): string {
  const bbox = buildListingInfrastructureBbox(listing)
  return [
    listing.id,
    bbox.south.toFixed(4),
    bbox.west.toFixed(4),
    bbox.north.toFixed(4),
    bbox.east.toFixed(4),
    String(bbox.zoom ?? 15),
  ].join(':')
}

function pruneExpiredCache(now = Date.now()): void {
  for (const [key, entry] of poiCache) {
    if (entry.expiresAt <= now && !entry.promise) {
      poiCache.delete(key)
    }
  }
}

export function clearListingInfrastructureCache(): void {
  poiCache.clear()
}

async function loadListingInfrastructurePois(
  listing: ListingDto,
  fallbackNames: Record<InfrastructureType, string>,
  signal?: AbortSignal,
): Promise<InfrastructurePoi[]> {
  pruneExpiredCache()
  const key = cacheKey(listing)
  const cached = poiCache.get(key)
  const now = Date.now()

  if (cached?.pois && cached.expiresAt > now) {
    return cached.pois
  }

  if (cached?.promise && cached.expiresAt > now) {
    return cached.promise
  }

  const promise = fetchInfrastructurePois(
    SUMMARY_TYPES,
    buildListingInfrastructureBbox(listing),
    fallbackNames,
    signal,
  )
    .then((pois) => {
      poiCache.set(key, {
        expiresAt: Date.now() + CACHE_TTL_MS,
        pois,
      })
      return pois
    })
    .catch((error) => {
      const current = poiCache.get(key)
      if (current?.promise === promise) {
        poiCache.delete(key)
      }
      throw error
    })

  poiCache.set(key, {
    expiresAt: now + CACHE_TTL_MS,
    promise,
  })

  return promise
}

function toInfrastructureItems(
  listing: ListingDto,
  pois: InfrastructurePoi[],
): ListingInfrastructureItem[] {
  return pois
    .map((poi) => ({
      icon: poi.type,
      label: poi.name,
      minutes: walkingMinutes(listing.latitude, listing.longitude, poi.latitude, poi.longitude),
      poi,
    }))
    .sort((left, right) => left.minutes - right.minutes)
}

export async function fetchListingInfrastructureSummary(
  listing: ListingDto,
  metroStation: MetroStationDto | undefined,
  fallbackNames: Record<InfrastructureType, string>,
  signal?: AbortSignal,
): Promise<ListingInfrastructureItem[]> {
  const pois = await loadListingInfrastructurePois(listing, fallbackNames, signal)
  const items: ListingInfrastructureItem[] = []

  if (metroStation) {
    items.push({
      icon: 'metro',
      label: metroStation.name,
      minutes: listing.metroMinutes ?? null,
    })
  }

  items.push(...toInfrastructureItems(listing, pois))

  return items.slice(0, INFRASTRUCTURE_SUMMARY_LIMIT)
}

export async function fetchListingNearbyPlaces(
  listing: ListingDto,
  fallbackNames: Record<InfrastructureType, string>,
  signal?: AbortSignal,
): Promise<InfrastructurePoi[]> {
  const pois = await loadListingInfrastructurePois(listing, fallbackNames, signal)
  return toInfrastructureItems(listing, pois).map((item) => item.poi!)
}
