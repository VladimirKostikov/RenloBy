<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ListingImageSlider from '@/components/ListingImageSlider.vue'
import MetroIcon from '@/components/MetroIcon.vue'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import ListingVerifiedBadge from '@/components/ListingVerifiedBadge.vue'
import { formatPublishedAgo } from '@/lib/formatPublishedAgo'
import { listingPath } from '@/lib/fullPageNav'
import { listingDealTypeKey, listingPropertyTypeKey } from '@/lib/listingOfferType'
import type { ListingDto, MetroStationDto } from '@/types'

const props = defineProps<{
  listing: ListingDto
  metroStation?: MetroStationDto
  districtName?: string
  featured?: boolean
  favorited?: boolean
  compared?: boolean
  compact?: boolean
}>()

const emit = defineEmits<{
  open: [id: number]
  favorite: [id: number]
  compare: [id: number]
}>()

const { t } = useI18n()

const publishedLabel = computed(() => formatPublishedAgo(props.listing.publishedAt))
const showTopBadge = computed(() => props.featured === true)
const dealTypeLabel = computed(() => t(listingDealTypeKey(props.listing.dealType)))
const propertyTypeLabel = computed(() => t(listingPropertyTypeKey(props.listing.listingType)))
const pageHref = computed(() => listingPath(props.listing.id))

function openListing() {
  emit('open', props.listing.id)
}
</script>

<template>
  <article
    class="catalog-card"
    :class="{ 'catalog-card--compact': compact }"
    role="button"
    tabindex="0"
    @click="openListing"
    @keydown.enter.prevent="openListing"
    @keydown.space.prevent="openListing"
  >
    <div class="catalog-card__image-wrap">
      <ListingImageSlider
        class="catalog-card__slider"
        :images="listing.images"
        :alt="listing.address"
        :reset-key="listing.id"
        :compact="compact"
      />
      <div class="catalog-card__badges">
        <span v-if="showTopBadge" class="catalog-card__top">{{ t('catalog.top') }}</span>
        <span class="catalog-card__offer-type">{{ dealTypeLabel }}</span>
        <span class="catalog-card__offer-type">{{ propertyTypeLabel }}</span>
        <ListingVerifiedBadge v-if="listing.verified" />
      </div>
      <div class="catalog-card__image-overlay">
        <div class="catalog-card__overlay-actions">
          <a
            class="catalog-card__page-link"
            :href="pageHref"
            :aria-label="t('catalog.openPage')"
            :title="t('catalog.openPage')"
            @click.stop
          >
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
              <path
                d="M6.5 3.5H3.5C2.94772 3.5 2.5 3.94772 2.5 4.5V12.5C2.5 13.0523 2.94772 13.5 3.5 13.5H11.5C12.0523 13.5 12.5 13.0523 12.5 12.5V9.5"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M9.5 2.5H13.5V6.5"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M7.5 8.5L13.25 2.75"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </a>
          <button
            type="button"
            class="catalog-card__compare-icon"
            :class="{ 'catalog-card__compare-icon--active': compared }"
            :aria-label="compared ? t('catalog.inCompare') : t('catalog.addToCompare')"
            :aria-pressed="compared"
            @click.stop="emit('compare', listing.id)"
          >
            <img data-theme-ink src="/figma/compare.svg" alt="" width="17" height="17" draggable="false" />
          </button>
          <button
            type="button"
            class="catalog-card__favorite"
            :class="{ 'catalog-card__favorite--active': favorited }"
            :aria-label="t('listingDetail.favorite')"
            :aria-pressed="favorited"
            @click.stop="emit('favorite', listing.id)"
          >
            <svg width="18" height="16" viewBox="0 0 18.5 15.5" fill="none" aria-hidden="true">
              <path
                d="M0.75 5.02729C0.75 8.60212 5.5015 12.5739 7.8985 14.3115C8.706 14.8962 9.794 14.8962 10.6015 14.3115C12.9985 12.5739 17.75 8.6013 17.75 5.02729C17.75 2.66547 15.9608 0.75 13.5 0.75C12.225 0.75 10.95 1.16175 9.25 2.80876C7.55 1.16175 6.275 0.75 5 0.75C2.53925 0.75 0.75 2.66547 0.75 5.02729Z"
                :fill="favorited ? 'var(--figma-accent)' : 'none'"
                :stroke="favorited ? 'var(--figma-accent)' : '#848484'"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div class="catalog-card__body">
      <div class="catalog-card__price-row">
          <CurrencyAmount class="catalog-card__price" :amount-usd="listing.price" variant="detailed" />
          <CurrencyAmount class="catalog-card__sqm" :amount-usd="listing.pricePerSqm" variant="perSqm" />
      </div>

      <div class="catalog-card__specs">
        <span>{{ t('listing.roomsShort', { n: listing.rooms }) }}</span>
        <span class="catalog-card__dot" />
        <span>{{ t('listing.areaShort', { n: listing.area }) }}</span>
        <span class="catalog-card__dot" />
        <span>{{ t('listing.floorShort', { floor: listing.floor, total: listing.totalFloors }) }}</span>
      </div>

      <div class="catalog-card__location">
        <p class="catalog-card__address">{{ listing.address }}</p>
        <p v-if="districtName" class="catalog-card__district">{{ districtName }}</p>

        <div v-if="metroStation" class="catalog-card__metro">
          <MetroIcon :color="metroStation.lineColor" />
          <span class="catalog-card__metro-name">{{ metroStation.name }}</span>
          <template v-if="listing.metroMinutes">
            <span class="catalog-card__dot" />
            <span class="catalog-card__metro-time">{{ listing.metroMinutes }} {{ t('catalog.minutesShort') }}</span>
          </template>
        </div>
      </div>

      <div class="catalog-card__footer">
        <div class="catalog-card__footer-actions">
          <button type="button" class="catalog-card__cta" @click.stop="openListing">
            {{ t('catalog.learnMore') }}
          </button>
          <button
            type="button"
            class="catalog-card__compare-btn"
            :class="{ 'catalog-card__compare-btn--active': compared }"
            :aria-pressed="compared"
            @click.stop="emit('compare', listing.id)"
          >
            {{ compared ? t('catalog.inCompare') : t('catalog.addToCompare') }}
          </button>
        </div>
        <span v-if="publishedLabel" class="catalog-card__published">{{ publishedLabel }}</span>
      </div>
    </div>
  </article>
</template>

<style scoped>
.catalog-card {
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  align-self: stretch;
  width: 100%;
  min-width: 0;
  min-height: 100%;
  height: 100%;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-catalog-card-radius);
  background: var(--figma-surface);
  overflow: hidden;
  cursor: pointer;
  transition:
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.catalog-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

.catalog-card__image-wrap {
  position: relative;
  margin: var(--figma-catalog-card-padding);
  margin-bottom: 0;
  height: var(--figma-catalog-image-height);
  border-radius: var(--figma-catalog-image-radius);
  overflow: hidden;
}

.catalog-card__slider {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.catalog-card__image-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  pointer-events: none;
}


.catalog-card__badges {
  position: absolute;
  top: 12px;
  left: 15px;
  right: 156px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  pointer-events: none;
  z-index: 2;
}

.catalog-card__offer-type {
  pointer-events: none;
  display: inline-flex;
  align-items: center;
  height: 30px;
  padding: 0 12px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
  box-shadow: 0 1px 2px var(--color-shadow);
}
.catalog-card__top {
  position: absolute;
  top: 12px;
  left: 15px;
  pointer-events: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 30px;
  padding: 0 14px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  line-height: 1;
}

.catalog-card__overlay-actions {
  position: absolute;
  top: 12px;
  right: 12px;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 8px;
  pointer-events: auto;
  z-index: 3;
}

.catalog-card__compare-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  padding: 0;
  cursor: pointer;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease;
}

.catalog-card__page-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  text-decoration: none;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease;
}

.catalog-card__page-link:hover {
  transform: scale(1.05);
}

.catalog-card__compare-icon:hover {
  transform: scale(1.05);
}

.catalog-card__compare-icon--active {
  border-color: var(--figma-accent);
  background: var(--figma-surface);
}

.catalog-card__favorite {
  position: static;
  top: auto;
  right: auto;
  pointer-events: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  padding: 0;
  cursor: pointer;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    background-color 0.2s ease;
}

.catalog-card__favorite:hover {
  transform: scale(1.05);
}

.catalog-card__favorite--active {
  border-color: var(--figma-accent);
  background: var(--figma-surface);
}

.catalog-card__body {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
  gap: 8px;
  padding: 16px var(--figma-catalog-card-padding) var(--figma-catalog-card-padding);
}

.catalog-card__price-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: end;
  column-gap: 12px;
  flex-shrink: 0;
  min-width: 0;
  max-width: 100%;
  min-height: 64px;
}

.catalog-card__price {
  min-width: 0;
  max-width: 100%;
  font-size: 24px;
  font-weight: 600;
  line-height: 1;
  color: var(--figma-ink);
}

.catalog-card__sqm {
  justify-self: end;
  min-width: 0;
  max-width: 100%;
  font-size: 14px;
  color: var(--figma-text-muted);
  text-align: right;
}

.catalog-card__specs {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 8px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

@media (max-width: 1279px) {
  .catalog-card__specs {
    flex-wrap: wrap;
  }
}

.catalog-card__dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #c4c4c4;
  flex-shrink: 0;
}

.catalog-card__address {
  margin: 0;
  font-size: 16px;
  font-weight: 400;
  color: var(--figma-ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.catalog-card__district {
  margin: 0;
  font-size: 16px;
  color: var(--figma-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.catalog-card__location {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  gap: 4px;
  min-width: 0;
  min-height: 72px;
  overflow: hidden;
}

.catalog-card__metro {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  font-size: 14px;
}

.catalog-card__metro-name {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--figma-ink);
}

.catalog-card__metro-time {
  flex-shrink: 0;
  color: var(--figma-text-muted);
  white-space: nowrap;
}

.catalog-card__footer {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  align-items: stretch;
  gap: 10px;
  margin-top: auto;
  padding-top: 8px;
}

.catalog-card__footer-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  min-width: 0;
}

.catalog-card__compare-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 40px;
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-btn);
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.catalog-card__compare-btn:hover {
  border-color: var(--figma-accent);
  color: var(--figma-accent);
}

.catalog-card__compare-btn--active {
  border-color: var(--figma-accent);
  color: var(--figma-accent);
}

.catalog-card__cta {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-width: 0;
  min-height: 40px;
  height: 40px;
  padding: 0 14px;
  border: none;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    transform 0.2s ease;
}

.catalog-card__cta:hover {
  background: var(--figma-accent-hover);
}

.catalog-card__cta:active {
  transform: scale(0.98);
}

.catalog-card__published {
  font-size: 12px;
  color: var(--figma-text-muted);
  white-space: nowrap;
  text-align: right;
}

@media (max-width: 767px) {
  .catalog-card__image-wrap {
    height: min(56vw, 300px);
  }

  .catalog-card__cta,
  .catalog-card__compare-btn {
    flex: 1 1 100%;
    width: 100%;
    min-height: 40px;
    height: 40px;
    padding: 0 12px;
    font-size: 14px;
  }

  .catalog-card__price {
    font-size: 20px;
  }
}

.catalog-card--compact .catalog-card__image-wrap {
  margin: 16px 16px 0;
  height: 200px;
}

.catalog-card--compact .catalog-card__body {
  gap: 6px;
  padding: 12px 16px 16px;
}

.catalog-card--compact .catalog-card__price {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow: hidden;
  font-size: 20px;
}

.catalog-card--compact .catalog-card__price-row {
  grid-template-columns: minmax(0, 1fr);
  align-items: stretch;
  gap: 2px;
  min-height: 0;
  overflow: hidden;
}

.catalog-card--compact .catalog-card__sqm {
  display: none;
}

.catalog-card--compact .catalog-card__price :deep(.currency-amount__secondary) {
  font-size: 11px;
}

.catalog-card--compact .catalog-card__address,
.catalog-card--compact .catalog-card__district {
  font-size: 14px;
}

.catalog-card--compact .catalog-card__location {
  min-height: 64px;
}

.catalog-card--compact .catalog-card__metro {
  font-size: 13px;
}

.catalog-card--compact .catalog-card__cta,
.catalog-card--compact .catalog-card__compare-btn {
  min-height: 38px;
  height: 38px;
  padding: 0 12px;
  font-size: 14px;
}

@media (max-width: 767px) {
  .catalog-card--compact .catalog-card__cta,
  .catalog-card--compact .catalog-card__compare-btn {
    min-height: 40px;
    height: 40px;
    padding: 0 12px;
    font-size: 14px;
  }
}

.catalog-card--compact .catalog-card__compare-icon,
.catalog-card--compact .catalog-card__favorite,
.catalog-card--compact .catalog-card__page-link {
  width: 36px;
  height: 36px;
}

.catalog-card--compact .catalog-card__favorite svg {
  width: 16px;
  height: 14px;
}
</style>
