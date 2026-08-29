<script setup lang="ts">
import { computed } from 'vue'
import {
  formatListingPrice,
  formatListingPriceDetailed,
  formatListingPricePerSqm,
} from '@/lib/formatPrice'

const props = withDefaults(
  defineProps<{
    amountUsd: number
    variant?: 'price' | 'detailed' | 'perSqm'
    tag?: string
  }>(),
  {
    variant: 'price',
    tag: 'span',
  },
)

const primary = computed(() => {
  if (props.variant === 'detailed') {
    return formatListingPriceDetailed(props.amountUsd, 'byn')
  }
  if (props.variant === 'perSqm') {
    return formatListingPricePerSqm(props.amountUsd, 'byn')
  }
  return formatListingPrice(props.amountUsd, 'byn')
})

const secondary = computed(() => {
  if (props.variant === 'detailed') {
    return formatListingPriceDetailed(props.amountUsd, 'usd')
  }
  if (props.variant === 'perSqm') {
    return formatListingPricePerSqm(props.amountUsd, 'usd')
  }
  return formatListingPrice(props.amountUsd, 'usd')
})
</script>

<template>
  <component :is="tag" class="currency-amount">
    <span class="currency-amount__primary">{{ primary }}</span>
    <span class="currency-amount__secondary">{{ secondary }}</span>
  </component>
</template>

<style scoped>
.currency-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-end;
  gap: 2px;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
  vertical-align: bottom;
  line-height: 1.15;
}

.currency-amount__primary,
.currency-amount__secondary {
  display: block;
  box-sizing: border-box;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.currency-amount__secondary {
  color: var(--figma-text-muted, #929292);
  font-size: 13px;
  font-weight: 400;
  line-height: 1.2;
}
</style>
