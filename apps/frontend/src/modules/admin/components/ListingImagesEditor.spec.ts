import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingImagesEditor from '@/modules/admin/components/ListingImagesEditor.vue'
import ru from '@/locales/ru.json'

const uploadMock = vi.fn(async () => ({
  url: '/uploads/listings/2026/07/new.jpg',
  type: 'image',
  mimeType: 'image/jpeg',
  size: 12,
}))

vi.mock('@/api/adminMedia', () => ({
  uploadAdminMedia: (...args: unknown[]) => uploadMock(...args),
  MediaFileTooLargeError: class MediaFileTooLargeError extends Error {},
}))

describe('ListingImagesEditor', () => {
  it('renders images and uploads a new photo', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingImagesEditor, {
      props: {
        images: ['https://example.com/a.jpg'],
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Фото')
    expect(wrapper.find('img').attributes('src')).toBe('https://example.com/a.jpg')

    const input = wrapper.get('input[type="file"]')
    const file = new File(['x'], 'new.jpg', { type: 'image/jpeg' })
    Object.defineProperty(input.element, 'files', {
      value: [file],
      configurable: true,
    })
    await input.trigger('change')
    await wrapper.vm.$nextTick()

    expect(uploadMock).toHaveBeenCalled()
    expect(wrapper.emitted('update:images')?.[0]?.[0]).toEqual([
      'https://example.com/a.jpg',
      '/uploads/listings/2026/07/new.jpg',
    ])
  })

  it('removes an image', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingImagesEditor, {
      props: {
        images: ['https://example.com/a.jpg', 'https://example.com/b.jpg'],
      },
      global: { plugins: [i18n] },
    })

    await wrapper.findAll('.listing-images-editor__remove')[0].trigger('click')
    expect(wrapper.emitted('update:images')?.[0]?.[0]).toEqual(['https://example.com/b.jpg'])
  })
})
