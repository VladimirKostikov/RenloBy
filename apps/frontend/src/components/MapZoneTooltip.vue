<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import type { ZoneTooltipData } from '@/types/map'

defineProps<{
  data: ZoneTooltipData | null
}>()

const { t } = useI18n()
</script>

<template>
  <Transition name="zone-tooltip">
    <div
      v-if="data"
      class="zone-tooltip"
      :style="{ left: `${data.x}px`, top: `${data.y}px` }"
    >
      <p class="zone-tooltip__title">{{ data.name }}</p>
      <p class="zone-tooltip__row">
        <span>{{ t('map.tooltip.count') }}</span>
        <strong>{{ data.count }}</strong>
      </p>
      <p class="zone-tooltip__row">
        <span>{{ t('map.tooltip.avgPrice') }}</span>
        <strong><CurrencyAmount :amount-usd="data.avgPrice" /></strong>
      </p>
      <p class="zone-tooltip__row">
        <span>{{ t('map.tooltip.avgSqm') }}</span>
        <strong><CurrencyAmount :amount-usd="data.avgPricePerSqm" variant="perSqm" /></strong>
      </p>
    </div>
  </Transition>
</template>

<style scoped>
.zone-tooltip {
  position: absolute;
  z-index: 700;
  min-width: 190px;
  padding: 12px 14px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  pointer-events: none;
  transform: translate(-50%, calc(-100% - 12px));
}

.zone-tooltip__title {
  margin: 0 0 8px;
  font-size: 14px;
  font-weight: 700;
  color: var(--figma-ink);
}

.zone-tooltip__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 0;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.zone-tooltip__row + .zone-tooltip__row {
  margin-top: 4px;
}

.zone-tooltip__row strong {
  font-weight: 700;
  color: var(--figma-ink);
}

.zone-tooltip-enter-active,
.zone-tooltip-leave-active {
  transition:
    opacity 0.16s ease,
    transform 0.16s ease;
}

.zone-tooltip-enter-from,
.zone-tooltip-leave-to {
  opacity: 0;
  transform: translate(-50%, calc(-100% - 4px));
}

@media (prefers-reduced-motion: reduce) {
  .zone-tooltip-enter-active,
  .zone-tooltip-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
