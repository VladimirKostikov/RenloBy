<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import CurrencyText from '@/components/CurrencyText.vue'
import { formatListingPrice } from '@/lib/formatPrice'
import { cityPath } from '@/lib/fullPageNav'
import type { CityWithStats } from '@/modules/location/lib/locationStats'
import { useCurrencyStore } from '@/stores/currency'

defineProps<{
  regionName: string
  items: CityWithStats[]
}>()

const { t } = useI18n()
const { code: currency } = storeToRefs(useCurrencyStore())

function cityPriceLabel(avgPrice: number): string {
  return t('location.districtAvgPrice', {
    price: formatListingPrice(avgPrice, currency.value),
  })
}
</script>

<template>
  <section class="location-cities">
    <h2 class="location-cities__title">{{ t('location.citiesTitle', { region: regionName }) }}</h2>
    <div class="location-cities__grid">
      <a
        v-for="item in items"
        :key="item.city.id"
        :href="cityPath(item.city.slug)"
        class="location-cities__card"
      >
        <span class="location-cities__name">{{ item.city.name }}</span>
        <span class="location-cities__meta">
          {{ t('location.districtOffers', { n: item.stats.count }) }}
        </span>
        <CurrencyText
          v-if="item.stats.avgPrice > 0"
          class="location-cities__price"
          :text="cityPriceLabel(item.stats.avgPrice)"
        />
      </a>
    </div>
  </section>
</template>

<style scoped>
.location-cities {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.location-cities__title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.location-cities__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--figma-location-district-gap);
}

.location-cities__card {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 88px;
  padding: 14px 16px;
  border-radius: var(--figma-location-district-radius);
  border: 1px solid var(--figma-border);
  background: var(--figma-surface);
  text-decoration: none;
  color: var(--figma-ink);
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.location-cities__card:hover {
  transform: translateY(-2px);
  border-color: rgba(225, 69, 84, 0.35);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}

.location-cities__name {
  font-size: 14px;
  font-weight: 600;
}

.location-cities__meta {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.72);
}

.location-cities__price {
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-accent);
}

@media (max-width: 767px) {
  .location-cities__grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .location-cities__card {
    min-height: 72px;
    padding: 14px;
  }
}

@media (min-width: 768px) and (max-width: 1279px) {
  .location-cities__grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
}
</style>
