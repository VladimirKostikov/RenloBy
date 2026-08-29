<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import MetroIcon from '@/components/MetroIcon.vue'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import ListingVerifiedBadge from '@/components/ListingVerifiedBadge.vue'
import ListingShareModal from '@/components/ListingShareModal.vue'
import ListingReportModal from '@/components/ListingReportModal.vue'
import ThemeIcon from '@/components/ThemeIcon.vue'
import { usePublishedAgo } from '@/composables/usePublishedAgo'
import { listingPath } from '@/lib/fullPageNav'
import { formatFloorShort } from '@/lib/listingOptionalFields'
import { formatListingOfferType } from '@/lib/listingOfferType'
import { formatListingRoomsShort } from '@/lib/listingRooms'
import { useComparisonsStore } from '@/stores/comparisons'
import type { ListingDto, MetroStationDto } from '@/types'

const props = defineProps<{
  listing: ListingDto
  metroStation?: MetroStationDto
  districtName?: string
  active?: boolean
  favorited?: boolean
  aiRecommended?: boolean
}>()

const emit = defineEmits<{
  favorite: [id: number]
}>()

const { t } = useI18n()
const comparisons = useComparisonsStore()

const imageUrl = computed(() => props.listing.images[0] ?? '')
const publishedLabel = usePublishedAgo(() => props.listing.publishedAt)
const offerTypeLabel = computed(() => formatListingOfferType(props.listing, t))
const roomsLabel = computed(() => formatListingRoomsShort(props.listing.rooms, t))
const areaLabel = computed(() => t('listing.areaShort', { n: props.listing.area }))
const floorLabel = computed(() =>
  formatFloorShort(props.listing.floor, props.listing.totalFloors, t('listing.notSpecified')),
)
const compared = computed(() => comparisons.isCompared(props.listing.id))
const shareUrl = computed(() => {
  const path = listingPath(props.listing.id)
  if (typeof window === 'undefined') {
    return path
  }
  return `${window.location.origin}${path}`
})

const menuOpen = ref(false)
const shareOpen = ref(false)
const reportOpen = ref(false)
const menuBtn = ref<HTMLElement | null>(null)
const menuPanel = ref<HTMLElement | null>(null)
const menuStyle = ref<{ top: string; left: string } | null>(null)

function updateMenuPosition() {
  const el = menuBtn.value
  if (!el) {
    return
  }

  const rect = el.getBoundingClientRect()
  const menuWidth = 180
  const left = Math.min(
    Math.max(8, rect.right - menuWidth),
    window.innerWidth - menuWidth - 8,
  )

  menuStyle.value = {
    top: `${rect.bottom + 4}px`,
    left: `${left}px`,
  }
}

function closeMenu() {
  menuOpen.value = false
  menuStyle.value = null
}

async function toggleMenu() {
  if (menuOpen.value) {
    closeMenu()
    return
  }

  menuOpen.value = true
  await nextTick()
  updateMenuPosition()
}

function openShare() {
  closeMenu()
  shareOpen.value = true
}

function openReport() {
  closeMenu()
  reportOpen.value = true
}

function toggleCompare() {
  closeMenu()
  void comparisons.toggle(props.listing.id, props.listing)
}

function onDocumentClick(event: MouseEvent) {
  if (!menuOpen.value) {
    return
  }

  const target = event.target
  if (!(target instanceof Node)) {
    return
  }

  if (menuBtn.value?.contains(target) || menuPanel.value?.contains(target)) {
    return
  }

  closeMenu()
}

function onDocumentKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeMenu()
  }
}

function onViewportChange() {
  if (menuOpen.value) {
    updateMenuPosition()
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onDocumentKeydown)
  window.addEventListener('resize', onViewportChange)
  window.addEventListener('scroll', onViewportChange, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
  window.removeEventListener('resize', onViewportChange)
  window.removeEventListener('scroll', onViewportChange, true)
})
</script>

<template>
  <article class="listing-card" :class="{ 'listing-card--active': active }">
    <div class="listing-card__media">
      <div class="listing-card__image-wrap">
        <img v-if="imageUrl" :src="imageUrl" :alt="listing.address" class="listing-card__image" />
        <div v-else class="listing-card__image listing-card__image--placeholder" />
        <div class="listing-card__media-actions">
          <a
            class="listing-card__page-link"
            :href="listingPath(listing.id)"
            :aria-label="t('catalog.openPage')"
            :title="t('catalog.openPage')"
            @click.stop
          >
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
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
            class="listing-card__favorite"
            :class="{ 'listing-card__favorite--active': favorited }"
            :aria-pressed="favorited"
            @click.stop="emit('favorite', listing.id)"
          >
            <img
              v-if="favorited"
              src="/figma/favorite-card-active.svg"
              alt=""
              width="32"
              height="32"
            />
            <span v-else class="listing-card__favorite-face" aria-hidden="true">
              <ThemeIcon src="/figma/heart.svg" :width="17" :height="14" />
            </span>
          </button>
        </div>
      </div>

      <div class="listing-card__params">
        <span class="listing-card__param">{{ roomsLabel }}</span>
        <span class="listing-card__dot" />
        <span class="listing-card__param">{{ areaLabel }}</span>
        <span class="listing-card__dot" />
        <span class="listing-card__param">{{ floorLabel }}</span>
      </div>

      <CurrencyAmount
        class="listing-card__sqm"
        :amount-usd="listing.pricePerSqm"
        variant="perSqm"
      />
    </div>

    <div class="listing-card__content">
      <div class="listing-card__top">
        <div class="listing-card__price-row">
          <CurrencyAmount class="listing-card__price" :amount-usd="listing.price" />
        </div>
        <div class="listing-card__menu-wrap">
          <button
            ref="menuBtn"
            type="button"
            class="listing-card__menu"
            :aria-label="t('listing.moreActions')"
            :aria-expanded="menuOpen"
            aria-haspopup="menu"
            @click.stop="toggleMenu"
          >
            <img data-theme-ink src="/figma/menu-dots.svg" alt="" width="24" height="24" />
          </button>
        </div>
      </div>

      <span class="listing-card__offer-type">{{ offerTypeLabel }}</span>

      <p class="listing-card__address">{{ listing.address }}</p>
      <p v-if="districtName" class="listing-card__district">{{ districtName }}</p>

      <div v-if="metroStation" class="listing-card__metro">
        <MetroIcon :color="metroStation.lineColor" />
        <span class="listing-card__metro-name">{{ metroStation.name }}</span>
        <span v-if="listing.metroMinutes" class="listing-card__dot" />
        <span v-if="listing.metroMinutes">{{ listing.metroMinutes }} мин.</span>
      </div>

      <div class="listing-card__badges">
        <ListingVerifiedBadge v-if="listing.verified" />
        <span v-if="aiRecommended" class="listing-card__badge listing-card__badge--recommend">
          <img src="/figma/ai-sparkle.svg" alt="" width="11" height="12" />
          {{ t('aiAssistant.recommendedBadge') }}
        </span>
        <span v-else-if="listing.aiGoodPrice" class="listing-card__badge listing-card__badge--ai">
          <img src="/figma/ai-star.svg" alt="" width="11" height="12" />
          {{ t('listing.aiGoodPrice') }}
        </span>
      </div>

      <div class="listing-card__footer">
        <span>{{ publishedLabel }}</span>
        <span class="listing-card__views">
          <img data-theme-ink src="/figma/views.svg" alt="" width="14" height="10" />
          {{ listing.views }}
        </span>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="menuOpen"
        ref="menuPanel"
        class="listing-card__menu-panel"
        role="menu"
        :style="menuStyle ?? undefined"
        @click.stop
      >
        <button type="button" class="listing-card__menu-item" role="menuitem" @click="openShare">
          {{ t('listingDetail.share') }}
        </button>
        <button type="button" class="listing-card__menu-item" role="menuitem" @click="toggleCompare">
          {{ compared ? t('catalog.inCompare') : t('catalog.addToCompare') }}
        </button>
        <button
          type="button"
          class="listing-card__menu-item listing-card__menu-item--danger"
          role="menuitem"
          @click="openReport"
        >
          {{ t('listingDetail.report') }}
        </button>
      </div>
    </Teleport>

    <ListingShareModal
      :open="shareOpen"
      :url="shareUrl"
      :title="listing.address"
      @close="shareOpen = false"
    />
    <ListingReportModal
      :open="reportOpen"
      :listing-id="listing.id"
      @close="reportOpen = false"
    />
  </article>
</template>

<style scoped>
.listing-card {
  display: grid;
  grid-template-columns: minmax(0, 200px) minmax(0, 1fr);
  gap: 12px;
  padding: 12px 0 24px;
  border-bottom: 1px solid var(--figma-border);
}

.listing-card--active .listing-card__price {
  color: var(--figma-accent);
}

.listing-card__media {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  max-width: 200px;
  min-width: 0;
}

.listing-card__image-wrap {
  position: relative;
  width: 100%;
  height: 140px;
}

.listing-card__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 10px;
}

.listing-card__image--placeholder {
  background: var(--figma-placeholder);
}

.listing-card__media-actions {
  position: absolute;
  top: 5px;
  right: 5px;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 6px;
}

.listing-card__page-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  text-decoration: none;
}

.listing-card__favorite {
  position: static;
  top: auto;
  right: auto;
  border: none;
  background: transparent;
  padding: 0;
  cursor: pointer;
}

.listing-card__favorite-face {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  border: 1px solid var(--figma-border);
  background: var(--figma-surface-glass);
  color: var(--figma-gray-mid);
}

.listing-card__params {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: nowrap;
  gap: 6px;
  min-width: 0;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-card__param {
  flex-shrink: 0;
  white-space: nowrap;
}

.listing-card__sqm {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  align-self: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 400;
  line-height: 1.2;
  color: var(--figma-text-muted);
  text-align: center;
}

.listing-card__sqm :deep(.currency-amount__primary),
.listing-card__sqm :deep(.currency-amount__secondary) {
  display: inline;
  color: var(--figma-text-muted);
  font-size: 12px;
  font-weight: 400;
  line-height: 1.2;
  max-width: none;
  overflow: visible;
  text-overflow: unset;
  white-space: nowrap;
}

.listing-card__sqm :deep(.currency-amount__secondary)::before {
  content: '·';
  margin-right: 6px;
  color: var(--figma-text-muted);
}

.listing-card__dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #c4c4c4;
  flex-shrink: 0;
}

.listing-card__content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
  padding-top: 2px;
}

.listing-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
}

.listing-card__menu-wrap {
  position: relative;
  flex-shrink: 0;
}

.listing-card__menu {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: transparent;
  padding: 0;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.listing-card__menu:hover,
.listing-card__menu[aria-expanded='true'] {
  background: rgba(0, 0, 0, 0.05);
}

.listing-card__price-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  min-width: 0;
}

.listing-card__price {
  flex: 0 1 auto;
  min-width: 0;
  max-width: 100%;
  font-size: 20px;
  font-weight: 600;
  line-height: 1;
  color: var(--figma-ink);
  white-space: nowrap;
}

.listing-card__offer-type {
  display: block;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-ink);
}

.listing-card__address {
  margin: 0;
  font-size: 12px;
  font-weight: 400;
  color: var(--figma-ink);
}

.listing-card__district {
  margin: 0;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-card__metro {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.listing-card__metro-name {
  font-weight: 400;
  color: var(--figma-ink);
}

.listing-card__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 4px;
}

.listing-card__badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 20px;
  padding: 4px 8px;
  border-radius: 9px;
  font-size: 10px;
  font-weight: 600;
}

.listing-card__badge--ai {
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.listing-card__badge--ai img {
  filter: brightness(0) invert(1);
}

.listing-card__badge--recommend {
  background: color-mix(in srgb, var(--figma-accent) 16%, var(--figma-mix-base));
  color: var(--figma-accent);
}

.listing-card__footer {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 2px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-card__views {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

@media (max-width: 767px) {
  .listing-card {
    grid-template-columns: 1fr;
    gap: 12px;
    margin: 0 0 12px;
    padding: 12px;
    border: 1px solid var(--figma-border);
    border-radius: 16px;
    background: var(--figma-surface);
    box-shadow: 0 2px 10px rgba(17, 24, 39, 0.04);
  }

  .listing-card__media {
    max-width: none;
    width: 100%;
    gap: 10px;
  }

  .listing-card__image-wrap {
    max-width: none;
    width: 100%;
    height: min(46vw, 200px);
  }

  .listing-card__image {
    border-radius: 12px;
  }

  .listing-card__media-actions {
    top: 8px;
    right: 8px;
  }

  .listing-card__page-link,
  .listing-card__favorite-face {
    width: 36px;
    height: 36px;
    border-radius: 50%;
  }

  .listing-card__params {
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 13px;
  }

  .listing-card__content {
    gap: 8px;
    padding-top: 0;
  }

  .listing-card__price-row {
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 4px 10px;
  }

  .listing-card__price {
    font-size: 22px;
    line-height: 1.15;
    white-space: normal;
  }

  .listing-card__sqm {
    font-size: 12px;
  }

  .listing-card__offer-type {
    font-size: 15px;
  }

  .listing-card__address {
    font-size: 15px;
  }

  .listing-card__footer {
    margin-top: 4px;
    padding-top: 8px;
    border-top: 1px solid var(--figma-border);
  }
}
</style>

<style>
.listing-card__menu-panel {
  position: fixed;
  z-index: 1200;
  display: flex;
  flex-direction: column;
  min-width: 180px;
  padding: 6px;
  border: 1px solid var(--figma-border, #e6e6e6);
  border-radius: 12px;
  background: var(--figma-surface);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
}

.listing-card__menu-item {
  display: flex;
  align-items: center;
  width: 100%;
  min-height: 40px;
  padding: 8px 12px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--figma-ink);
  font-size: 14px;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.listing-card__menu-item:hover {
  background: rgba(0, 0, 0, 0.04);
}

.listing-card__menu-item--danger {
  color: var(--figma-accent, #e14554);
}
</style>
