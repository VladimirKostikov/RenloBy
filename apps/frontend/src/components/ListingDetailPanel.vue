<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ListingDetailMapPreview from '@/components/ListingDetailMapPreview.vue'
import ListingNearbyPanel from '@/components/ListingNearbyPanel.vue'
import ListingCharacteristicsModal from '@/components/ListingCharacteristicsModal.vue'
import ListingChatModal from '@/components/ListingChatModal.vue'
import ListingPhotoLightbox from '@/components/ListingPhotoLightbox.vue'
import ListingSellerCard from '@/components/ListingSellerCard.vue'
import ListingSellerModal from '@/components/ListingSellerModal.vue'
import ListingReportModal from '@/components/ListingReportModal.vue'
import ListingRequestModal from '@/components/ListingRequestModal.vue'
import ListingShareModal from '@/components/ListingShareModal.vue'
import CatalogGridCard from '@/components/CatalogGridCard.vue'
import SkeletonWave from '@/components/SkeletonWave.vue'
import MetroIcon from '@/components/MetroIcon.vue'
import CurrencyAmount from '@/components/CurrencyAmount.vue'
import { useListingImageSlider } from '@/composables/useListingImageSlider'
import {
  buildListingDetailExtras,
  findSimilarListings,
  resolveCharacteristicText,
} from '@/lib/listingDetailExtras'
import {
  fetchListingInfrastructureSummary,
  fetchListingNearbyPlaces,
  type ListingInfrastructureItem,
} from '@/lib/listingNearbyInfrastructure'
import { scrollListingDetailToTop } from '@/lib/scrollListingDetailToTop'
import { recordListingContactEvent } from '@/api/account'
import { useComparisonsStore } from '@/stores/comparisons'
import { useFavoritesStore } from '@/stores/favorites'
import { useListingsStore } from '@/stores/listings'
import type { InfrastructurePoi, InfrastructureType } from '@/types/infrastructure'
import type { ListingDto, MetroStationDto } from '@/types'

const SIMILAR_LISTINGS_LIMIT = 4

const props = withDefaults(
  defineProps<{
    listing: ListingDto
    metroStation?: MetroStationDto
    districtName?: string
    loading?: boolean
    asPage?: boolean
    showClose?: boolean
  }>(),
  {
    loading: false,
    asPage: false,
    showClose: true,
  },
)

const emit = defineEmits<{
  close: []
  showOnMap: []
}>()

const { t } = useI18n()
const listingsStore = useListingsStore()
const favorites = useFavoritesStore()
const comparisons = useComparisonsStore()

const descriptionExpanded = ref(false)
const characteristicsOpen = ref(false)
const nearbyOpen = ref(false)
const shareOpen = ref(false)
const chatOpen = ref(false)
const leaveRequestOpen = ref(false)
const reportOpen = ref(false)
const sellerOpen = ref(false)
const photoLightboxOpen = ref(false)
const galleryHovering = ref(false)
const favoritePulse = ref(false)
const comparePulse = ref(false)
const blocksReady = ref(false)
const nearbyLoading = ref(false)
const infraLoading = ref(false)
const infrastructure = ref<ListingInfrastructureItem[]>([])
const nearbyPlaces = ref<InfrastructurePoi[]>([])
const rootEl = ref<HTMLElement | null>(null)
const galleryViewportRef = ref<HTMLElement | null>(null)
let infraAbortController: AbortController | null = null
let nearbyAbortController: AbortController | null = null

const infraFallbackNames = computed<Record<InfrastructureType, string>>(() => ({
  shop: t('map.infra.shop'),
  pharmacy: t('map.infra.pharmacy'),
  school: t('map.infra.school'),
  park: t('listingDetail.infraPark'),
}))

const images = computed(() => (props.listing.images.length > 0 ? props.listing.images : ['']))
const slideCount = computed(() => images.value.length)
const galleryPaused = computed(() => photoLightboxOpen.value || !galleryHovering.value)
const {
  slideIndex,
  slideTransitionEnabled,
  isDragging,
  hasMultipleSlides,
  goTo,
  showPrevSlide,
  showNextSlide,
  resetToFirst,
  enableTransitionNextTick,
  onPointerDown,
  onPointerMove,
  onPointerUp,
  consumeClickSuppressed,
  trackStyle: buildTrackStyle,
  restartAutoplay,
} = useListingImageSlider({
  slideCount,
  autoplay: ref(true),
  autoplayMs: ref(4500),
  paused: galleryPaused,
})
const slideCounter = computed(() =>
  images.value.length > 1 ? `${slideIndex.value + 1}/${images.value.length}` : '',
)
const thumbImages = computed(() => images.value.slice(0, 4))
const thumbsOverflow = computed(() => Math.max(0, images.value.length - thumbImages.value.length))
const galleryTrackStyle = computed(() => buildTrackStyle(galleryViewportRef.value?.clientWidth ?? 0))

const extras = computed(() => buildListingDetailExtras(props.listing))
const similarListings = computed(() =>
  findSimilarListings(
    props.listing,
    [...listingsStore.items, ...listingsStore.mapItems],
    SIMILAR_LISTINGS_LIMIT,
  ),
)
const visibleCharacteristics = computed(() => extras.value.characteristics.slice(0, 3))
const hasMoreCharacteristics = computed(
  () => extras.value.characteristics.length > visibleCharacteristics.value.length,
)
const shareUrl = computed(() => {
  if (typeof window === 'undefined') {
    return `/listings/${props.listing.id}`
  }
  return `${window.location.origin}/listings/${props.listing.id}`
})
const chatText = computed(() => `${props.listing.address} ${shareUrl.value}`)
const showSeller = computed(() => props.listing.seller != null)
const sellerPhone = computed(() => props.listing.seller?.phone ?? undefined)
const contactTrackedIds = new Set<number>()

async function trackContactOpen() {
  const id = props.listing.id
  if (contactTrackedIds.has(id)) {
    return
  }
  contactTrackedIds.add(id)
  try {
    await recordListingContactEvent(id, 'contact')
  } catch {
    contactTrackedIds.delete(id)
  }
}

function openSellerProfile() {
  sellerOpen.value = true
  void trackContactOpen()
}

function openChatModal() {
  chatOpen.value = true
  void trackContactOpen()
}

function openLeaveRequestModal() {
  leaveRequestOpen.value = true
}

watch(
  () => props.listing.id,
  async () => {
    resetToFirst()
    descriptionExpanded.value = false
    characteristicsOpen.value = false
    nearbyOpen.value = false
    shareOpen.value = false
    chatOpen.value = false
    leaveRequestOpen.value = false
    reportOpen.value = false
    sellerOpen.value = false
    photoLightboxOpen.value = false
    galleryHovering.value = false
    blocksReady.value = false
    enableTransitionNextTick(nextTick)
    void loadInfrastructure()
    window.setTimeout(() => {
      if (!props.loading) {
        blocksReady.value = true
      }
    }, 420)
  },
  { immediate: true },
)

watch(
  () => props.loading,
  (isLoading) => {
    if (!isLoading) {
      window.setTimeout(() => {
        blocksReady.value = true
      }, 280)
      return
    }
    blocksReady.value = false
  },
)

async function loadInfrastructure() {
  infraAbortController?.abort()
  infraAbortController = new AbortController()

  infraLoading.value = true
  try {
    infrastructure.value = await fetchListingInfrastructureSummary(
      props.listing,
      props.metroStation,
      infraFallbackNames.value,
      infraAbortController.signal,
    )
  } catch {
    infrastructure.value = props.metroStation
      ? [{ icon: 'metro', label: props.metroStation.name, minutes: props.listing.metroMinutes ?? 8 }]
      : []
  } finally {
    infraLoading.value = false
  }
}

async function openNearbyPanel() {
  nearbyOpen.value = true
  nearbyLoading.value = true
  nearbyAbortController?.abort()
  nearbyAbortController = new AbortController()

  try {
    nearbyPlaces.value = await fetchListingNearbyPlaces(
      props.listing,
      infraFallbackNames.value,
      nearbyAbortController.signal,
    )
  } catch {
    nearbyPlaces.value = []
  } finally {
    nearbyLoading.value = false
  }
}

function closeNearbyPanel() {
  nearbyOpen.value = false
  nearbyAbortController?.abort()
}

function onGalleryPrev() {
  showPrevSlide()
  restartAutoplay()
}

function onGalleryNext() {
  showNextSlide()
  restartAutoplay()
}

function selectSlide(index: number) {
  goTo(index)
  restartAutoplay()
}

function openPhotoLightbox() {
  if (consumeClickSuppressed() || !images.value.some((image) => Boolean(image))) {
    return
  }
  photoLightboxOpen.value = true
}

function getSimilarMetroStation(listing: ListingDto) {
  if (!listing.metroStationId) {
    return undefined
  }
  return listingsStore.metroStations.find((station) => station.id === listing.metroStationId)
}

function getSimilarDistrictLabel(listing: ListingDto) {
  const district = listingsStore.districts.find((item) => item.id === listing.districtId)
  const city = listingsStore.cities.find((item) => item.id === listing.cityId)
  if (!district || !city) {
    return undefined
  }
  return `${district.name}, ${city.name}`
}

function openSimilarListing(id: number) {
  scrollListingDetailToTop(rootEl.value, { asPage: props.asPage })
  void listingsStore.openDetailListing(id)
}

function handleSimilarFavorite(id: number) {
  const listing = similarListings.value.find((item) => item.id === id)
  favorites.toggle(id, listing)
}

function handleSimilarCompare(id: number) {
  const listing = similarListings.value.find((item) => item.id === id)
  comparisons.toggle(id, listing)
}

function toggleFavorite() {
  favorites.toggle(props.listing.id, props.listing)
  favoritePulse.value = true
  window.setTimeout(() => {
    favoritePulse.value = false
  }, 420)
}

function toggleComparison() {
  comparisons.toggle(props.listing.id, props.listing)
  comparePulse.value = true
  window.setTimeout(() => {
    comparePulse.value = false
  }, 420)
}

function infraIcon(item: ListingInfrastructureItem): string | null {
  if (item.icon === 'metro') {
    return null
  }
  if (item.icon === 'school') {
    return '/figma/infra-school.svg'
  }
  if (item.icon === 'shop') {
    return '/figma/infra-shop.svg'
  }
  if (item.icon === 'park') {
    return '/figma/infra-park.svg'
  }
  return null
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    if (photoLightboxOpen.value) {
      photoLightboxOpen.value = false
      return
    }
    if (nearbyOpen.value) {
      closeNearbyPanel()
      return
    }
    if (characteristicsOpen.value) {
      characteristicsOpen.value = false
      return
    }
    if (shareOpen.value) {
      shareOpen.value = false
      return
    }
    if (chatOpen.value) {
      chatOpen.value = false
      return
    }
    if (leaveRequestOpen.value) {
      leaveRequestOpen.value = false
      return
    }
    if (reportOpen.value) {
      reportOpen.value = false
      return
    }
    emit('close')
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  if (!props.asPage) {
    document.body.style.overflow = 'hidden'
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  if (!props.asPage) {
    document.body.style.overflow = ''
  }
  infraAbortController?.abort()
  nearbyAbortController?.abort()
})
</script>

<template>
  <div
    ref="rootEl"
    class="listing-detail-modal"
    :class="{ 'listing-detail-modal--page': asPage }"
    :role="asPage ? undefined : 'dialog'"
    :aria-modal="asPage ? undefined : 'true'"
  >
    <button
      v-if="showClose"
      type="button"
      class="listing-detail-modal__close"
      :aria-label="t('listingDetail.close')"
      @click="emit('close')"
    >
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" />
      </svg>
    </button>

    <div class="listing-detail-modal__content">
      <div class="listing-detail-modal__top">
        <div class="listing-detail-modal__top-main">
          <div class="listing-detail-modal__badges">
            <span v-if="listing.verified" class="listing-detail-modal__badge listing-detail-modal__badge--verified">
              <img src="/figma/verified.svg" alt="" width="10" height="10" />
              {{ t('listing.verified') }}
            </span>
            <span v-if="listing.aiGoodPrice" class="listing-detail-modal__badge listing-detail-modal__badge--ai">
              <img src="/figma/ai-star.svg" alt="" width="11" height="12" />
              {{ t('listing.aiGoodPrice') }}
            </span>
          </div>

          <div class="listing-detail-modal__meta">
            <span>{{ t('listing.publishedAgo') }}</span>
            <span class="listing-detail-modal__views">
              <img data-theme-ink src="/figma/views.svg" alt="" width="14" height="10" />
              {{ listing.views }}
            </span>
          </div>
        </div>

        <div class="listing-detail-modal__actions">
              <button
                type="button"
                class="listing-detail-modal__icon-btn"
                :class="{
                  'listing-detail-modal__icon-btn--active': favorites.isFavorite(listing.id),
                  'listing-detail-modal__icon-btn--pulse': favoritePulse,
                }"
                :aria-label="t('listingDetail.favorite')"
                :aria-pressed="favorites.isFavorite(listing.id)"
                @click="toggleFavorite"
              >
                <svg width="24" height="22" viewBox="0 0 24 22" fill="none" aria-hidden="true">
                  <path
                    d="M2 7.28C2 11.35 7.43 15.87 10.17 17.85C11.09 18.51 12.91 18.51 13.83 17.85C16.57 15.87 22 11.35 22 7.28C22 4.59 19.95 2.5 17.14 2.5C15.69 2.5 14.23 2.97 12.29 4.84C10.35 2.97 8.89 2.5 7.43 2.5C4.62 2.5 2 4.59 2 7.28Z"
                    :fill="favorites.isFavorite(listing.id) ? 'var(--figma-accent)' : 'none'"
                    :stroke="favorites.isFavorite(listing.id) ? 'var(--figma-accent)' : '#848484'"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </button>
              <button
                type="button"
                class="listing-detail-modal__action-btn"
                :class="{
                  'listing-detail-modal__action-btn--active': comparisons.isCompared(listing.id),
                  'listing-detail-modal__action-btn--pulse': comparePulse,
                }"
                :aria-pressed="comparisons.isCompared(listing.id)"
                @click="toggleComparison"
              >
                <img data-theme-ink src="/figma/compare.svg" alt="" width="17" height="17" />
                {{ comparisons.isCompared(listing.id) ? t('catalog.inCompare') : t('catalog.addToCompare') }}
              </button>
              <button type="button" class="listing-detail-modal__action-btn" @click="shareOpen = true">
                <svg width="15" height="18" viewBox="0 0 15 18" fill="none" aria-hidden="true">
                  <path d="M7.5 0L14 5.25V16.5C14 17.05 13.55 17.5 13 17.5H2C1.45 17.5 1 17.05 1 16.5V5.25L7.5 0Z" stroke="currentColor" stroke-width="1.2" />
                </svg>
                {{ t('listingDetail.share') }}
              </button>
              <button
                type="button"
                class="listing-detail-modal__action-btn listing-detail-modal__action-btn--report"
                @click="reportOpen = true"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="12" cy="12" r="9" stroke="#E14554" stroke-width="1.5" />
                  <path d="M12 7v6M12 16v1" stroke="#E14554" stroke-width="1.5" stroke-linecap="round" />
                </svg>
                {{ t('listingDetail.report') }}
              </button>
        </div>
      </div>

      <div class="listing-detail-modal__hero">
            <div
              class="listing-detail-modal__gallery"
              @mouseenter="galleryHovering = true"
              @mouseleave="galleryHovering = false"
            >
              <div class="listing-detail-modal__gallery-main">
                <div
                  ref="galleryViewportRef"
                  class="listing-detail-modal__gallery-viewport"
                  :class="{ 'listing-detail-modal__gallery-viewport--dragging': isDragging }"
                  role="button"
                  tabindex="0"
                  :aria-label="t('listingDetail.expandPhoto')"
                  @click="openPhotoLightbox"
                  @keydown.enter.prevent="openPhotoLightbox"
                  @keydown.space.prevent="openPhotoLightbox"
                  @pointerdown="onPointerDown"
                  @pointermove="onPointerMove"
                  @pointerup="onPointerUp"
                  @pointercancel="onPointerUp"
                >
                  <div
                    class="listing-detail-modal__gallery-track"
                    :class="{ 'listing-detail-modal__gallery-track--animate': slideTransitionEnabled && !isDragging }"
                    :style="galleryTrackStyle"
                  >
                    <template v-for="(image, index) in images" :key="`${listing.id}-${index}`">
                      <img
                        v-if="image"
                        :src="image"
                        :alt="listing.address"
                        class="listing-detail-modal__gallery-image"
                        draggable="false"
                      />
                      <div
                        v-else
                        class="listing-detail-modal__gallery-image listing-detail-modal__gallery-image--empty"
                      />
                    </template>
                  </div>
                </div>

                <button
                  v-if="hasMultipleSlides"
                  type="button"
                  class="listing-detail-modal__gallery-nav listing-detail-modal__gallery-nav--prev"
                  :aria-label="t('map.card.prevPhoto')"
                  @click.stop="onGalleryPrev"
                >
                  ‹
                </button>
                <button
                  v-if="hasMultipleSlides"
                  type="button"
                  class="listing-detail-modal__gallery-nav listing-detail-modal__gallery-nav--next"
                  :aria-label="t('map.card.nextPhoto')"
                  @click.stop="onGalleryNext"
                >
                  ›
                </button>

                <div
                  v-if="hasMultipleSlides"
                  class="listing-detail-modal__gallery-dots"
                  role="tablist"
                  :aria-label="t('catalog.photoPagination')"
                >
                  <button
                    v-for="(_, index) in images"
                    :key="`dot-${listing.id}-${index}`"
                    type="button"
                    class="listing-detail-modal__gallery-dot"
                    :class="{ 'listing-detail-modal__gallery-dot--active': slideIndex === index }"
                    role="tab"
                    :aria-selected="slideIndex === index"
                    :aria-label="t('catalog.photoSlide', { n: index + 1 })"
                    @click.stop="selectSlide(index)"
                  />
                </div>

                <div v-if="slideCounter" class="listing-detail-modal__gallery-counter">{{ slideCounter }}</div>
              </div>

              <div v-if="hasMultipleSlides" class="listing-detail-modal__thumbs-row">
                <div class="listing-detail-modal__thumbs">
                  <button
                    v-for="(image, index) in thumbImages"
                    :key="`${listing.id}-${index}`"
                    type="button"
                    class="listing-detail-modal__thumb"
                    :class="{ 'listing-detail-modal__thumb--active': slideIndex === index }"
                    @click="selectSlide(index)"
                  >
                    <img v-if="image" :src="image" :alt="listing.address" />
                  </button>
                  <button
                    v-if="thumbsOverflow > 0"
                    type="button"
                    class="listing-detail-modal__thumb listing-detail-modal__thumb--more"
                    @click="selectSlide(thumbImages.length)"
                  >
                    {{ t('listingDetail.photosMore', { n: thumbsOverflow }) }}
                  </button>
                </div>
                <span class="listing-detail-modal__photos-count">
                  {{ t('listingDetail.photosCount', { n: images.length }) }}
                </span>
              </div>
            </div>

            <div class="listing-detail-modal__summary">
              <div class="listing-detail-modal__price-row">
                <CurrencyAmount class="listing-detail-modal__price" :amount-usd="listing.price" />
                <CurrencyAmount class="listing-detail-modal__sqm" :amount-usd="listing.pricePerSqm" variant="perSqm" />
                <span
                  v-if="listing.priceNegotiable"
                  class="listing-detail-modal__badge listing-detail-modal__badge--negotiable"
                >
                  {{ t('listing.priceNegotiable') }}
                </span>
              </div>

              <div class="listing-detail-modal__specs">
                <span>{{ t('listing.roomsShort', { n: listing.rooms }) }}</span>
                <span class="listing-detail-modal__dot" />
                <span>{{ t('listing.areaShort', { n: listing.area }) }}</span>
                <span class="listing-detail-modal__dot" />
                <span>{{ t('listing.floorShort', { floor: listing.floor, total: listing.totalFloors }) }}</span>
              </div>

              <p class="listing-detail-modal__address">{{ listing.address }}</p>
              <p v-if="districtName" class="listing-detail-modal__district">{{ districtName }}</p>

              <div v-if="metroStation" class="listing-detail-modal__metro">
                <MetroIcon :color="metroStation.lineColor" />
                <span>{{ metroStation.name }}</span>
                <span v-if="listing.metroMinutes" class="listing-detail-modal__dot" />
                <span v-if="listing.metroMinutes">{{ t('listingDetail.metroWalk', { n: listing.metroMinutes }) }}</span>
              </div>

              <ListingSellerCard
                v-if="showSeller && listing.seller"
                :seller="listing.seller"
                :from-owner="listing.fromOwner"
                @open="openSellerProfile"
                @contact="trackContactOpen"
              />

              <div class="listing-detail-modal__summary-footer">
                <div class="listing-detail-modal__cta-row">
                  <button
                    type="button"
                    class="listing-detail-modal__cta listing-detail-modal__cta--primary"
                    @click="openChatModal"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M4 5h16v10H7l-3 3V5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                    </svg>
                    {{ t('listingDetail.contactSeller') }}
                  </button>
                  <button
                    type="button"
                    class="listing-detail-modal__cta listing-detail-modal__cta--secondary"
                    @click="openLeaveRequestModal"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M4 4h16v16H4V4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                      <path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                    {{ t('listingDetail.leaveRequest') }}
                  </button>
                </div>

                <p class="listing-detail-modal__response">
                  <span class="listing-detail-modal__response-dot" />
                  {{ t('listingDetail.responseTime', { n: extras.responseMinutes }) }}
                </p>
              </div>
            </div>
          </div>

          <div class="listing-detail-modal__bento">
            <section class="listing-detail-modal__card listing-detail-modal__card--description">
              <h3 class="listing-detail-modal__card-title">{{ t('listingDetail.description') }}</h3>
              <template v-if="!blocksReady || loading">
                <SkeletonWave :lines="4" height="12px" />
              </template>
              <template v-else>
                <p
                  class="listing-detail-modal__description-text"
                  :class="{ 'listing-detail-modal__description-text--expanded': descriptionExpanded }"
                >
                  {{ extras.description }}
                </p>
                <button
                  type="button"
                  class="listing-detail-modal__link"
                  @click="descriptionExpanded = !descriptionExpanded"
                >
                  {{ descriptionExpanded ? t('listingDetail.showLess') : t('listingDetail.showMore') }}
                </button>
              </template>
            </section>

            <div class="listing-detail-modal__bento-main">
              <div class="listing-detail-modal__bento-grid">
                <section class="listing-detail-modal__card listing-detail-modal__card--chars">
                  <h3 class="listing-detail-modal__card-title">{{ t('listingDetail.characteristics') }}</h3>
                  <template v-if="!blocksReady || loading">
                    <div class="listing-detail-modal__chars-skeleton">
                      <SkeletonWave v-for="n in 6" :key="n" :lines="1" height="18px" />
                    </div>
                  </template>
                  <template v-else>
                    <dl class="listing-detail-modal__characteristics">
                      <div
                        v-for="row in visibleCharacteristics"
                        :key="row.label"
                        class="listing-detail-modal__characteristics-row"
                      >
                        <dt>{{ t(row.label) }}</dt>
                        <dd>{{ resolveCharacteristicText(row.value, t) }}</dd>
                      </div>
                    </dl>
                    <button
                      v-if="hasMoreCharacteristics"
                      type="button"
                      class="listing-detail-modal__link"
                      @click="characteristicsOpen = true"
                    >
                      {{ t('listingDetail.allCharacteristics') }}
                    </button>
                  </template>
                </section>

                <section class="listing-detail-modal__card listing-detail-modal__card--security">
                  <h3 class="listing-detail-modal__card-title">{{ t('listingDetail.security') }}</h3>
                  <template v-if="!blocksReady || loading">
                    <div class="listing-detail-modal__security-body">
                      <SkeletonWave :lines="4" height="14px" />
                    </div>
                  </template>
                  <template v-else>
                    <div class="listing-detail-modal__security-body">
                      <ul class="listing-detail-modal__security-list">
                        <li>{{ t('listingDetail.securityDocs') }}</li>
                        <li>{{ t('listingDetail.securityOwner') }}</li>
                        <li>{{ t('listingDetail.securityActive') }}</li>
                      </ul>
                      <p class="listing-detail-modal__link listing-detail-modal__link--static">
                        {{ t('listingDetail.securityChecked', { n: extras.securityCheckedDaysAgo }) }}
                      </p>
                    </div>
                    <div class="listing-detail-modal__shield" aria-hidden="true">
                      <svg width="62" height="78" viewBox="0 0 62 78" fill="none">
                        <path d="M31 4L56 16V38C56 55 31 74 31 74C31 74 6 55 6 38V16L31 4Z" fill="#E14554" />
                        <path d="M22 39L28 45L42 31" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                    </div>
                  </template>
                </section>

                <section class="listing-detail-modal__card listing-detail-modal__card--infra">
                  <h3 class="listing-detail-modal__card-title">{{ t('listingDetail.infrastructure') }}</h3>
                  <template v-if="!blocksReady || loading || infraLoading">
                    <div class="listing-detail-modal__infra-skeleton">
                      <SkeletonWave v-for="n in 4" :key="n" :lines="1" height="28px" />
                    </div>
                  </template>
                  <template v-else>
                    <ul class="listing-detail-modal__infra-list">
                      <li v-for="(item, index) in infrastructure" :key="`${item.icon}-${index}`">
                        <span class="listing-detail-modal__infra-icon">
                          <MetroIcon
                            v-if="item.icon === 'metro' && metroStation"
                            :color="metroStation.lineColor"
                          />
                          <img
                            v-else-if="infraIcon(item)"
                            :data-theme-ink="item.icon !== 'park' ? '' : undefined"
                            :src="infraIcon(item)!"
                            alt=""
                            width="17"
                            height="17"
                          />
                          <svg v-else width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 20h16M6 20V10l6-6 6 6v10" stroke="#E14554" stroke-width="1.5" stroke-linejoin="round" />
                          </svg>
                        </span>
                        <span class="listing-detail-modal__infra-label">{{ item.label }}</span>
                        <strong>{{ t('listingDetail.minutesShort', { n: item.minutes }) }}</strong>
                      </li>
                    </ul>
                    <button type="button" class="listing-detail-modal__link" @click="openNearbyPanel">
                      {{ t('listingDetail.allNearby') }}
                    </button>
                  </template>
                </section>

                <section class="listing-detail-modal__card listing-detail-modal__card--conditions">
                  <h3 class="listing-detail-modal__card-title">{{ t('listingDetail.conditions') }}</h3>
                  <template v-if="!blocksReady || loading">
                    <div class="listing-detail-modal__chars-skeleton">
                      <SkeletonWave v-for="n in 4" :key="n" :lines="1" height="18px" />
                    </div>
                  </template>
                  <dl v-else class="listing-detail-modal__characteristics">
                    <div
                      v-for="row in extras.conditions"
                      :key="row.label"
                      class="listing-detail-modal__characteristics-row"
                    >
                      <dt>{{ t(row.label) }}</dt>
                      <dd>{{ resolveCharacteristicText(row.value, t) }}</dd>
                    </div>
                  </dl>
                </section>
              </div>

              <section class="listing-detail-modal__card listing-detail-modal__card--map">
                <div v-if="!blocksReady || loading" class="listing-detail-modal__map-skeleton">
                  <SkeletonWave :lines="1" height="100%" />
                </div>
                <ListingDetailMapPreview
                  v-else
                  :latitude="listing.latitude"
                  :longitude="listing.longitude"
                  @show-on-map="emit('showOnMap')"
                />
              </section>
            </div>

            <section
              v-if="similarListings.length > 0 || !blocksReady || loading"
              class="listing-detail-modal__card listing-detail-modal__card--similar"
            >
              <h3 class="listing-detail-modal__card-title">{{ t('listingDetail.similar') }}</h3>

              <div v-if="!blocksReady || loading" class="listing-detail-modal__similar-skeleton">
                <SkeletonWave v-for="n in 4" :key="n" :lines="1" height="180px" />
              </div>
              <div v-else class="listing-detail-modal__similar-grid">
                <CatalogGridCard
                  v-for="item in similarListings"
                  :key="item.id"
                  :listing="item"
                  :metro-station="getSimilarMetroStation(item)"
                  :district-name="getSimilarDistrictLabel(item)"
                  :favorited="favorites.isFavorite(item.id)"
                  :compared="comparisons.isCompared(item.id)"
                  compact
                  @open="openSimilarListing"
                  @favorite="handleSimilarFavorite"
                  @compare="handleSimilarCompare"
                />
              </div>
            </section>
          </div>
    </div>

    <ListingNearbyPanel
      v-if="nearbyOpen"
      :listing="listing"
      :places="nearbyPlaces"
      @close="closeNearbyPanel"
    />

    <ListingCharacteristicsModal
      v-if="characteristicsOpen"
      :rows="extras.characteristics"
      @close="characteristicsOpen = false"
    />

    <ListingShareModal
      v-if="shareOpen"
      :url="shareUrl"
      :title="listing.address"
      @close="shareOpen = false"
    />
    <ListingChatModal
      :open="chatOpen"
      :text="chatText"
      :phone="sellerPhone"
      @close="chatOpen = false"
    />
    <ListingRequestModal
      :open="leaveRequestOpen"
      :listing-id="listing.id"
      @close="leaveRequestOpen = false"
    />
    <ListingPhotoLightbox
      v-if="photoLightboxOpen"
      :images="images"
      :alt="listing.address"
      :start-index="slideIndex"
      @close="photoLightboxOpen = false"
    />
    <ListingReportModal
      v-if="reportOpen"
      :listing-id="listing.id"
      @close="reportOpen = false"
    />
    <ListingSellerModal
      v-if="listing.seller"
      :open="sellerOpen"
      :seller-id="listing.seller.id"
      :from-owner="listing.fromOwner"
      :initial-seller="listing.seller"
      @close="sellerOpen = false"
      @open-listing="openSimilarListing"
    />
  </div>
</template>

<style scoped>
.listing-detail-modal {
  position: relative;
  width: min(980px, 100%);
  margin: auto;
  padding: 22px;
  background: var(--figma-surface);
  border-radius: var(--figma-radius-chip);
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.22);
  --listing-detail-section-gap: 18px;
}

.listing-detail-modal--page {
  width: min(1040px, 100%);
  margin: 0 auto;
  padding: 20px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-catalog-card-radius, 20px);
  background: var(--figma-surface);
  box-shadow: none;
  --listing-detail-section-gap: 18px;
}

.listing-detail-modal__content {
  display: flex;
  flex-direction: column;
  gap: var(--listing-detail-section-gap, 20px);
}

.listing-detail-modal__close {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 2;
  border: none;
  background: transparent;
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.listing-detail-modal__loader {
  position: absolute;
  inset: 0;
  z-index: 3;
  display: grid;
  place-items: center;
  background: var(--figma-surface-glass-soft);
  font-size: 14px;
  font-weight: 600;
}

.listing-detail-modal__top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px 20px;
  margin-bottom: 0;
  padding-right: 36px;
}

.listing-detail-modal--page .listing-detail-modal__top {
  padding-right: 0;
}

.listing-detail-modal__top-main {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.listing-detail-modal__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.listing-detail-modal__badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 23px;
  padding: 0 10px;
  border-radius: 10px;
  font-size: 11.5px;
  font-weight: 600;
}

.listing-detail-modal__badge--verified {
  background: var(--figma-verified-bg);
  color: var(--figma-verified-text);
}

.listing-detail-modal__badge--ai {
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.listing-detail-modal__badge--negotiable {
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
  color: var(--figma-accent);
}

.listing-detail-modal__meta {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-detail-modal__views {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.listing-detail-modal__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  justify-content: flex-end;
  margin-left: auto;
}

.listing-detail-modal__icon-btn,
.listing-detail-modal__action-btn {
  cursor: pointer;
}

.listing-detail-modal__icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  background: transparent;
  padding: 0;
  color: var(--figma-gray-mid);
}

.listing-detail-modal__icon-btn--active,
.listing-detail-modal__action-btn--active {
  color: var(--figma-accent);
}

.listing-detail-modal__icon-btn--pulse,
.listing-detail-modal__action-btn--pulse {
  animation: listing-action-pulse 0.42s ease;
}

@keyframes listing-action-pulse {
  0% {
    transform: scale(1);
  }
  40% {
    transform: scale(1.14);
  }
  100% {
    transform: scale(1);
  }
}

.listing-detail-modal__action-btn--active {
  border-color: var(--figma-accent);
}

.listing-detail-modal__action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 39px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}

.listing-detail-modal__hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(240px, 320px);
  gap: 20px;
  margin-bottom: 0;
  align-items: stretch;
}

.listing-detail-modal--page .listing-detail-modal__hero {
  grid-template-columns: minmax(0, 1.35fr) minmax(280px, 360px);
  gap: var(--listing-detail-section-gap, 24px);
}

.listing-detail-modal__gallery {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.listing-detail-modal__gallery-main {
  position: relative;
}

.listing-detail-modal__gallery-viewport {
  overflow: hidden;
  border-radius: 17px;
  cursor: zoom-in;
  touch-action: pan-y;
  user-select: none;
  -webkit-user-select: none;
}

.listing-detail-modal__gallery-viewport--dragging {
  cursor: grabbing;
}

.listing-detail-modal--page .listing-detail-modal__gallery-viewport {
  border-radius: var(--figma-catalog-image-radius, 15px);
}

.listing-detail-modal__gallery-track {
  display: flex;
  will-change: transform;
}

.listing-detail-modal__gallery-track--animate {
  transition: transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-detail-modal__gallery-track .listing-detail-modal__gallery-image {
  flex: 0 0 100%;
  min-width: 100%;
}

.listing-detail-modal__gallery-image {
  width: 100%;
  height: 320px;
  object-fit: cover;
  border-radius: 17px;
  display: block;
  background: var(--figma-placeholder);
}

.listing-detail-modal--page .listing-detail-modal__gallery-image {
  height: var(--listing-detail-gallery-height, 380px);
  border-radius: var(--figma-catalog-image-radius, 15px);
}

.listing-detail-modal__summary {
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 100%;
  padding: 4px 0;
}

.listing-detail-modal--page .listing-detail-modal__summary {
  gap: 0;
}

.listing-detail-modal__summary-footer {
  margin-top: auto;
  padding-top: 20px;
}

.listing-detail-modal__gallery-image--empty {
  background: var(--figma-placeholder);
}

.listing-detail-modal__gallery-nav {
  position: absolute;
  top: 50%;
  z-index: 2;
  width: 32px;
  height: 32px;
  margin-top: -16px;
  border: none;
  border-radius: 50%;
  background: var(--figma-surface-glass);
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.listing-detail-modal__gallery-nav--prev {
  left: 12px;
}

.listing-detail-modal__gallery-nav--next {
  right: 12px;
}

.listing-detail-modal__gallery-dots {
  position: absolute;
  left: 50%;
  bottom: 12px;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transform: translateX(-50%);
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(17, 24, 39, 0.35);
}

.listing-detail-modal__gallery-dot {
  width: 8px;
  height: 8px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.55);
  cursor: pointer;
  transition:
    transform 0.2s ease,
    background-color 0.2s ease;
}

.listing-detail-modal__gallery-dot--active {
  background: #fff;
  transform: scale(1.2);
}

.listing-detail-modal__gallery-dot:focus-visible {
  outline: 2px solid #fff;
  outline-offset: 2px;
}

.listing-detail-modal__gallery-counter {
  position: absolute;
  right: 16px;
  top: 16px;
  z-index: 2;
  height: 30px;
  padding: 0 12px;
  border-radius: 16px;
  background: #505050;
  color: var(--figma-on-accent);
  font-size: 14px;
  line-height: 30px;
}

.listing-detail-modal__thumbs-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  margin-top: 10px;
}

.listing-detail-modal__thumbs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  min-width: 0;
}

.listing-detail-modal__thumb {
  border: 2px solid transparent;
  border-radius: 10px;
  padding: 0;
  overflow: hidden;
  width: 72px;
  height: 72px;
  flex: 0 0 72px;
  background: var(--figma-placeholder);
  cursor: pointer;
}

.listing-detail-modal__thumb--active {
  border-color: var(--figma-accent);
}

.listing-detail-modal__thumb--more {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f3f3f3;
  color: var(--figma-gray-strong);
  font-size: 14px;
  font-weight: 600;
}

.listing-detail-modal__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.listing-detail-modal__photos-count {
  flex-shrink: 0;
  padding-bottom: 4px;
  font-size: 13px;
  font-weight: 600;
  color: var(--figma-text-muted);
  white-space: nowrap;
}

.listing-detail-modal__price-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 8px 16px;
  margin-bottom: 8px;
  min-width: 0;
  max-width: 100%;
}

.listing-detail-modal__price {
  min-width: 0;
  max-width: 100%;
  font-size: 35px;
  font-weight: 600;
  line-height: 1.1;
}

.listing-detail-modal__price :deep(.currency-amount__secondary) {
  font-size: 15px;
}

.listing-detail-modal__sqm {
  min-width: 0;
  max-width: 100%;
  font-size: 16px;
  color: var(--figma-text-muted);
}

.listing-detail-modal__sqm :deep(.currency-amount__primary),
.listing-detail-modal__sqm :deep(.currency-amount__secondary) {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.listing-detail-modal__sqm :deep(.currency-amount__secondary) {
  font-size: 15px;
}

.listing-detail-modal__specs,
.listing-detail-modal__metro {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 16px;
  color: var(--figma-text-muted);
}

.listing-detail-modal__dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #c4c4c4;
}

.listing-detail-modal__address {
  margin: 10px 0 0;
  font-size: 16px;
  color: var(--figma-ink);
}

.listing-detail-modal__district {
  margin: 6px 0 0;
  font-size: 16px;
  color: var(--figma-text-muted);
}

.listing-detail-modal__metro {
  margin-top: 10px;
  font-size: 12px;
}

.listing-detail-modal__cta-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 0;
}

.listing-detail-modal__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 44px;
  padding: 0 18px;
  border-radius: var(--figma-radius-btn);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.listing-detail-modal__cta--primary {
  border: none;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.listing-detail-modal__cta--secondary {
  border: 1px solid var(--figma-border);
  background: var(--figma-surface);
  color: var(--figma-ink);
}

.listing-detail-modal__response {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 10px 0 0;
  font-size: 10px;
  color: var(--figma-text-muted);
}

.listing-detail-modal__response-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #04832a;
}

.listing-detail-modal__description {
  margin-bottom: 0;
  padding: 0;
}

.listing-detail-modal--page .listing-detail-modal__description {
  padding-top: 4px;
}

.listing-detail-modal__section-title,
.listing-detail-modal__card-title {
  margin: 0 0 14px;
  font-size: 17px;
  font-weight: 700;
  color: var(--figma-ink);
}

.listing-detail-modal__description-text {
  margin: 0;
  max-width: none;
  font-size: 14px;
  line-height: 1.65;
  color: var(--figma-ink);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.listing-detail-modal__description-text--expanded {
  display: block;
  -webkit-line-clamp: unset;
}

.listing-detail-modal__link {
  margin-top: 12px;
  border: none;
  background: transparent;
  padding: 0;
  color: var(--figma-accent);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  text-align: left;
}

.listing-detail-modal__link--static {
  cursor: default;
}

.listing-detail-modal__bento {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.listing-detail-modal__bento-main {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(200px, 240px);
  gap: 14px;
  align-items: stretch;
}

.listing-detail-modal__bento-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  min-width: 0;
}

.listing-detail-modal__card--description {
  min-height: 0;
  padding: 18px 22px;
}

.listing-detail-modal__card--security {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: 6px 16px;
}

.listing-detail-modal__card--security .listing-detail-modal__card-title {
  grid-column: 1 / -1;
}

.listing-detail-modal__security-body {
  min-width: 0;
}

.listing-detail-modal__card--map {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  min-width: 0;
  padding: 0;
  border: none;
  overflow: hidden;
  background: transparent;
}

.listing-detail-modal__card {
  position: relative;
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(146, 146, 146, 0.19);
  border-radius: 16px;
  padding: 16px 18px;
  min-height: 0;
  min-width: 0;
  background: var(--figma-surface);
}

.listing-detail-modal__card--similar {
  width: 100%;
}

.listing-detail-modal__chars-skeleton,
.listing-detail-modal__infra-skeleton {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.listing-detail-modal__map-skeleton {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 360px;
  border: 1px solid var(--figma-border);
  border-radius: 16px;
  overflow: hidden;
  padding: 0;
}

.listing-detail-modal__map-skeleton :deep(.skeleton-wave),
.listing-detail-modal__map-skeleton :deep(.skeleton-wave__line) {
  flex: 1;
  height: 100%;
  min-height: 360px;
  border-radius: 0;
}

.listing-detail-modal__card--map :deep(.map-preview) {
  display: flex;
  flex-direction: column;
  flex: 1;
  height: 100%;
  min-height: 360px;
  border-radius: 16px;
}

.listing-detail-modal__card--map :deep(.map-preview__canvas),
.listing-detail-modal__card--map :deep(.map-preview__fallback) {
  flex: 1 1 auto;
  width: 100%;
  min-height: 280px;
  height: auto;
}

.listing-detail-modal__card--map :deep(.map-preview__cta) {
  flex: 0 0 auto;
}

.listing-detail-modal__characteristics {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0;
  margin: 0 0 8px;
}

.listing-detail-modal__characteristics-row {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
  align-items: baseline;
  gap: 10px;
  min-height: 0;
  padding: 7px 0;
  border-bottom: 1px solid rgba(146, 146, 146, 0.16);
}

.listing-detail-modal__characteristics-row:last-of-type {
  border-bottom: none;
}

.listing-detail-modal__characteristics-row dt {
  margin: 0;
  font-size: 13px;
  font-weight: 500;
  color: var(--figma-text-muted);
}

.listing-detail-modal__characteristics-row dd {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: var(--figma-ink);
  text-align: right;
  white-space: nowrap;
}

.listing-detail-modal__security-list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.listing-detail-modal__security-list li {
  position: relative;
  padding-left: 22px;
  margin-bottom: 14px;
  font-size: 14px;
  line-height: 1.4;
}

.listing-detail-modal__security-list li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: var(--figma-accent);
  font-weight: 700;
}

.listing-detail-modal__shield {
  position: static;
  align-self: start;
  flex-shrink: 0;
  opacity: 0.95;
}

.listing-detail-modal__infra-state {
  font-size: 13px;
  color: var(--figma-text-muted);
}

.listing-detail-modal__infra-list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.listing-detail-modal__infra-list li {
  display: grid;
  grid-template-columns: 24px 1fr auto;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  font-size: 14px;
  color: var(--figma-ink);
}

.listing-detail-modal__infra-icon {
  display: grid;
  place-items: center;
  width: 24px;
  height: 24px;
}

.listing-detail-modal__infra-icon img {
  display: block;
  width: 17px;
  height: 17px;
  object-fit: contain;
}

.listing-detail-modal__infra-label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listing-detail-modal__similar-skeleton,
.listing-detail-modal__similar-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  width: 100%;
  align-items: stretch;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card) {
  flex: 0 1 calc((100% - 30px) / 4);
  width: calc((100% - 30px) / 4);
  max-width: calc((100% - 30px) / 4);
  min-width: 0;
}

.listing-detail-modal__similar-skeleton > :deep(.skeleton-wave) {
  flex: 0 1 calc((100% - 30px) / 4);
  width: calc((100% - 30px) / 4);
  max-width: calc((100% - 30px) / 4);
  min-width: 0;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__image-wrap) {
  margin: 10px 10px 0;
  height: 132px;
  border-radius: 12px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__body) {
  gap: 4px;
  padding: 8px 10px 10px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__price) {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow: hidden;
  font-size: 14px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__price-row) {
  flex-direction: column;
  align-items: stretch;
  gap: 2px;
  min-height: 0;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__sqm) {
  display: none;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .currency-amount__primary),
.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .currency-amount__secondary) {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .currency-amount__secondary) {
  font-size: 11px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__address),
.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__district) {
  font-size: 12px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__location) {
  min-height: 48px;
  gap: 2px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__metro) {
  font-size: 11px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__cta),
.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__compare-btn) {
  min-height: 36px;
  height: 36px;
  padding: 0 10px;
  font-size: 13px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__compare-icon),
.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__favorite) {
  width: 32px;
  height: 32px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__badges) {
  top: 8px;
  left: 8px;
  right: 80px;
  max-width: calc(100% - 88px);
  gap: 4px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__offer-type) {
  height: 22px;
  padding: 0 8px;
  font-size: 10px;
}

.listing-detail-modal__similar-grid > :deep(.catalog-card--compact .catalog-card__overlay-actions) {
  top: 8px;
  right: 8px;
  gap: 6px;
}

.listing-detail-modal__similar-skeleton :deep(.skeleton-wave),
.listing-detail-modal__similar-skeleton :deep(.skeleton-wave__line) {
  min-height: 140px;
  border-radius: 12px;
}

.listing-detail-modal__card--similar .listing-detail-modal__card-title {
  margin-bottom: 12px;
  text-align: center;
}

@media (max-width: 991px) {
  .listing-detail-modal__similar-grid > :deep(.catalog-card),
  .listing-detail-modal__similar-skeleton > :deep(.skeleton-wave) {
    flex-basis: calc((100% - 10px) / 2);
    width: calc((100% - 10px) / 2);
    max-width: calc((100% - 10px) / 2);
  }
}

@media (max-width: 767px) {
  .listing-detail-modal__similar-grid > :deep(.catalog-card),
  .listing-detail-modal__similar-skeleton > :deep(.skeleton-wave) {
    flex-basis: 100%;
    width: 100%;
    max-width: 100%;
  }
}

@media (max-width: 1279px) {
  .listing-detail-modal:not(.listing-detail-modal--page) .listing-detail-modal__hero {
    grid-template-columns: 1fr;
  }

  .listing-detail-modal:not(.listing-detail-modal--page) .listing-detail-modal__bento-main {
    grid-template-columns: 1fr;
  }

  .listing-detail-modal:not(.listing-detail-modal--page) .listing-detail-modal__price {
    font-size: 28px;
  }

  .listing-detail-modal__card--map :deep(.map-preview),
  .listing-detail-modal__map-skeleton {
    min-height: 280px;
  }

  .listing-detail-modal__card--map :deep(.map-preview__canvas),
  .listing-detail-modal__card--map :deep(.map-preview__fallback),
  .listing-detail-modal__map-skeleton :deep(.skeleton-wave__line) {
    min-height: 200px;
  }
}

@media (max-width: 1199px) {
  .listing-detail-modal--page .listing-detail-modal__bento-main {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 991px) {
  .listing-detail-modal--page .listing-detail-modal__hero {
    grid-template-columns: 1fr;
  }

  .listing-detail-modal--page .listing-detail-modal__price {
    font-size: 30px;
  }

  .listing-detail-modal__bento-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767px) {
  .listing-detail-modal:not(.listing-detail-modal--page) {
    width: 100%;
    min-height: 100vh;
    border-radius: 0;
    padding: 16px 12px 24px;
  }

  .listing-detail-modal--page {
    padding: 16px;
    border-radius: 16px;
  }

  .listing-detail-modal__bento-main,
  .listing-detail-modal__bento-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .listing-detail-modal__card {
    min-height: 0;
    padding: 16px;
  }

  .listing-detail-modal__card--security {
    grid-template-columns: 1fr;
  }

  .listing-detail-modal__shield {
    display: none;
  }

  .listing-detail-modal__top {
    flex-direction: column;
    align-items: stretch;
    padding-right: 0;
  }

  .listing-detail-modal__actions {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: center;
    justify-content: stretch;
    gap: 10px;
    width: 100%;
    margin-left: 0;
  }

  .listing-detail-modal__actions > .listing-detail-modal__icon-btn {
    grid-column: 1;
    grid-row: 1;
    width: 44px;
    height: 44px;
  }

  .listing-detail-modal__actions > .listing-detail-modal__action-btn:nth-child(2) {
    grid-column: 2;
    grid-row: 1;
    width: 100%;
    min-height: 44px;
    justify-content: center;
  }

  .listing-detail-modal__actions > .listing-detail-modal__action-btn:nth-child(3),
  .listing-detail-modal__actions > .listing-detail-modal__action-btn:nth-child(4) {
    grid-column: 1 / -1;
    width: 100%;
    min-height: 44px;
    justify-content: center;
  }
}

@media (prefers-reduced-motion: reduce) {
  .listing-detail-modal__gallery-track--animate {
    transition: none;
  }
}
</style>
