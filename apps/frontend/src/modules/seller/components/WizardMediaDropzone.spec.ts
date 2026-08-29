import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import WizardMediaDropzone from '@/modules/seller/components/WizardMediaDropzone.vue'
import ru from '@/locales/ru.json'

const uploadListingMedia = vi.fn()

vi.mock('@/api/account', () => ({
  ListingMediaTooLargeError: class ListingMediaTooLargeError extends Error {
    constructor() {
      super('validation.media_file_too_large')
      this.name = 'ListingMediaTooLargeError'
    }
  },
  uploadListingMedia: (...args: unknown[]) => uploadListingMedia(...args),
}))

describe('WizardMediaDropzone', () => {
  beforeEach(() => {
    uploadListingMedia.mockReset()
  })

  it('uploads dropped files and emits image urls', async () => {
    uploadListingMedia.mockResolvedValue({
      url: '/uploads/listings/2026/07/a.jpg',
      type: 'image',
      mimeType: 'image/jpeg',
      size: 1200,
    })

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardMediaDropzone, {
      props: { images: [] },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Перетащите фото сюда')
    expect(wrapper.text()).toContain('До 10 фото')
    expect(wrapper.text()).toContain('до 15 МБ')

    const file = new File([new Uint8Array(12)], 'photo.jpg', { type: 'image/jpeg' })
    await wrapper.get('.wizard-media-dropzone__zone').trigger('drop', {
      dataTransfer: { files: [file] },
      preventDefault: () => undefined,
    })
    await flushPromises()

    expect(uploadListingMedia).toHaveBeenCalledTimes(1)
    expect(wrapper.emitted('update:images')?.[0]?.[0]).toEqual(['/uploads/listings/2026/07/a.jpg'])
  })

  it('shows error for oversized files without calling api', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardMediaDropzone, {
      props: { images: [] },
      global: { plugins: [i18n] },
    })

    const big = new File(['big'], 'big.jpg', { type: 'image/jpeg' })
    Object.defineProperty(big, 'size', { value: 15 * 1024 * 1024 + 1 })
    await wrapper.get('.wizard-media-dropzone__zone').trigger('drop', {
      dataTransfer: { files: [big] },
      preventDefault: () => undefined,
    })
    await flushPromises()

    expect(uploadListingMedia).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Файл больше 15 МБ')
  })

  it('stops uploading after 10 photos', async () => {
    uploadListingMedia.mockResolvedValue({
      url: '/uploads/listings/2026/07/a.jpg',
      type: 'image',
      mimeType: 'image/jpeg',
      size: 1200,
    })

    const existing = Array.from({ length: 10 }, (_, index) => `/uploads/listings/2026/07/${index}.jpg`)
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardMediaDropzone, {
      props: { images: existing },
      global: { plugins: [i18n] },
    })

    const file = new File([new Uint8Array(12)], 'extra.jpg', { type: 'image/jpeg' })
    await wrapper.get('.wizard-media-dropzone__zone').trigger('drop', {
      dataTransfer: { files: [file] },
      preventDefault: () => undefined,
    })
    await flushPromises()

    expect(uploadListingMedia).not.toHaveBeenCalled()
    expect(wrapper.emitted('update:images')?.[0]?.[0]).toEqual(existing)
  })
})
