<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import CurrencyText from '@/components/CurrencyText.vue'
import { formatListingPrice } from '@/lib/formatPrice'
import { districtPath } from '@/lib/fullPageNav'
import type { DistrictWithStats } from '@/modules/location/lib/locationStats'
import { useCurrencyStore } from '@/stores/currency'

defineProps<{
  citySlug: string
  cityName: string
  items: DistrictWithStats[]
}>()

const { t } = useI18n()
const { code: currency } = storeToRefs(useCurrencyStore())

function districtPriceLabel(avgPrice: number): string {
  return t('location.districtAvgPrice', {
    price: formatListingPrice(avgPrice, currency.value),
  })
}
</script>

<template>
  <section class="location-districts">
    <h2 class="location-districts__title">{{ t('location.districtsTitle', { city: cityName }) }}</h2>
    <div class="location-districts__grid">
      <a
        v-for="item in items"
        :key="item.district.id"
        :href="districtPath(citySlug, item.district.slug)"
        class="location-districts__card"
      >
        <span class="location-districts__name">{{ item.district.name }}</span>
        <span class="location-districts__meta">
          {{ t('location.districtOffers', { n: item.stats.count }) }}
        </span>
        <CurrencyText
          v-if="item.stats.avgPrice > 0"
          class="location-districts__price"
          :text="districtPriceLabel(item.stats.avgPrice)"
        />
      </a>
    </div>
  </section>
</template>

<style scoped>
.location-districts {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.location-districts__title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.location-districts__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--figma-location-district-gap);
}

.location-districts__card {
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

.location-districts__card:hover {
  transform: translateY(-2px);
  border-color: rgba(225, 69, 84, 0.35);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}

.location-districts__name {
  font-size: 14px;
  font-weight: 600;
}

.location-districts__meta {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.72);
}

.location-districts__price {
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-accent);
}

@media (max-width: 767px) {
  .location-districts__grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .location-districts__card {
    min-height: 72px;
    padding: 14px;
  }
}

@media (min-width: 768px) and (max-width: 1279px) {
  .location-districts__grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
}
</style>
