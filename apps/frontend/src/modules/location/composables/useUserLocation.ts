import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { CITY_COORDS } from '@/lib/cityCoords'
import { findNearestCitySlug } from '@/lib/findNearestCitySlug'
import { reverseGeocode } from '@/lib/reverseGeocode'
import { useListingsStore } from '@/stores/listings'

const CITY_STORAGE_KEY = 'donmap-city-id'
const ADDRESS_STORAGE_KEY = 'donmap-address'

let bootstrapPromise: Promise<void> | null = null

export function resetUserLocationBootstrap() {
  bootstrapPromise = null
}

function readStoredCityId(): number | undefined {
  const raw = localStorage.getItem(CITY_STORAGE_KEY)
  if (!raw) {
    return undefined
  }

  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : undefined
}

function readStoredAddress(): string {
  return localStorage.getItem(ADDRESS_STORAGE_KEY) ?? ''
}

function persistCity(cityId: number, addressLabel: string) {
  localStorage.setItem(CITY_STORAGE_KEY, String(cityId))
  localStorage.setItem(ADDRESS_STORAGE_KEY, addressLabel)
}

function clearPersistedCity() {
  localStorage.removeItem(CITY_STORAGE_KEY)
  localStorage.removeItem(ADDRESS_STORAGE_KEY)
}

async function applyNationwide(
  listings: ReturnType<typeof useListingsStore>,
  addressLabel: ReturnType<typeof ref<string>>,
  belarusLabel: string,
) {
  listings.regionSlug = undefined
  listings.cityId = undefined
  listings.districtId = undefined
  listings.setMapNationwide(true)
  clearPersistedCity()
  addressLabel.value = belarusLabel
  await listings.loadDistricts()
}

async function applyCity(
  listings: ReturnType<typeof useListingsStore>,
  cityId: number,
  addressLabel: string,
) {
  listings.cityId = cityId
  listings.regionSlug = undefined
  listings.districtId = undefined
  listings.setMapNationwide(false)
  persistCity(cityId, addressLabel)
  await listings.loadDistricts(cityId)
}

async function detectUserLocation(
  listings: ReturnType<typeof useListingsStore>,
  locale: 'ru' | 'en',
  addressLabel: ReturnType<typeof ref<string>>,
  detecting: ReturnType<typeof ref<boolean>>,
  belarusLabel: string,
) {
  if (!navigator.geolocation) {
    await applyNationwide(listings, addressLabel, belarusLabel)
    return
  }

  detecting.value = true

  try {
    const position = await new Promise<GeolocationPosition>((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: false,
        timeout: 8000,
        maximumAge: 300000,
      })
    })

    const { latitude, longitude } = position.coords
    const nearestSlug = findNearestCitySlug(latitude, longitude, CITY_COORDS)
    const geocoded = await reverseGeocode(latitude, longitude, locale)
    const city = nearestSlug
      ? listings.cities.find((item) => item.slug === nearestSlug)
      : undefined

    if (city) {
      addressLabel.value = geocoded?.label ?? city.name
      await applyCity(listings, city.id, addressLabel.value)
      await listings.search()
      return
    }

    if (geocoded?.label) {
      addressLabel.value = geocoded.label
    }

    await applyNationwide(listings, addressLabel, belarusLabel)
  } catch {
    await applyNationwide(listings, addressLabel, belarusLabel)
  } finally {
    detecting.value = false
  }
}

async function runBootstrap(
  listings: ReturnType<typeof useListingsStore>,
  _locale: 'ru' | 'en',
  addressLabel: ReturnType<typeof ref<string>>,
  _detecting: ReturnType<typeof ref<boolean>>,
  ready: ReturnType<typeof ref<boolean>>,
  belarusLabel: string,
) {
  if (!listings.cities.length) {
    await listings.loadReferenceData()
  }

  const storedCityId = readStoredCityId()
  const storedAddress = readStoredAddress()

  if (storedCityId) {
    const storedCity = listings.cities.find((city) => city.id === storedCityId)
    if (storedCity) {
      addressLabel.value = storedAddress || storedCity.name
      if (listings.cityId !== storedCityId) {
        listings.cityId = storedCityId
        listings.regionSlug = undefined
        listings.setMapNationwide(false)
        await listings.loadDistricts(storedCityId)
        await listings.search()
      }
      ready.value = true
      return
    }
  }

  await applyNationwide(listings, addressLabel, belarusLabel)
  ready.value = true
}

export function useUserLocation() {
  const { t, locale } = useI18n()
  const listings = useListingsStore()
  const addressLabel = ref(readStoredAddress())
  const detecting = ref(false)
  const ready = ref(false)

  const belarusLabel = computed(() => t('map.breadcrumb.belarus'))

  const cityOptions = computed(() => listings.cities.map((city) => ({
    value: city.id,
    label: city.name,
  })))

  const selectedCityId = computed(() => listings.cityId ?? '')

  async function bootstrap() {
    const currentLocale = locale.value === 'en' ? 'en' : 'ru'
    if (!bootstrapPromise) {
      bootstrapPromise = runBootstrap(
        listings,
        currentLocale,
        addressLabel,
        detecting,
        ready,
        belarusLabel.value,
      )
    }
    await bootstrapPromise
  }

  async function selectCity(cityId: number) {
    const city = listings.cities.find((item) => item.id === cityId)
    if (!city) {
      return
    }

    addressLabel.value = city.name
    await applyCity(listings, city.id, city.name)
    await listings.search()
  }

  async function selectNationwide() {
    await applyNationwide(listings, addressLabel, belarusLabel.value)
    await listings.search()
  }

  async function refreshLocation() {
    bootstrapPromise = null
    await detectUserLocation(
      listings,
      locale.value === 'en' ? 'en' : 'ru',
      addressLabel,
      detecting,
      belarusLabel.value,
    )
    addressLabel.value = addressLabel.value
      || listings.cities.find((city) => city.id === listings.cityId)?.name
      || belarusLabel.value
    ready.value = true
  }

  return {
    addressLabel,
    detecting,
    ready,
    cityOptions,
    selectedCityId,
    bootstrap,
    selectCity,
    selectNationwide,
    refreshLocation,
  }
}
