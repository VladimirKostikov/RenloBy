<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import {
  formatMarketChangePct,
  marketRankPercent,
  type ListingMarketSnapshotDto,
} from '@/lib/listingMarket'

const props = defineProps<{
  market: ListingMarketSnapshotDto
}>()

const { t } = useI18n()

const changeLabel = computed(() => formatMarketChangePct(props.market.changePct))
const changeClass = computed(() => {
  if (props.market.changePct < 0) {
    return 'listing-market-card__delta--down'
  }
  if (props.market.changePct > 0) {
    return 'listing-market-card__delta--up'
  }
  return 'listing-market-card__delta--flat'
})
const barWidth = computed(() => marketRankPercent(props.market.rank, props.market.similarCount))
const metricHint = computed(() =>
  props.market.metric === 'price_per_sqm'
    ? t('listingDetail.market.perSqm')
    : t('listingDetail.market.perMonth'),
)
</script>

<template>
  <div class="listing-market-card">
    <p class="listing-market-card__caption">{{ metricHint }}</p>

    <div class="listing-market-card__main">
      <CurrencyAmount
        class="listing-market-card__amount"
        :amount-usd="market.avg"
        :variant="market.metric === 'price_per_sqm' ? 'perSqm' : 'price'"
      />
      <em class="listing-market-card__delta" :class="changeClass">{{ changeLabel }}</em>
    </div>

    <p class="listing-market-card__rank">
      {{ t('listingDetail.market.rank', { rank: market.rank, total: market.similarCount }) }}
    </p>

    <div class="listing-market-card__bar" aria-hidden="true">
      <span class="listing-market-card__bar-fill" :style="{ width: `${barWidth}%` }" />
    </div>

    <div class="listing-market-card__stats">
      <div>
        <span>{{ t('listingDetail.market.current') }}</span>
        <strong>
          <CurrencyAmount
            :amount-usd="market.current"
            :variant="market.metric === 'price_per_sqm' ? 'perSqm' : 'price'"
          />
        </strong>
      </div>
      <div>
        <span>{{ t('listingDetail.market.min') }}</span>
        <strong>
          <CurrencyAmount
            :amount-usd="market.min"
            :variant="market.metric === 'price_per_sqm' ? 'perSqm' : 'price'"
          />
        </strong>
      </div>
      <div>
        <span>{{ t('listingDetail.market.max') }}</span>
        <strong>
          <CurrencyAmount
            :amount-usd="market.max"
            :variant="market.metric === 'price_per_sqm' ? 'perSqm' : 'price'"
          />
        </strong>
      </div>
      <div>
        <span>{{ t('listingDetail.market.avg') }}</span>
        <strong>
          <CurrencyAmount
            :amount-usd="market.avg"
            :variant="market.metric === 'price_per_sqm' ? 'perSqm' : 'price'"
          />
        </strong>
      </div>
    </div>

    <p v-if="market.aiGoodPrice" class="listing-market-card__badge">
      {{ t('listing.aiGoodPrice') }}
    </p>
  </div>
</template>

<style scoped>
.listing-market-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-height: 0;
  flex: 1;
}

.listing-market-card__caption {
  margin: 0;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-market-card__main {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 10px;
}

.listing-market-card__amount {
  font-size: 22px;
  font-weight: 700;
  color: var(--figma-ink-secondary);
}

.listing-market-card__amount :deep(.currency-amount__secondary) {
  font-size: 13px;
}

.listing-market-card__delta {
  font-style: normal;
  font-size: 13px;
  font-weight: 600;
}

.listing-market-card__delta--down {
  color: #15803d;
}

.listing-market-card__delta--up {
  color: var(--color-danger);
}

.listing-market-card__delta--flat {
  color: var(--figma-text-muted);
}

.listing-market-card__rank {
  margin: 0;
  font-size: 13px;
  color: #333;
}

.listing-market-card__bar {
  height: 6px;
  border-radius: 999px;
  background: var(--color-bg-muted);
  overflow: hidden;
}

.listing-market-card__bar-fill {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--figma-accent, #e14554);
}

.listing-market-card__stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px 12px;
  margin-top: auto;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-market-card__stats strong {
  display: block;
  margin-top: 4px;
  font-size: 13px;
  color: var(--figma-ink-secondary);
  font-weight: 600;
}

.listing-market-card__badge {
  margin: 0;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(225, 69, 84, 0.1);
  color: var(--figma-accent, #e14554);
  font-size: 12px;
  font-weight: 600;
  width: fit-content;
}
</style>
