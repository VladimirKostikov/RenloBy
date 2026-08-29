<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useListingImageSlider } from '@/composables/useListingImageSlider'

const props = defineProps<{
  open?: boolean
  images: string[]
  alt: string
  startIndex?: number
  originRect?: { top: number; left: number; width: number; height: number } | null
}>()

const emit = defineEmits<{
  close: []
}>()

const { t } = useI18n()
const stageRef = ref<HTMLElement | null>(null)
const autoplayEnabled = ref(false)
const autoplayMs = ref(4500)
const paused = ref(true)

const photos = computed(() => props.images.filter((image) => Boolean(image)))
const slideCount = computed(() => photos.value.length)

const {
  slideIndex: index,
  slideTransitionEnabled,
  isDragging,
  hasMultipleSlides: hasMultiple,
  goTo,
  showPrevSlide: showPrev,
  showNextSlide: showNext,
  onPointerDown,
  onPointerMove,
  onPointerUp,
  trackStyle: buildTrackStyle,
  restartAutoplay,
} = useListingImageSlider({
  slideCount,
  autoplay: autoplayEnabled,
  autoplayMs,
  paused,
})

const counter = computed(() =>
  photos.value.length > 0 ? `${index.value + 1}/${photos.value.length}` : '',
)

const trackStyle = computed(() => {
  const width = isDragging.value ? (stageRef.value?.clientWidth ?? 0) : 0
  return buildTrackStyle(width)
})

watch(
  () => props.startIndex,
  (value) => {
    if (typeof value !== 'number' || photos.value.length === 0) {
      return
    }
    const next = Math.min(Math.max(0, value), photos.value.length - 1)
    if (next === index.value) {
      return
    }
    goTo(next)
    restartAutoplay()
  },
)

function close() {
  emit('close')
}

function onPrevClick() {
  showPrev()
  restartAutoplay()
}

function onNextClick() {
  showNext()
  restartAutoplay()
}

function onDotClick(dotIndex: number) {
  goTo(dotIndex)
  restartAutoplay()
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    event.preventDefault()
    close()
    return
  }
  if (event.key === 'ArrowLeft') {
    event.preventDefault()
    onPrevClick()
    return
  }
  if (event.key === 'ArrowRight') {
    event.preventDefault()
    onNextClick()
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  document.body.style.overflow = 'hidden'
  if (typeof props.startIndex === 'number' && props.startIndex > 0 && photos.value.length > 1) {
    goTo(Math.min(props.startIndex, photos.value.length - 1))
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <div
      class="photo-lightbox"
      role="dialog"
      aria-modal="true"
      :aria-label="t('listingDetail.photoFullscreen')"
      @click.self="close"
    >
      <button
        type="button"
        class="photo-lightbox__close photo-lightbox__close--corner"
        :aria-label="t('listingDetail.close')"
        @click="close"
      >
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>

      <button
        v-if="hasMultiple"
        type="button"
        class="photo-lightbox__nav photo-lightbox__nav--prev"
        :aria-label="t('catalog.prevPhoto')"
        @click.stop="onPrevClick"
      >
        ‹
      </button>

      <div
        ref="stageRef"
        class="photo-lightbox__stage"
        :class="{ 'photo-lightbox__stage--dragging': isDragging }"
        @click.self="close"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
      >
        <div
          class="photo-lightbox__track"
          :class="{ 'photo-lightbox__track--animate': slideTransitionEnabled && !isDragging }"
          :style="trackStyle"
        >
          <div v-for="(image, imageIndex) in photos" :key="`${image}-${imageIndex}`" class="photo-lightbox__slide">
            <img :src="image" :alt="alt" class="photo-lightbox__image" draggable="false" />
          </div>
        </div>
        <div
          v-if="hasMultiple"
          class="photo-lightbox__dots"
          role="tablist"
          :aria-label="t('catalog.photoPagination')"
        >
          <button
            v-for="(_, dotIndex) in photos"
            :key="`lightbox-dot-${dotIndex}`"
            type="button"
            class="photo-lightbox__dot"
            :class="{ 'photo-lightbox__dot--active': index === dotIndex }"
            role="tab"
            :aria-selected="index === dotIndex"
            :aria-label="t('catalog.photoSlide', { n: dotIndex + 1 })"
            @click.stop="onDotClick(dotIndex)"
          />
        </div>
        <div v-if="counter" class="photo-lightbox__counter">{{ counter }}</div>
      </div>

      <button
        v-if="hasMultiple"
        type="button"
        class="photo-lightbox__nav photo-lightbox__nav--next"
        :aria-label="t('catalog.nextPhoto')"
        @click.stop="onNextClick"
      >
        ›
      </button>

      <div class="photo-lightbox__footer">
        <button type="button" class="photo-lightbox__close-btn" @click="close">
          {{ t('listingDetail.close') }}
        </button>
        <button
          type="button"
          class="photo-lightbox__backdrop-hint"
          :aria-label="t('listingDetail.close')"
          @click="close"
        >
          {{ t('listingDetail.closeHint') }}
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.photo-lightbox {
  position: fixed;
  inset: 0;
  z-index: 5000;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  grid-template-rows: auto minmax(0, 1fr) auto;
  align-items: center;
  justify-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.88);
}

.photo-lightbox__close--corner {
  grid-column: 1 / -1;
  grid-row: 1;
  justify-self: end;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.16);
  color: var(--figma-on-accent);
  cursor: pointer;
  transition:
    background-color 0.16s ease,
    transform 0.16s ease;
}

.photo-lightbox__close--corner:hover {
  background: rgba(255, 255, 255, 0.28);
  transform: scale(1.04);
}

.photo-lightbox__stage {
  grid-column: 2;
  grid-row: 2;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: min(100%, 1200px);
  height: 100%;
  min-height: 0;
  overflow: hidden;
  touch-action: pan-y;
  user-select: none;
  -webkit-user-select: none;
}

.photo-lightbox__stage--dragging {
  cursor: grabbing;
}

.photo-lightbox__track {
  display: flex;
  width: 100%;
  height: 100%;
  will-change: transform;
  pointer-events: none;
}

.photo-lightbox__track--animate {
  transition: transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
}

.photo-lightbox__slide {
  flex: 0 0 100%;
  width: 100%;
  min-width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.photo-lightbox__image {
  max-width: 100%;
  max-height: calc(100vh - 160px);
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 12px;
  pointer-events: auto;
  user-select: none;
}

.photo-lightbox__dots {
  position: absolute;
  left: 50%;
  bottom: 48px;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transform: translateX(-50%);
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(17, 24, 39, 0.45);
  pointer-events: auto;
}

.photo-lightbox__dot {
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

.photo-lightbox__dot--active {
  background: #fff;
  transform: scale(1.2);
}

.photo-lightbox__dot:focus-visible {
  outline: 2px solid #fff;
  outline-offset: 2px;
}

.photo-lightbox__counter {
  position: absolute;
  left: 50%;
  bottom: 12px;
  z-index: 2;
  transform: translateX(-50%);
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.55);
  color: var(--figma-on-accent);
  font-size: 13px;
  font-weight: 600;
  pointer-events: none;
}

.photo-lightbox__nav {
  grid-row: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border: none;
  border-radius: 50%;
  background: var(--figma-surface-glass);
  color: var(--figma-ink);
  font-size: 30px;
  line-height: 1;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
  transition: transform 0.16s ease;
}

.photo-lightbox__nav:hover {
  transform: scale(1.05);
}

.photo-lightbox__nav--prev {
  grid-column: 1;
}

.photo-lightbox__nav--next {
  grid-column: 3;
}

.photo-lightbox__footer {
  grid-column: 1 / -1;
  grid-row: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding-top: 8px;
}

.photo-lightbox__close-btn {
  min-width: 140px;
  height: 44px;
  padding: 0 20px;
  border: none;
  border-radius: 50px;
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.16s ease, background-color 0.16s ease;
}

.photo-lightbox__close-btn:hover {
  background: #f3f3f3;
  transform: translateY(-1px);
}

.photo-lightbox__backdrop-hint {
  border: none;
  background: transparent;
  color: rgba(255, 255, 255, 0.72);
  font-size: 12px;
  cursor: pointer;
}

@media (max-width: 767px) {
  .photo-lightbox {
    grid-template-columns: 1fr;
    grid-template-rows: auto minmax(0, 1fr) auto auto;
    padding: 12px;
  }

  .photo-lightbox__stage {
    grid-column: 1;
    width: 100%;
  }

  .photo-lightbox__nav {
    position: absolute;
    top: 50%;
    z-index: 2;
    margin-top: -24px;
  }

  .photo-lightbox__nav--prev {
    left: 10px;
    grid-column: 1;
  }

  .photo-lightbox__nav--next {
    right: 10px;
    grid-column: 1;
  }

  .photo-lightbox__image {
    max-height: calc(100vh - 180px);
  }

  .photo-lightbox__dots {
    bottom: 44px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .photo-lightbox__close--corner,
  .photo-lightbox__nav,
  .photo-lightbox__close-btn,
  .photo-lightbox__dot {
    transition: none;
  }

  .photo-lightbox__track--animate {
    transition: none;
  }
}
</style>
