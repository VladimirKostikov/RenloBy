import { computed, onMounted, onUnmounted, ref, watch, type Ref, getCurrentInstance } from 'vue'

const SWIPE_THRESHOLD_PX = 40
const DEFAULT_AUTOPLAY_MS = 4500

export { SWIPE_THRESHOLD_PX, DEFAULT_AUTOPLAY_MS }

export function useListingImageSlider(options: {
  slideCount: Ref<number>
  autoplay?: Ref<boolean>
  autoplayMs?: Ref<number>
  paused?: Ref<boolean>
}) {
  const slideIndex = ref(0)
  const slideTransitionEnabled = ref(true)
  const dragOffsetPx = ref(0)
  const isDragging = ref(false)

  let pointerId: number | null = null
  let startX = 0
  let startY = 0
  let moved = false
  let autoplayTimer: ReturnType<typeof setInterval> | null = null
  let suppressClick = false

  const hasMultipleSlides = computed(() => options.slideCount.value > 1)

  function clampIndex(index: number): number {
    const count = options.slideCount.value
    if (count <= 0) {
      return 0
    }
    return ((index % count) + count) % count
  }

  function goTo(index: number) {
    if (options.slideCount.value <= 1) {
      return
    }
    slideIndex.value = clampIndex(index)
  }

  function showPrevSlide() {
    goTo(slideIndex.value - 1)
  }

  function showNextSlide() {
    goTo(slideIndex.value + 1)
  }

  function resetToFirst() {
    slideTransitionEnabled.value = false
    slideIndex.value = 0
    dragOffsetPx.value = 0
    isDragging.value = false
    pointerId = null
    moved = false
  }

  function enableTransitionNextTick(nextTickFn: () => Promise<void>) {
    void nextTickFn().then(() => {
      slideTransitionEnabled.value = true
    })
  }

  function stopAutoplay() {
    if (autoplayTimer !== null) {
      clearInterval(autoplayTimer)
      autoplayTimer = null
    }
  }

  function startAutoplay() {
    stopAutoplay()

    if (!options.autoplay?.value || !hasMultipleSlides.value || options.paused?.value) {
      return
    }

    if (typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return
    }

    const interval = options.autoplayMs?.value ?? DEFAULT_AUTOPLAY_MS
    autoplayTimer = setInterval(() => {
      if (options.paused?.value || isDragging.value) {
        return
      }
      showNextSlide()
    }, interval)
  }

  function restartAutoplay() {
    startAutoplay()
  }

  function onPointerDown(event: PointerEvent) {
    if (!hasMultipleSlides.value || event.button !== 0) {
      return
    }

    pointerId = event.pointerId
    startX = event.clientX
    startY = event.clientY
    moved = false
    isDragging.value = true
    dragOffsetPx.value = 0
    slideTransitionEnabled.value = false
    stopAutoplay()
    ;(event.currentTarget as HTMLElement | null)?.setPointerCapture?.(event.pointerId)
  }

  function onPointerMove(event: PointerEvent) {
    if (!isDragging.value || pointerId !== event.pointerId) {
      return
    }

    const dx = event.clientX - startX
    const dy = event.clientY - startY

    if (!moved && Math.abs(dx) < 6 && Math.abs(dy) < 6) {
      return
    }

    if (!moved && Math.abs(dy) > Math.abs(dx)) {
      isDragging.value = false
      dragOffsetPx.value = 0
      pointerId = null
      slideTransitionEnabled.value = true
      restartAutoplay()
      return
    }

    moved = true
    dragOffsetPx.value = dx
  }

  function onPointerUp(event: PointerEvent) {
    if (pointerId !== event.pointerId && pointerId !== null) {
      return
    }

    const dx = dragOffsetPx.value
    const didSwipe = moved && Math.abs(dx) >= SWIPE_THRESHOLD_PX

    isDragging.value = false
    pointerId = null
    dragOffsetPx.value = 0
    slideTransitionEnabled.value = true

    if (didSwipe) {
      suppressClick = true
      if (dx < 0) {
        showNextSlide()
      } else {
        showPrevSlide()
      }
    }

    moved = false
    restartAutoplay()
  }

  function consumeClickSuppressed(): boolean {
    if (!suppressClick) {
      return false
    }
    suppressClick = false
    return true
  }

  function trackStyle(viewportWidthPx: number): Record<string, string> {
    const base = -slideIndex.value * 100
    if (!isDragging.value || dragOffsetPx.value === 0 || viewportWidthPx <= 0) {
      return {
        transform: `translate3d(${base}%, 0, 0)`,
      }
    }

    const dragPercent = (dragOffsetPx.value / viewportWidthPx) * 100
    return {
      transform: `translate3d(calc(${base}% + ${dragPercent}%), 0, 0)`,
    }
  }

  watch(
    () => [options.slideCount.value, options.autoplay?.value, options.autoplayMs?.value, options.paused?.value] as const,
    () => {
      if (slideIndex.value >= options.slideCount.value) {
        slideIndex.value = Math.max(0, options.slideCount.value - 1)
      }
      restartAutoplay()
    },
  )

  const instance = getCurrentInstance()
  if (instance) {
    onMounted(() => {
      startAutoplay()
    })

    onUnmounted(() => {
      stopAutoplay()
    })
  } else {
    startAutoplay()
  }

  return {
    slideIndex,
    slideTransitionEnabled,
    dragOffsetPx,
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
    trackStyle,
    restartAutoplay,
    stopAutoplay,
  }
}
