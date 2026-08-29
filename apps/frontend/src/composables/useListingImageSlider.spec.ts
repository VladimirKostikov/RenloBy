import { computed, ref } from 'vue'
import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  DEFAULT_AUTOPLAY_MS,
  SWIPE_THRESHOLD_PX,
  useListingImageSlider,
} from '@/composables/useListingImageSlider'

describe('useListingImageSlider', () => {
  afterEach(() => {
    vi.useRealTimers()
  })

  it('advances slides on interval when autoplay is enabled', () => {
    vi.useFakeTimers()
    const slideCount = ref(3)
    const autoplay = ref(true)
    const paused = ref(false)

    const slider = useListingImageSlider({
      slideCount,
      autoplay,
      autoplayMs: ref(1000),
      paused,
    })
    slider.restartAutoplay()

    expect(slider.slideIndex.value).toBe(0)
    vi.advanceTimersByTime(1000)
    expect(slider.slideIndex.value).toBe(1)
    vi.advanceTimersByTime(1000)
    expect(slider.slideIndex.value).toBe(2)
    vi.advanceTimersByTime(1000)
    expect(slider.slideIndex.value).toBe(0)

    slider.stopAutoplay()
  })

  it('does not autoplay while paused', () => {
    vi.useFakeTimers()
    const slider = useListingImageSlider({
      slideCount: ref(2),
      autoplay: ref(true),
      autoplayMs: ref(500),
      paused: ref(true),
    })

    vi.advanceTimersByTime(2000)
    expect(slider.slideIndex.value).toBe(0)
    slider.stopAutoplay()
  })

  it('goes to selected slide index', () => {
    const slider = useListingImageSlider({
      slideCount: ref(4),
      autoplay: ref(false),
    })

    slider.goTo(2)
    expect(slider.slideIndex.value).toBe(2)
    slider.showPrevSlide()
    expect(slider.slideIndex.value).toBe(1)
    slider.stopAutoplay()
  })

  it('exports swipe threshold and default interval', () => {
    expect(SWIPE_THRESHOLD_PX).toBe(40)
    expect(DEFAULT_AUTOPLAY_MS).toBe(4500)
  })

  it('builds dragged track transform from viewport width', () => {
    const slider = useListingImageSlider({
      slideCount: ref(3),
      autoplay: ref(false),
    })

    slider.slideIndex.value = 1
    slider.isDragging.value = true
    slider.dragOffsetPx.value = -50

    const style = slider.trackStyle(200)
    expect(style.transform).toContain('calc(-100%')
    expect(style.transform).toContain('-25%')
    slider.stopAutoplay()
  })
})
