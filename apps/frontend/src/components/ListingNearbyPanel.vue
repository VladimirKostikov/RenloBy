<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { getYandexMapsPointUrl } from '@/lib/infrastructureMaps'
import { walkingMinutes } from '@/lib/listingNearbyInfrastructure'
import type { InfrastructurePoi } from '@/types/infrastructure'
import type { ListingDto } from '@/types'

const props = defineProps<{
  listing: ListingDto
  places: InfrastructurePoi[]
}>()

const emit = defineEmits<{
  close: []
}>()

const { t } = useI18n()

const rows = computed(() =>
  props.places.map((place) => ({
    place,
    minutes: walkingMinutes(
      props.listing.latitude,
      props.listing.longitude,
      place.latitude,
      place.longitude,
    ),
    mapsUrl: getYandexMapsPointUrl(place.latitude, place.longitude),
  })),
)

function iconFor(type: InfrastructurePoi['type']): string {
  if (type === 'school') {
    return '/figma/infra-school.svg'
  }
  if (type === 'shop') {
    return '/figma/infra-shop.svg'
  }
  if (type === 'pharmacy') {
    return '/figma/infra-pharmacy.svg'
  }
  if (type === 'park') {
    return '/figma/infra-park.svg'
  }
  return '/figma/infra-shop.svg'
}
</script>

<template>
  <Teleport to="body">
    <div class="nearby-overlay" @click.self="emit('close')">
      <div class="nearby-panel" role="dialog" aria-modal="true" @click.stop>
        <div class="nearby-panel__header">
          <h3>{{ t('listingDetail.nearbyTitle') }}</h3>
          <button type="button" class="nearby-panel__close" :aria-label="t('listingDetail.close')" @click="emit('close')">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" />
            </svg>
          </button>
        </div>

        <p class="nearby-panel__hint">{{ t('listingDetail.nearbyHint') }}</p>

        <div v-if="rows.length === 0" class="nearby-panel__empty">{{ t('listingDetail.nearbyEmpty') }}</div>

        <ul v-else class="nearby-panel__list">
          <li v-for="row in rows" :key="row.place.id" class="nearby-panel__item">
            <img
              :data-theme-ink="row.place.type !== 'park' ? '' : undefined"
              :src="iconFor(row.place.type)"
              alt=""
              width="20"
              height="20"
            />
            <div class="nearby-panel__item-body">
              <strong>{{ row.place.name }}</strong>
              <span>{{ row.place.address }}</span>
            </div>
            <div class="nearby-panel__item-meta">
              <span>{{ t('listingDetail.minutesShort', { n: row.minutes }) }}</span>
              <a :href="row.mapsUrl" target="_blank" rel="noopener noreferrer">{{ t('listingDetail.openInYandex') }}</a>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.nearby-overlay {
  position: fixed;
  inset: 0;
  z-index: 4000;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.5);
  animation: nearby-overlay-in 0.24s ease;
}

.nearby-panel {
  width: min(560px, 100%);
  max-height: min(80vh, 640px);
  overflow: auto;
  padding: 20px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.2);
  animation: nearby-panel-in 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.nearby-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.nearby-panel__header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--figma-ink);
}

.nearby-panel__close {
  border: none;
  background: transparent;
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  color: var(--figma-ink);
  cursor: pointer;
}

.nearby-panel__hint {
  margin: 0 0 16px;
  font-size: 13px;
  color: var(--figma-text-muted);
}

.nearby-panel__empty {
  padding: 24px 0;
  text-align: center;
  color: var(--figma-text-muted);
}

.nearby-panel__list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.nearby-panel__item {
  display: grid;
  grid-template-columns: 24px 1fr auto;
  gap: 12px;
  align-items: start;
  padding: 12px 0;
  border-top: 1px solid var(--figma-border);
}

.nearby-panel__item-body {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.nearby-panel__item-body strong {
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.nearby-panel__item-body span {
  font-size: 12px;
  color: var(--figma-text-muted);
}

.nearby-panel__item-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  font-size: 12px;
  white-space: nowrap;
  color: var(--figma-ink);
}

.nearby-panel__item-meta a {
  color: var(--figma-accent);
  font-weight: 600;
  text-decoration: none;
}

@keyframes nearby-overlay-in {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}

@keyframes nearby-panel-in {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.98);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
