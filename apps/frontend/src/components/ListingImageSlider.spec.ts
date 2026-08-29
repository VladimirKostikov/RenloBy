import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingImageSlider from '@/components/ListingImageSlider.vue'
import ru from '@/locales/ru.json'

function mountSlider(images: string[], props: Record<string, unknown> = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  return mount(ListingImageSlider, {
    props: {
      images,
      alt: 'Test listing',
      resetKey: 1,
      autoplay: false,
      ...props,
    },
    global: {
      plugins: [i18n],
    },
  })
}

describe('ListingImageSlider', () => {
  afterEach(() => {
    vi.useRealTimers()
  })

  it('shows navigation and pagination dots when listing has multiple photos', () => {
    const wrapper = mountSlider([
      'https://example.com/1.jpg',
      'https://example.com/2.jpg',
    ])

    expect(wrapper.find('.listing-image-slider__nav--prev').exists()).toBe(true)
    expect(wrapper.find('.listing-image-slider__nav--next').exists()).toBe(true)
    expect(wrapper.findAll('.listing-image-slider__dot')).toHaveLength(2)
    expect(wrapper.find('.listing-image-slider__dot--active').exists()).toBe(true)
  })

  it('switches photo on next button click', async () => {
    const wrapper = mountSlider([
      'https://example.com/1.jpg',
      'https://example.com/2.jpg',
    ])

    const track = wrapper.find('.listing-image-slider__track')

    await wrapper.find('.listing-image-slider__nav--next').trigger('click')
    await wrapper.vm.$nextTick()

    expect((track.element as HTMLElement).style.transform).toBe('translate3d(-100%, 0, 0)')
    expect(wrapper.findAll('.listing-image-slider__dot')[1].classes()).toContain(
      'listing-image-slider__dot--active',
    )
    expect(track.classes()).toContain('listing-image-slider__track--animate')
  })

  it('switches photo when pagination dot is clicked', async () => {
    const wrapper = mountSlider([
      'https://example.com/1.jpg',
      'https://example.com/2.jpg',
      'https://example.com/3.jpg',
    ])

    await wrapper.findAll('.listing-image-slider__dot')[2].trigger('click')
    await wrapper.vm.$nextTick()

    const track = wrapper.find('.listing-image-slider__track')
    expect((track.element as HTMLElement).style.transform).toBe('translate3d(-200%, 0, 0)')
  })

  it('hides navigation for a single photo', () => {
    const wrapper = mountSlider(['https://example.com/1.jpg'])

    expect(wrapper.find('.listing-image-slider__nav--prev').exists()).toBe(false)
    expect(wrapper.find('.listing-image-slider__nav--next').exists()).toBe(false)
    expect(wrapper.find('.listing-image-slider__dots').exists()).toBe(false)
  })

  it('opens fullscreen lightbox on photo click', async () => {
    const wrapper = mountSlider([
      'https://example.com/1.jpg',
      'https://example.com/2.jpg',
    ])

    await wrapper.find('.listing-image-slider__viewport').trigger('click')
    await wrapper.vm.$nextTick()

    expect(document.body.querySelector('.photo-lightbox')).not.toBeNull()
    expect(document.body.querySelector('.photo-lightbox__close--corner')).not.toBeNull()
    expect(document.body.querySelector('.photo-lightbox__close-btn')).not.toBeNull()

    wrapper.unmount()
  })

  it('swipes to the next slide on pointer gesture', async () => {
    const wrapper = mountSlider([
      'https://example.com/1.jpg',
      'https://example.com/2.jpg',
    ])

    const viewport = wrapper.find('.listing-image-slider__viewport')
    Object.defineProperty(viewport.element, 'clientWidth', { value: 300, configurable: true })

    await viewport.trigger('pointerdown', { pointerId: 1, button: 0, clientX: 200, clientY: 40 })
    await viewport.trigger('pointermove', { pointerId: 1, clientX: 120, clientY: 42 })
    await viewport.trigger('pointerup', { pointerId: 1, clientX: 120, clientY: 42 })
    await wrapper.vm.$nextTick()

    const track = wrapper.find('.listing-image-slider__track')
    expect((track.element as HTMLElement).style.transform).toBe('translate3d(-100%, 0, 0)')
  })

  it('autoplays to the next slide only while hovered', async () => {
    vi.useFakeTimers()
    const wrapper = mountSlider(
      ['https://example.com/1.jpg', 'https://example.com/2.jpg'],
      { autoplay: true, autoplayMs: 1000 },
    )

    vi.advanceTimersByTime(1000)
    await wrapper.vm.$nextTick()
    expect((wrapper.find('.listing-image-slider__track').element as HTMLElement).style.transform).toBe(
      'translate3d(0%, 0, 0)',
    )

    await wrapper.find('.listing-image-slider').trigger('mouseenter')
    vi.advanceTimersByTime(1000)
    await wrapper.vm.$nextTick()

    expect((wrapper.find('.listing-image-slider__track').element as HTMLElement).style.transform).toBe(
      'translate3d(-100%, 0, 0)',
    )

    await wrapper.find('.listing-image-slider').trigger('mouseleave')
    vi.advanceTimersByTime(1000)
    await wrapper.vm.$nextTick()

    expect((wrapper.find('.listing-image-slider__track').element as HTMLElement).style.transform).toBe(
      'translate3d(-100%, 0, 0)',
    )

    wrapper.unmount()
  })
})
