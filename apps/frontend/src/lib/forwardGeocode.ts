export interface ForwardGeocodeResult {
  latitude: number
  longitude: number
  label: string
}

function isUsableQuery(query: string): boolean {
  return query.trim().length >= 3
}

function isInBelarus(lat: number, lng: number): boolean {
  return lat >= 51.2 && lat <= 56.3 && lng >= 23.0 && lng <= 32.8
}

export async function forwardGeocode(
  query: string,
  locale: 'ru' | 'en',
): Promise<ForwardGeocodeResult | null> {
  if (!isUsableQuery(query)) {
    return null
  }

  const params = new URLSearchParams({
    q: query.trim(),
    format: 'json',
    addressdetails: '1',
    limit: '1',
    countrycodes: 'by',
    'accept-language': locale,
  })

  const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`, {
    headers: {
      Accept: 'application/json',
    },
  })

  if (!response.ok) {
    return null
  }

  const payload = await response.json() as Array<{
    lat?: string
    lon?: string
    display_name?: string
  }>

  const first = payload[0]
  if (!first) {
    return null
  }

  const latitude = Number(first.lat)
  const longitude = Number(first.lon)
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || !isInBelarus(latitude, longitude)) {
    return null
  }

  const label = first.display_name?.split(',').slice(0, 3).join(',').trim() || query.trim()

  return {
    latitude,
    longitude,
    label,
  }
}
