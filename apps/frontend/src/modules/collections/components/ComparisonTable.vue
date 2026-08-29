<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { formatListingPrice, formatListingPricePerSqm } from '@/lib/formatPrice'
import {
  findBestComparisonIndexes,
  type ComparisonRowKey,
} from '@/lib/comparisonHighlights'
import { listingPath, navigateTo } from '@/lib/fullPageNav'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import CurrencyText from '@/components/CurrencyText.vue'
import ListingVerifiedBadge from '@/components/ListingVerifiedBadge.vue'
import { useCurrencyStore } from '@/stores/currency'
import type { ListingDto } from '@/types'

const props = withDefaults(
  defineProps<{
    listings: ListingDto[]
    compact?: boolean
  }>(),
  { compact: false },
)

const emit = defineEmits<{
  remove: [listingId: number]
}>()

const { t } = useI18n()
const { code: currency } = storeToRefs(useCurrencyStore())

const columnCount = computed(() => Math.max(props.listings.length, 1))

const rows = computed(() => {
  const keys: ComparisonRowKey[] = [
    'price',
    'pricePerSqm',
    'rooms',
    'area',
    'floor',
    'address',
    'dealType',
  ]

  return keys.map((key) => {
    const bestIndexes = findBestComparisonIndexes(props.listings, key)
    const values =
      key === 'price'
        ? props.listings.map((listing) => formatListingPrice(listing.price, currency.value))
        : key === 'pricePerSqm'
          ? props.listings.map((listing) =>
              formatListingPricePerSqm(listing.pricePerSqm, currency.value),
            )
          : key === 'rooms'
            ? props.listings.map((listing) => t('listing.roomsShort', { n: listing.rooms }))
            : key === 'area'
              ? props.listings.map((listing) => t('listing.areaShort', { n: listing.area }))
              : key === 'floor'
                ? props.listings.map((listing) =>
                    t('listing.floorShort', {
                      floor: listing.floor,
                      total: listing.totalFloors,
                    }),
                  )
                : key === 'address'
                  ? props.listings.map((listing) => listing.address)
                  : props.listings.map((listing) => t(`nav.${listing.dealType}`))

    return {
      key,
      label: t(`collections.compare.${key}`),
      values,
      bestIndexes,
    }
  })
})

const bestPriceIndexes = computed(() => findBestComparisonIndexes(props.listings, 'price'))

function openListing(listing: ListingDto) {
  navigateTo(listingPath(listing.id))
}
</script>

<template>
  <div
    class="comparison-table"
    :class="{ 'comparison-table--compact': compact }"
    :style="{ '--comparison-cols': columnCount }"
  >
    <div class="comparison-table__scroll">
      <div class="comparison-table__grid">
        <div class="comparison-table__label-spacer" aria-hidden="true" />
        <article
          v-for="(listing, listingIndex) in listings"
          :key="listing.id"
          class="comparison-table__card"
          :class="{ 'comparison-table__card--best': bestPriceIndexes.has(listingIndex) }"
        >
          <button
            type="button"
            class="comparison-table__remove"
            :aria-label="t('collections.remove')"
            @click="emit('remove', listing.id)"
          >
            <span aria-hidden="true">×</span>
          </button>
          <button type="button" class="comparison-table__image-btn" @click="openListing(listing)">
            <img
              v-if="listing.images[0]"
              :src="listing.images[0]"
              :alt="listing.address"
              class="comparison-table__image"
            />
            <div v-else class="comparison-table__image comparison-table__image--empty" />
          </button>
          <button type="button" class="comparison-table__price" @click="openListing(listing)">
            <CurrencyAmount :amount-usd="listing.price" />
          </button>
          <ListingVerifiedBadge v-if="listing.verified" compact class="comparison-table__verified" />
          <p class="comparison-table__address">{{ listing.address }}</p>
        </article>

        <template v-for="(row, rowIndex) in rows" :key="row.key">
          <div
            class="comparison-table__label"
            :class="{ 'comparison-table__label--alt': rowIndex % 2 === 1 }"
          >
            {{ row.label }}
          </div>
          <div
            v-for="(value, index) in row.values"
            :key="`${row.key}-${index}`"
            class="comparison-table__value"
            :class="{
              'comparison-table__value--alt': rowIndex % 2 === 1,
              'comparison-table__value--best': row.bestIndexes.has(index),
            }"
          >
            <CurrencyText
              v-if="row.key === 'price' || row.key === 'pricePerSqm'"
              :text="value"
            />
            <template v-else>{{ value }}</template>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.comparison-table {
  --comparison-label-width: 120px;
  --comparison-col-width: 200px;
  --comparison-gap: 12px;
  width: 100%;
  min-width: 0;
}

.comparison-table__scroll {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.comparison-table__grid {
  display: grid;
  grid-template-columns: var(--comparison-label-width) repeat(var(--comparison-cols), var(--comparison-col-width));
  gap: var(--comparison-gap);
  align-items: stretch;
  width: max-content;
}

.comparison-table__label-spacer {
  min-height: 1px;
}

.comparison-table__card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  min-width: 0;
  padding: 12px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-catalog-card-radius, 20px);
  background: var(--figma-surface);
  text-align: center;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.comparison-table__card--best {
  border-color: color-mix(in srgb, #04832a 45%, var(--figma-border));
  box-shadow: 0 0 0 1px color-mix(in srgb, #04832a 20%, transparent);
}

.comparison-table__remove {
  position: absolute;
  top: 18px;
  right: 18px;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 22px;
  font-weight: 400;
  line-height: 1;
  cursor: pointer;
  transition:
    color 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease;
}

.comparison-table__remove:hover {
  color: var(--figma-accent);
  border-color: color-mix(in srgb, var(--figma-accent) 35%, transparent);
  background: color-mix(in srgb, var(--figma-accent) 8%, var(--figma-mix-base));
}

.comparison-table__image-btn,
.comparison-table__price {
  border: none;
  background: transparent;
  padding: 0;
  cursor: pointer;
  text-align: center;
}

.comparison-table__image-btn {
  width: 100%;
}

.comparison-table__image {
  width: 100%;
  height: 140px;
  border-radius: var(--figma-catalog-image-radius, 15px);
  object-fit: cover;
  display: block;
}

.comparison-table__image--empty {
  background: var(--figma-page-bg);
}

.comparison-table__price {
  font-size: 18px;
  font-weight: 600;
  color: var(--figma-ink);
  line-height: 1.2;
}

.comparison-table__card--best .comparison-table__price {
  color: #04832a;
}

.comparison-table__address {
  margin: 0;
  font-size: 13px;
  line-height: 1.35;
  color: var(--figma-text-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-align: center;
}

.comparison-table__verified {
  margin-top: 6px;
  align-self: center;
}

.comparison-table__label,
.comparison-table__value {
  display: flex;
  align-items: center;
  min-height: 48px;
  padding: 12px;
  font-size: 14px;
  line-height: 1.35;
  border-radius: 10px;
  background: var(--figma-surface);
}

.comparison-table__label {
  justify-content: flex-start;
  font-size: 13px;
  font-weight: 600;
  color: var(--figma-text-muted);
  text-align: left;
}

.comparison-table__value {
  justify-content: center;
  font-weight: 500;
  color: var(--figma-ink);
  word-break: break-word;
  text-align: center;
}

.comparison-table__label--alt,
.comparison-table__value--alt {
  background: color-mix(in srgb, var(--figma-page-bg) 70%, var(--figma-mix-base));
}

.comparison-table__value--best {
  color: #04832a;
  font-weight: 700;
  background: color-mix(in srgb, #d8fae3 70%, var(--figma-mix-base));
}

.comparison-table__value--alt.comparison-table__value--best {
  background: color-mix(in srgb, #d8fae3 85%, var(--figma-mix-base));
}

.comparison-table--compact {
  --comparison-col-width: 180px;
  --comparison-gap: 10px;
}

.comparison-table--compact .comparison-table__card {
  padding: 10px;
  gap: 8px;
  border-radius: var(--figma-radius-chip);
}

.comparison-table--compact .comparison-table__image {
  height: 100px;
}

.comparison-table--compact .comparison-table__price {
  font-size: 16px;
}

.comparison-table--compact .comparison-table__label,
.comparison-table--compact .comparison-table__value {
  min-height: 42px;
  padding: 10px;
  font-size: 13px;
}

@media (min-width: 768px) {
  .comparison-table {
    --comparison-label-width: 140px;
    --comparison-col-width: 220px;
  }

  .comparison-table__image {
    height: 160px;
  }

  .comparison-table__price {
    font-size: 20px;
  }
}

@media (min-width: 1280px) {
  .comparison-table {
    --comparison-label-width: 160px;
    --comparison-col-width: 240px;
    --comparison-gap: 16px;
  }

  .comparison-table__card {
    padding: 16px;
  }

  .comparison-table__image {
    height: 180px;
  }
}

@media (max-width: 767px) {
  .comparison-table {
    --comparison-label-width: 108px;
    --comparison-col-width: 180px;
    --comparison-gap: 10px;
  }

  .comparison-table__label-spacer {
    display: none;
  }

  .comparison-table__grid {
    grid-template-columns: repeat(var(--comparison-cols), var(--comparison-col-width));
  }

  .comparison-table__label {
    grid-column: 1 / -1;
    justify-content: center;
    min-height: auto;
    padding-bottom: 4px;
    background: transparent;
    text-align: center;
  }

  .comparison-table__label--alt {
    background: transparent;
  }

  .comparison-table__remove {
    top: 10px;
    right: 10px;
    width: var(--touch-target-min, 44px);
    height: var(--touch-target-min, 44px);
  }
}
</style>
