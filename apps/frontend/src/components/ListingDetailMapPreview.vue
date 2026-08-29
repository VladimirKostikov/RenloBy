<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { createYandexMap } from '@/lib/mapConfig'

const props = defineProps<{
  latitude: number
  longitude: number
}>()

defineEmits<{
  showOnMap: []
}>()

const { t } = useI18n()

const mapRoot = ref<HTMLElement | null>(null)
const mapError = ref(false)
let map: YandexMapInstance | null = null

onMounted(async () => {
  if (!mapRoot.value || !Number.isFinite(props.latitude) || !Number.isFinite(props.longitude)) {
    mapError.value = true
    return
  }

  try {
    map = await createYandexMap(mapRoot.value, [props.latitude, props.longitude], 15)
    map.behaviors.disable(['drag', 'scrollZoom', 'dblClickZoom', 'multiTouch'])

    map.geoObjects.add(
      new ymaps.Placemark(
        [props.latitude, props.longitude],
        {},
        { preset: 'islands#redDotIcon' },
      ),
    )
  } catch {
    mapError.value = true
  }
})

onUnmounted(() => {
  map?.destroy()
  map = null
})
</script>

<template>
  <div class="map-preview">
    <div v-if="mapError" class="map-preview__fallback">
      {{ t('map.unavailable') }}
    </div>
    <div v-else ref="mapRoot" class="map-preview__canvas" />
    <button type="button" class="map-preview__cta" @click="$emit('showOnMap')">
      <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path
          d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Z"
          stroke="currentColor"
          stroke-width="1.5"
        />
        <circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5" />
      </svg>
      {{ t('listingDetail.showOnMap') }}
    </button>
  </div>
</template>

<style scoped>
.map-preview {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: inherit;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  overflow: hidden;
  background: var(--figma-surface);
}

.map-preview__canvas {
  flex: 1 1 auto;
  width: 100%;
  min-height: 186px;
  height: 186px;
}

.map-preview__fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1 1 auto;
  width: 100%;
  min-height: 186px;
  height: 186px;
  padding: 16px;
  text-align: center;
  font-size: 14px;
  color: var(--figma-text-muted);
  background: var(--figma-page-bg);
}

.map-preview__cta {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  gap: 8px;
  width: 100%;
  height: 52px;
  border: none;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}
</style>
