import { mount, flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { nextTick } from 'vue'
import ListingPhotoLightbox from '@/components/ListingPhotoLightbox.vue'
import ru from '@/locales/ru.json'

function mountLightbox(
  props: Record<string, unknown> = {},
) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  return mount(ListingPhotoLightbox, {
    props: {
      open: true,
      images: ['https://example.com/1.jpg', 'https://example.com/2.jpg'],
      alt: 'Test',
      startIndex: 0,
      ...props,
    },
    global: {
      plugins: [i18n],
      stubs: {
        teleport: true,
      },
    },
  })
}

describe('ListingPhotoLightbox', () => {
  afterEach(() => {
    document.body.innerHTML = ''
    document.body.style.overflow = ''
    vi.restoreAllMocks()
  })

  it('shows image counter and closes from corner button', async () => {
    const wrapper = mountLightbox()
    await nextTick()

    expect(wrapper.find('.photo-lightbox__counter').text()).toBe('1/2')
    expect(wrapper.find('.photo-lightbox__close-btn').text()).toBe('Закрыть')
    expect(wrapper.find('.photo-lightbox__backdrop-hint').text()).toContain('Escape')

    await wrapper.find('.photo-lightbox__close--corner').trigger('click')
    await flushPromises()
    expect(wrapper.emitted('close')).toHaveLength(1)

    wrapper.unmount()
  })

  it('switches photo with next button', async () => {
    const wrapper = mountLightbox()
    await nextTick()

    await wrapper.find('.photo-lightbox__nav--next').trigger('click')
    await nextTick()

    expect(wrapper.find('.photo-lightbox__counter').text()).toBe('2/2')
    expect(wrapper.findAll('.photo-lightbox__image')[1].attributes('src')).toBe(
      'https://example.com/2.jpg',
    )
    expect(wrapper.findAll('.photo-lightbox__dot')[1].classes()).toContain('photo-lightbox__dot--active')

    wrapper.unmount()
  })

  it('switches photo via pagination dots', async () => {
    const wrapper = mountLightbox({
      images: [
        'https://example.com/1.jpg',
        'https://example.com/2.jpg',
        'https://example.com/3.jpg',
      ],
    })
    await nextTick()

    expect(wrapper.findAll('.photo-lightbox__dot')).toHaveLength(3)
    await wrapper.findAll('.photo-lightbox__dot')[2].trigger('click')
    await nextTick()

    expect(wrapper.find('.photo-lightbox__counter').text()).toBe('3/3')
    expect(wrapper.findAll('.photo-lightbox__dot')[2].classes()).toContain('photo-lightbox__dot--active')

    wrapper.unmount()
  })

  it('closes from footer close button', async () => {
    const wrapper = mountLightbox()
    await nextTick()

    await wrapper.find('.photo-lightbox__close-btn').trigger('click')
    await flushPromises()
    expect(wrapper.emitted('close')).toHaveLength(1)

    wrapper.unmount()
  })

  it('closes when clicking outside the photo', async () => {
    const wrapper = mountLightbox()
    await nextTick()

    await wrapper.find('.photo-lightbox__stage').trigger('click')
    await flushPromises()
    expect(wrapper.emitted('close')).toHaveLength(1)

    wrapper.unmount()
  })

  it('does not close when clicking the photo itself', async () => {
    const wrapper = mountLightbox()
    await nextTick()

    await wrapper.find('.photo-lightbox__image').trigger('click')
    expect(wrapper.emitted('close')).toBeUndefined()

    wrapper.unmount()
  })

  it('accepts origin rect for expand-from-source motion', async () => {
    const wrapper = mountLightbox({
      originRect: { top: 120, left: 80, width: 240, height: 160 },
    })
    await nextTick()

    expect(wrapper.find('.photo-lightbox').exists()).toBe(true)
    expect(wrapper.find('.photo-lightbox__image').exists()).toBe(true)
    expect(wrapper.props('originRect')).toEqual({
      top: 120,
      left: 80,
      width: 240,
      height: 160,
    })

    wrapper.unmount()
  })
})
