export interface ReverseGeocodeResult {
  label: string
  region: string
  city: string
  district: string
  street: string
  house: string
  address: string
}

function isValidCoordinate(value: number, min: number, max: number): boolean {
  return Number.isFinite(value) && value >= min && value <= max
}

function pickCity(address: Record<string, string | undefined>): string {
  return address.city
    ?? address.town
    ?? address.village
    ?? address.municipality
    ?? address.county
    ?? ''
}

function pickDistrict(address: Record<string, string | undefined>): string {
  return address.city_district
    ?? address.suburb
    ?? address.neighbourhood
    ?? address.quarter
    ?? address.borough
    ?? ''
}

function pickStreet(address: Record<string, string | undefined>): string {
  return address.road
    ?? address.pedestrian
    ?? address.street
    ?? address.residential
    ?? ''
}

function buildStreetAddress(street: string, house: string): string {
  const parts = [street.trim(), house.trim()].filter(Boolean)
  return parts.join(', ')
}

function fromAddressParts(
  address: Record<string, string | undefined>,
  displayName?: string,
): ReverseGeocodeResult {
  const city = pickCity(address).trim()
  const district = pickDistrict(address).trim()
  const street = pickStreet(address).trim()
  const house = (address.house_number ?? '').trim()
  const region = (address.state ?? address.region ?? address.county ?? '').trim()
  const streetAddress = buildStreetAddress(street, house)
  const label = city || region || streetAddress || (displayName?.split(',')[0]?.trim() ?? '')

  return {
    label,
    region,
    city,
    district,
    street,
    house,
    address: streetAddress || (displayName ? displayName.split(',').slice(0, 2).join(',').trim() : ''),
  }
}

export async function reverseGeocode(
  lat: number,
  lng: number,
  locale: 'ru' | 'en',
): Promise<ReverseGeocodeResult | null> {
  if (!isValidCoordinate(lat, -90, 90) || !isValidCoordinate(lng, -180, 180)) {
    return null
  }

  const params = new URLSearchParams({
    lat: String(lat),
    lon: String(lng),
    format: 'json',
    addressdetails: '1',
    'accept-language': locale,
  })

  const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${params.toString()}`, {
    headers: {
      Accept: 'application/json',
    },
  })

  if (!response.ok) {
    return null
  }

  const payload = await response.json() as {
    display_name?: string
    address?: Record<string, string | undefined>
  }

  if (payload.address) {
    const result = fromAddressParts(payload.address, payload.display_name)
    if (result.label || result.address || result.city) {
      return result
    }
  }

  if (payload.display_name) {
    const [firstPart] = payload.display_name.split(',')
    return {
      label: firstPart?.trim() ?? '',
      region: '',
      city: firstPart?.trim() ?? '',
      district: '',
      street: '',
      house: '',
      address: payload.display_name.split(',').slice(0, 2).join(',').trim(),
    }
  }

  return null
}
