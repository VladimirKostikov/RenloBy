<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { forwardGeocode } from '@/lib/forwardGeocode'
import {
  createYandexMap,
  fitMapToBounds,
  getBelarusBoundsPoints,
} from '@/lib/mapConfig'

const props = defineProps<{
  latitude: number
  longitude: number
}>()

const emit = defineEmits<{
  'update:coords': [latitude: number, longitude: number]
}>()

const { t, locale } = useI18n()
const mapRoot = ref<HTMLElement | null>(null)
const mapError = ref(false)
const locating = ref(false)
const addressQuery = ref('')
const addressSearching = ref(false)
const addressError = ref('')

let map: YandexMapInstance | null = null
let placemark: YandexGeoObject | null = null
let syncingFromProps = false

const DETAIL_ZOOM = 14

function readCoords(event: YandexMapEvent): [number, number] | null {
  const coords = event.get('coords')
  if (!Array.isArray(coords) || coords.length < 2) {
    return null
  }
  const lat = Number(coords[0])
  const lng = Number(coords[1])
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return null
  }
  return [lat, lng]
}

function emitCoords(lat: number, lng: number) {
  emit('update:coords', lat, lng)
}

function setPlacemark(lat: number, lng: number) {
  if (!map) {
    return
  }

  if (placemark?.geometry?.setCoordinates) {
    syncingFromProps = true
    placemark.geometry.setCoordinates([lat, lng])
    syncingFromProps = false
    return
  }

  placemark = new ymaps.Placemark(
    [lat, lng],
    {},
    {
      preset: 'islands#redDotIcon',
      draggable: true,
    },
  )

  placemark.events.add('dragend', () => {
    if (syncingFromProps) {
      return
    }
    const coords = placemark?.geometry?.getCoordinates?.()
    if (!coords || coords.length < 2) {
      return
    }
    emitCoords(Number(coords[0]), Number(coords[1]))
  })

  map.geoObjects.add(placemark)
}

function focusOnCoords(lat: number, lng: number, zoom = DETAIL_ZOOM) {
  map?.setCenter([lat, lng], zoom, { duration: 200 })
}

function placeAt(lat: number, lng: number, zoom = DETAIL_ZOOM) {
  setPlacemark(lat, lng)
  focusOnCoords(lat, lng, zoom)
  emitCoords(lat, lng)
}

function handleMapClick(event: YandexMapEvent) {
  const coords = readCoords(event)
  if (!coords) {
    return
  }
  addressError.value = ''
  placeAt(coords[0], coords[1])
}

async function searchAddress() {
  const query = addressQuery.value.trim()
  if (query.length < 3 || addressSearching.value) {
    return
  }

  addressSearching.value = true
  addressError.value = ''

  try {
    const result = await forwardGeocode(
      query,
      locale.value === 'en' ? 'en' : 'ru',
    )
    if (!result) {
      addressError.value = t('account.wizard.mapAddressNotFound')
      return
    }

    if (map) {
      placeAt(result.latitude, result.longitude, 16)
    } else {
      emitCoords(result.latitude, result.longitude)
    }
    addressQuery.value = result.label
  } catch {
    addressError.value = t('account.wizard.mapAddressNotFound')
  } finally {
    addressSearching.value = false
  }
}

async function useMyLocation() {
  if (!navigator.geolocation) {
    return
  }
  locating.value = true
  addressError.value = ''
  try {
    const position = await new Promise<GeolocationPosition>((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
      })
    })
    placeAt(position.coords.latitude, position.coords.longitude, 16)
  } catch {
    // keep current marker
  } finally {
    locating.value = false
  }
}

onMounted(async () => {
  if (!mapRoot.value) {
    mapError.value = true
    return
  }

  try {
    map = await createYandexMap(mapRoot.value)
    fitMapToBounds(map, getBelarusBoundsPoints(), 7, 0)
    setPlacemark(props.latitude, props.longitude)
    map.events.add('click', handleMapClick)
  } catch {
    mapError.value = true
  }
})

watch(
  () => [props.latitude, props.longitude] as const,
  ([lat, lng]) => {
    if (!map || !Number.isFinite(lat) || !Number.isFinite(lng)) {
      return
    }
    setPlacemark(lat, lng)
  },
)

onUnmounted(() => {
  if (map) {
    map.events.remove('click', handleMapClick)
    map.destroy()
  }
  map = null
  placemark = null
})
</script>

<template>
  <div class="wizard-location-map">
    <form class="wizard-location-map__search" @submit.prevent="searchAddress">
      <label class="wizard-location-map__search-field">
        <span class="wizard-location-map__search-label">{{ t('account.wizard.mapAddress') }}</span>
        <input
          v-model="addressQuery"
          type="search"
          class="wizard-location-map__search-input"
          maxlength="240"
          autocomplete="street-address"
          :placeholder="t('account.wizard.mapAddressPlaceholder')"
          :disabled="addressSearching"
        />
      </label>
      <button
        type="submit"
        class="wizard-location-map__search-btn"
        :disabled="addressSearching || addressQuery.trim().length < 3"
      >
        {{ addressSearching ? t('listing.loading') : t('account.wizard.mapAddressFind') }}
      </button>
    </form>
    <p v-if="addressError" class="wizard-location-map__search-error" role="alert">
      {{ addressError }}
    </p>

    <div v-if="mapError" class="wizard-location-map__fallback">
      {{ t('map.unavailable') }}
    </div>
    <div v-else ref="mapRoot" class="wizard-location-map__canvas" />
    <div class="wizard-location-map__toolbar">
      <p class="wizard-location-map__hint">{{ t('account.wizard.mapHint') }}</p>
      <button
        type="button"
        class="wizard-location-map__locate"
        :disabled="locating || mapError"
        @click="useMyLocation"
      >
        {{ locating ? t('listing.loading') : t('account.wizard.useMyLocation') }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.wizard-location-map {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
  min-width: 0;
  flex: 1 1 auto;
  min-height: 0;
}

.wizard-location-map__search {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 10px;
  width: 100%;
}

.wizard-location-map__search-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1 1 220px;
  min-width: 0;
}

.wizard-location-map__search-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text, #000);
}

.wizard-location-map__search-input {
  box-sizing: border-box;
  width: 100%;
  min-height: 44px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: #fff;
  color: var(--color-text, #000);
  font-family: inherit;
  font-size: 14px;
}

.wizard-location-map__search-input:focus {
  outline: 2px solid color-mix(in srgb, var(--figma-accent) 45%, transparent);
  outline-offset: 1px;
  border-color: var(--figma-accent);
}

.wizard-location-map__search-input:disabled {
  opacity: 0.7;
}

.wizard-location-map__search-btn {
  min-height: 44px;
  padding: 0 16px;
  border: none;
  border-radius: 10px;
  background: var(--figma-accent);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    transform 0.15s ease;
}

.wizard-location-map__search-btn:hover:not(:disabled) {
  background: var(--figma-accent-hover);
}

.wizard-location-map__search-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.wizard-location-map__search-btn:disabled {
  opacity: 0.55;
  cursor: default;
}

.wizard-location-map__search-error {
  margin: 0;
  font-size: 13px;
  color: #b91c1c;
}

.wizard-location-map__canvas,
.wizard-location-map__fallback {
  width: 100%;
  flex: 1 1 auto;
  height: clamp(260px, 42vh, 520px);
  min-height: 260px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  overflow: hidden;
  background: var(--figma-page-bg, #f5f5f5);
}

.wizard-location-map__fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  text-align: center;
  font-size: 14px;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.65));
}

.wizard-location-map__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px 12px;
}

.wizard-location-map__hint {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.65));
}

.wizard-location-map__locate {
  min-height: 40px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: #fff;
  color: var(--color-text, #000);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.wizard-location-map__locate:disabled {
  opacity: 0.6;
  cursor: default;
}

@media (max-width: 767px) {
  .wizard-location-map__search-btn {
    width: 100%;
  }

  .wizard-location-map__canvas,
  .wizard-location-map__fallback {
    height: 220px;
  }
}
</style>
