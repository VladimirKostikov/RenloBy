<script setup lang="ts">
import { computed, nextTick, ref, toRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ListingPhotoLightbox from '@/components/ListingPhotoLightbox.vue'
import { useListingImageSlider } from '@/composables/useListingImageSlider'
import { sliceListingImages } from '@/lib/mapListingSelection'

const props = withDefaults(
  defineProps<{
    images: string[]
    alt: string
    maxSlides?: number
    compact?: boolean
    resetKey?: string | number
    autoplay?: boolean
    autoplayMs?: number
    showDots?: boolean
    showCounter?: boolean
    enableLightbox?: boolean
  }>(),
  {
    maxSlides: 4,
    compact: false,
    autoplay: true,
    autoplayMs: 4500,
    showDots: true,
    showCounter: false,
    enableLightbox: true,
  },
)

const { t } = useI18n()
const lightboxOpen = ref(false)
const viewportRef = ref<HTMLElement | null>(null)
const isHovering = ref(false)

const sliderImages = computed(() => sliceListingImages(props.images, props.maxSlides))
const slideCount = computed(() => sliderImages.value.length)
const hasPhotos = computed(() => sliderImages.value.some((image) => Boolean(image)))
const autoplayEnabled = toRef(props, 'autoplay')
const autoplayMs = toRef(props, 'autoplayMs')
const paused = computed(() => lightboxOpen.value || !isHovering.value)

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
  autoplay: autoplayEnabled,
  autoplayMs,
  paused,
})

const slideCounter = computed(() => {
  if (sliderImages.value.length === 0) {
    return ''
  }
  return `${slideIndex.value + 1}/${sliderImages.value.length}`
})

const trackStyle = computed(() => {
  const width = viewportRef.value?.clientWidth ?? 0
  return buildTrackStyle(width)
})

watch(
  () => [props.resetKey, props.images] as const,
  async () => {
    resetToFirst()
    lightboxOpen.value = false
    enableTransitionNextTick(nextTick)
  },
)

function openLightbox() {
  if (!props.enableLightbox || !hasPhotos.value || consumeClickSuppressed()) {
    return
  }
  lightboxOpen.value = true
}

function onPrevClick() {
  showPrevSlide()
  restartAutoplay()
}

function onNextClick() {
  showNextSlide()
  restartAutoplay()
}

function onDotClick(index: number) {
  goTo(index)
  restartAutoplay()
}
</script>

<template>
  <div
    class="listing-image-slider"
    :class="{ 'listing-image-slider--compact': compact }"
    @mouseenter="isHovering = true"
    @mouseleave="isHovering = false"
  >
    <div
      ref="viewportRef"
      class="listing-image-slider__viewport"
      role="group"
      :aria-roledescription="t('catalog.photoSlider')"
      :aria-label="alt"
      :tabindex="hasPhotos && enableLightbox ? 0 : undefined"
      @pointerdown="onPointerDown"
      @pointermove="onPointerMove"
      @pointerup="onPointerUp"
      @pointercancel="onPointerUp"
      @click="openLightbox"
      @keydown.enter.prevent="openLightbox"
      @keydown.space.prevent="openLightbox"
    >
      <div
        class="listing-image-slider__track"
        :class="{ 'listing-image-slider__track--animate': slideTransitionEnabled && !isDragging }"
        :style="trackStyle"
      >
        <template v-if="sliderImages.length > 0">
          <template v-for="(image, index) in sliderImages" :key="`${resetKey ?? 'slide'}-${index}`">
            <img
              v-if="image"
              :src="image"
              :alt="alt"
              class="listing-image-slider__image"
              draggable="false"
            />
            <div v-else class="listing-image-slider__image listing-image-slider__image--placeholder" />
          </template>
        </template>
        <div
          v-else
          class="listing-image-slider__image listing-image-slider__image--placeholder"
        />
      </div>
    </div>

    <button
      v-if="hasMultipleSlides"
      type="button"
      class="listing-image-slider__nav listing-image-slider__nav--prev"
      :aria-label="t('catalog.prevPhoto')"
      @click.stop="onPrevClick"
    >
      ‹
    </button>
    <button
      v-if="hasMultipleSlides"
      type="button"
      class="listing-image-slider__nav listing-image-slider__nav--next"
      :aria-label="t('catalog.nextPhoto')"
      @click.stop="onNextClick"
    >
      ›
    </button>

    <div
      v-if="showDots && hasMultipleSlides"
      class="listing-image-slider__dots"
      role="tablist"
      :aria-label="t('catalog.photoPagination')"
    >
      <button
        v-for="(_, index) in sliderImages"
        :key="`dot-${index}`"
        type="button"
        class="listing-image-slider__dot"
        :class="{ 'listing-image-slider__dot--active': slideIndex === index }"
        role="tab"
        :aria-selected="slideIndex === index"
        :aria-label="t('catalog.photoSlide', { n: index + 1 })"
        @click.stop="onDotClick(index)"
      />
    </div>

    <div v-if="showCounter && slideCounter" class="listing-image-slider__counter">{{ slideCounter }}</div>

    <ListingPhotoLightbox
      v-if="lightboxOpen"
      :images="sliderImages"
      :alt="alt"
      :start-index="slideIndex"
      @close="lightboxOpen = false"
    />
  </div>
</template>

<style scoped>
.listing-image-slider {
  position: relative;
  width: 100%;
  height: 100%;
  touch-action: pan-y;
}

.listing-image-slider__viewport {
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  border: none;
  background: transparent;
  overflow: hidden;
  cursor: zoom-in;
  touch-action: pan-y;
  user-select: none;
  -webkit-user-select: none;
}

.listing-image-slider__viewport:focus-visible {
  outline: 2px solid var(--figma-accent);
  outline-offset: 2px;
}

.listing-image-slider__track {
  display: flex;
  height: 100%;
  will-change: transform;
}

.listing-image-slider__track--animate {
  transition: transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-image-slider__image {
  flex: 0 0 100%;
  width: 100%;
  min-width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  pointer-events: none;
}

.listing-image-slider__image--placeholder {
  background: var(--figma-placeholder);
}

.listing-image-slider__nav {
  position: absolute;
  top: 50%;
  z-index: 2;
  width: 32px;
  height: 32px;
  margin-top: -16px;
  border: none;
  border-radius: 50%;
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  opacity: 0;
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.listing-image-slider:hover .listing-image-slider__nav,
.listing-image-slider:focus-within .listing-image-slider__nav {
  opacity: 1;
}

.listing-image-slider__nav:hover {
  transform: scale(1.05);
}

.listing-image-slider__nav--prev {
  left: 10px;
}

.listing-image-slider__nav--next {
  right: 10px;
}

.listing-image-slider__dots {
  position: absolute;
  left: 50%;
  bottom: 10px;
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

.listing-image-slider__dot {
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

.listing-image-slider__dot--active {
  background: #fff;
  transform: scale(1.2);
}

.listing-image-slider__dot:focus-visible {
  outline: 2px solid #fff;
  outline-offset: 2px;
}

.listing-image-slider__counter {
  position: absolute;
  right: 12px;
  bottom: 12px;
  z-index: 2;
  height: 22px;
  padding: 0 10px;
  border-radius: 10px;
  background: rgba(80, 80, 80, 0.88);
  color: var(--figma-on-accent);
  font-size: 12px;
  font-weight: 600;
  line-height: 22px;
  pointer-events: none;
}

.listing-image-slider--compact .listing-image-slider__nav {
  width: 28px;
  height: 28px;
  margin-top: -14px;
  font-size: 20px;
}

.listing-image-slider--compact .listing-image-slider__nav--prev {
  left: 8px;
}

.listing-image-slider--compact .listing-image-slider__nav--next {
  right: 8px;
}

.listing-image-slider--compact .listing-image-slider__dots {
  bottom: 8px;
  gap: 5px;
  padding: 3px 6px;
}

.listing-image-slider--compact .listing-image-slider__dot {
  width: 7px;
  height: 7px;
}

.listing-image-slider--compact .listing-image-slider__counter {
  right: 10px;
  bottom: 10px;
  height: 20px;
  font-size: 11px;
  line-height: 20px;
}

@media (max-width: 767px) {
  .listing-image-slider__nav {
    opacity: 1;
    width: 36px;
    height: 36px;
    margin-top: -18px;
  }

  .listing-image-slider__dot {
    width: 9px;
    height: 9px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .listing-image-slider__track--animate {
    transition: none;
  }

  .listing-image-slider__nav,
  .listing-image-slider__dot {
    transition: none;
  }
}
</style>
