import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ArticleMediaEditor from '@/modules/articles/components/ArticleMediaEditor.vue'
import ru from '@/locales/ru.json'

vi.mock('@/api/adminMedia', () => ({
  uploadAdminMedia: vi.fn(async () => ({
    url: '/uploads/articles/2026/07/test.jpg',
    type: 'image',
    mimeType: 'image/jpeg',
    size: 12,
  })),
}))

describe('ArticleMediaEditor', () => {
  it('renders cover and media upload controls', () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ArticleMediaEditor, {
      props: {
        coverImage: 'https://images.unsplash.com/photo.jpg',
        media: [{ url: 'https://images.unsplash.com/gallery.jpg', type: 'image' }],
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Обложка')
    expect(wrapper.text()).toContain('Мультимедиа')
    expect(wrapper.findAll('img')).toHaveLength(2)
    expect(wrapper.find('input[type="file"]').exists()).toBe(true)
  })
})
