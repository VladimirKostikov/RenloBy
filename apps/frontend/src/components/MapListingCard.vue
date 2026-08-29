<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import ListingImageSlider from '@/components/ListingImageSlider.vue'
import MetroIcon from '@/components/MetroIcon.vue'
import type { PopupPlacement } from '@/lib/mapPopupPosition'
import type { ListingDto, MetroStationDto } from '@/types'

const props = defineProps<{
  listing: ListingDto
  metroStation?: MetroStationDto
  districtName?: string
  left: number
  top: number
  placement?: PopupPlacement
  cardWidth?: number
  maxHeight?: number
  loading?: boolean
}>()

const cardRoot = ref<HTMLElement | null>(null)

const emit = defineEmits<{
  close: []
  viewDetails: []
}>()

const { t } = useI18n()

const cardStyle = computed(() => ({
  left: `${props.left}px`,
  top: `${props.top}px`,
  width: props.cardWidth ? `${props.cardWidth}px` : undefined,
  maxHeight: props.maxHeight ? `${props.maxHeight}px` : undefined,
}))

defineExpose({
  getRootElement: () => cardRoot.value,
})
</script>

<template>
  <div
    ref="cardRoot"
    class="map-card"
    :class="placement === 'below' ? 'map-card--below' : 'map-card--above'"
    :style="cardStyle"
    @click.stop
    @mousedown.stop
  >
    <button type="button" class="map-card__close" :aria-label="t('map.card.close')" @click.stop="emit('close')">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" />
      </svg>
    </button>

    <div class="map-card__media">
      <ListingImageSlider
        class="map-card__slider"
        :images="listing.images"
        :alt="listing.address"
        :reset-key="listing.id"
        :enable-lightbox="false"
        :show-counter="false"
        compact
      />

      <div v-if="loading" class="map-card__media-loader" aria-live="polite">
        <div class="map-card__spinner" />
        <span>{{ t('map.card.loading') }}</span>
      </div>
    </div>

    <div class="map-card__body">
      <div class="map-card__price-row">
        <CurrencyAmount class="map-card__price" :amount-usd="listing.price" />
        <CurrencyAmount class="map-card__sqm" :amount-usd="listing.pricePerSqm" variant="perSqm" />
      </div>

      <div class="map-card__specs">
        <span>{{ t('listing.roomsShort', { n: listing.rooms }) }}</span>
        <span class="map-card__dot" />
        <span>{{ t('listing.areaShort', { n: listing.area }) }}</span>
        <span class="map-card__dot" />
        <span>{{ t('listing.floorShort', { floor: listing.floor, total: listing.totalFloors }) }}</span>
      </div>

      <p class="map-card__address">{{ listing.address }}</p>
      <p v-if="districtName" class="map-card__district">{{ districtName }}</p>

      <div v-if="metroStation" class="map-card__metro">
        <MetroIcon :color="metroStation.lineColor" />
        <span>{{ metroStation.name }}</span>
        <span v-if="listing.metroMinutes" class="map-card__dot" />
        <span v-if="listing.metroMinutes">{{ listing.metroMinutes }} мин.</span>
      </div>

      <div class="map-card__badges">
        <span v-if="listing.verified" class="map-card__badge map-card__badge--verified">
          <img src="/figma/verified.svg" alt="" width="10" height="10" />
          {{ t('listing.verified') }}
        </span>
        <span v-if="listing.aiGoodPrice" class="map-card__badge map-card__badge--ai">
          <img src="/figma/ai-star.svg" alt="" width="11" height="12" />
          {{ t('listing.aiGoodPrice') }}
        </span>
      </div>

      <button type="button" class="map-card__cta" @click.stop="emit('viewDetails')">
        {{ t('listing.viewDetails') }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.map-card {
  position: absolute;
  z-index: 30;
  display: flex;
  flex-direction: column;
  width: 263px;
  max-width: calc(100vw - 24px);
  overflow: hidden;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: var(--figma-surface);
  box-shadow: 0 12px 32px rgba(17, 24, 39, 0.16);
  pointer-events: auto;
}

.map-card--above {
  transform: translate(-50%, calc(-100% - 12px));
}

.map-card--below {
  transform: translate(-50%, 12px);
}

.map-card__close {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 4;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  cursor: pointer;
}

.map-card__media {
  position: relative;
  width: 100%;
  height: 148px;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--figma-placeholder);
}

.map-card__slider {
  width: 100%;
  height: 100%;
}

.map-card__media-loader {
  position: absolute;
  inset: 0;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.72);
  color: var(--figma-text-muted);
  font-size: 12px;
  font-weight: 600;
}

.map-card__spinner {
  width: 22px;
  height: 22px;
  border: 2px solid rgba(0, 0, 0, 0.12);
  border-top-color: var(--figma-accent);
  border-radius: 50%;
  animation: map-card-spin 0.7s linear infinite;
}

@keyframes map-card-spin {
  to {
    transform: rotate(360deg);
  }
}

.map-card__body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 12px;
}

.map-card__price-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 4px 8px;
  min-width: 0;
  max-width: 100%;
}

.map-card__price {
  min-width: 0;
  max-width: 100%;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.1;
  color: var(--figma-ink);
}

.map-card__sqm {
  min-width: 0;
  max-width: 100%;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.map-card__specs {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.map-card__dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #c4c4c4;
}

.map-card__address {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.map-card__district {
  margin: 0;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.map-card__metro {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--figma-ink);
}

.map-card__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.map-card__badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}

.map-card__badge--verified {
  background: var(--figma-verified-bg);
  color: var(--figma-verified-text);
}

.map-card__badge--ai {
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.map-card__cta {
  margin-top: 8px;
  width: 100%;
  min-height: 36px;
  height: auto;
  padding: 8px 12px;
  border: none;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

@media (prefers-reduced-motion: reduce) {
  .map-card__spinner {
    animation: none;
  }
}

@media (max-width: 767px) {
  .map-card {
    width: min(320px, calc(100vw - 32px));
  }

  .map-card__cta {
    min-height: 40px;
    padding: 0 12px;
    font-size: 14px;
  }
}
</style>
