import type { CityCoordsMap } from '@/lib/cityCoords'

function toRadians(value: number): number {
  return (value * Math.PI) / 180
}

export function haversineKm(lat1: number, lng1: number, lat2: number, lng2: number): number {
  const earthRadiusKm = 6371
  const dLat = toRadians(lat2 - lat1)
  const dLng = toRadians(lng2 - lng1)
  const a = Math.sin(dLat / 2) ** 2
    + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLng / 2) ** 2

  return earthRadiusKm * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)))
}

export function findNearestCitySlug(
  lat: number,
  lng: number,
  coords: CityCoordsMap,
): string | undefined {
  let nearestSlug: string | undefined
  let nearestDistance = Number.POSITIVE_INFINITY

  for (const [slug, [cityLat, cityLng]] of Object.entries(coords)) {
    const distance = haversineKm(lat, lng, cityLat, cityLng)
    if (distance < nearestDistance) {
      nearestDistance = distance
      nearestSlug = slug
    }
  }

  return nearestSlug
}
