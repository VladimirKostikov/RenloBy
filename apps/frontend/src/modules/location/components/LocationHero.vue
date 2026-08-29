<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import type { LocationKind } from '@/modules/location/lib/resolveLocation'
import type { MapZoneStats } from '@/types/map'

defineProps<{
  kind: LocationKind
  title: string
  stats: MapZoneStats
}>()

const { t } = useI18n()
</script>

<template>
  <section class="location-hero">
    <div class="location-hero__head">
      <h1 class="location-hero__title">{{ title }}</h1>
      <p class="location-hero__subtitle">{{ t('location.subtitle') }}</p>
    </div>

    <div class="location-hero__stats">
      <article class="location-hero__stat">
        <span class="location-hero__stat-label">{{ t('map.tooltip.count') }}</span>
        <strong class="location-hero__stat-value">{{ stats.count }}</strong>
      </article>
      <article class="location-hero__stat">
        <span class="location-hero__stat-label">{{ t('map.tooltip.avgPrice') }}</span>
        <strong class="location-hero__stat-value">
          <CurrencyAmount :amount-usd="stats.avgPrice" />
        </strong>
      </article>
      <article class="location-hero__stat">
        <span class="location-hero__stat-label">{{ t('map.tooltip.avgSqm') }}</span>
        <strong class="location-hero__stat-value">
          <CurrencyAmount :amount-usd="stats.avgPricePerSqm" variant="perSqm" />
        </strong>
      </article>
    </div>
  </section>
</template>

<style scoped>
.location-hero {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 24px;
  border-radius: var(--figma-location-hero-radius);
  background: var(--figma-surface);
  border: 1px solid var(--figma-border);
}

.location-hero__head {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.location-hero__title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  line-height: 1.25;
}

.location-hero__subtitle {
  margin: 0;
  font-size: 14px;
  color: rgba(0, 0, 0, 0.72);
}

.location-hero__stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.location-hero__stat {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 14px 16px;
  border-radius: var(--figma-location-stat-radius);
  background: var(--figma-page-bg);
  transition: transform 0.2s ease, background-color 0.2s ease;
}

.location-hero__stat-label {
  font-size: 11px;
  color: rgba(0, 0, 0, 0.72);
}

.location-hero__stat-value {
  font-size: 16px;
  font-weight: 600;
  color: var(--figma-ink);
}

@media (max-width: 767px) {
  .location-hero {
    padding: 18px;
  }

  .location-hero__title {
    font-size: 20px;
  }

  .location-hero__stats {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 1280px) {
  .location-hero {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .location-hero__title {
    font-size: 28px;
  }

  .location-hero__stats {
    width: min(100%, 520px);
    flex-shrink: 0;
  }
}
</style>
