import type { ListingDto } from '@/types'
import type { InfrastructurePoi, InfrastructureType } from '@/types/infrastructure'

export type MapBoundsBox = {
  south: number
  west: number
  north: number
  east: number
}

const TYPE_NAME_SUFFIX: Record<InfrastructureType, string[]> = {
  shop: ['Продукты', 'Супермаркет', 'Евроопт', 'Магазин'],
  pharmacy: ['Аптека', 'Белфармация', 'Аптека 24'],
  school: ['Школа', 'Гимназия', 'Лицей', 'Садик'],
  park: ['Парк', 'Сквер', 'Бульвар'],
}

function boundsContains(box: MapBoundsBox, latitude: number, longitude: number): boolean {
  return latitude >= box.south
    && latitude <= box.north
    && longitude >= box.west
    && longitude <= box.east
}

export function buildInfrastructurePoisFromListings(
  listings: ListingDto[],
  types: InfrastructureType[],
  fallbackNames: Record<InfrastructureType, string>,
  bounds?: MapBoundsBox,
): InfrastructurePoi[] {
  if (types.length === 0) {
    return []
  }

  const pois: InfrastructurePoi[] = []

  for (const listing of listings) {
    if (!Number.isFinite(listing.latitude) || !Number.isFinite(listing.longitude)) {
      continue
    }

    const address = listing.address?.trim()
    if (!address) {
      continue
    }

    if (bounds && !boundsContains(bounds, listing.latitude, listing.longitude)) {
      continue
    }

    for (const type of types) {
      const suffixes = TYPE_NAME_SUFFIX[type]
      const name = suffixes[listing.id % suffixes.length] ?? fallbackNames[type]

      pois.push({
        id: `listing-${listing.id}-${type}`,
        type,
        name,
        address,
        latitude: listing.latitude,
        longitude: listing.longitude,
      })
    }
  }

  return pois
}
