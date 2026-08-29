import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import { seoMetaListToMap } from '@/modules/seo/seoOverrides'
import { buildPageMeta } from '@/modules/seo/buildPageMeta'

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: {
    ru: {
      admin: {
        save: 'Сохранить',
        cancel: 'Отмена',
        fields: { isTest: 'Тестовые данные' },
        wysiwyg: {
          bold: 'Жирный',
          italic: 'Курсив',
          heading: 'Заголовок',
          paragraph: 'Абзац',
          list: 'Список',
          undo: 'Отменить',
        },
      },
    },
  },
})

describe('AdminModal', () => {
  it('renders panel when open', async () => {
    const wrapper = mount(AdminModal, {
      props: { open: true, title: 'Форма' },
      slots: { default: '<p>content</p>' },
      global: { plugins: [i18n] },
      attachTo: document.body,
    })

    expect(document.body.querySelector('.admin-modal__title')?.textContent).toContain('Форма')
    expect(document.body.textContent).toContain('content')
    wrapper.unmount()
  })
})

describe('AdminCrudForm', () => {
  it('emits save with field values and isTest', async () => {
    const wrapper = mount(AdminCrudForm, {
      props: {
        fields: [
          { key: 'name', label: 'Name' },
          { key: 'slug', label: 'Slug' },
        ],
        modelValue: { name: 'Minsk', slug: 'minsk', isTest: true },
      },
      global: { plugins: [i18n, createPinia()] },
    })

    await wrapper.find('form').trigger('submit')
    expect(wrapper.emitted('save')?.[0]?.[0]).toEqual({ name: 'Minsk', slug: 'minsk', isTest: true })
  })
})

describe('seo overrides', () => {
  it('maps seo meta list to page keys', () => {
    const map = seoMetaListToMap([
      {
        id: 1,
        pageKey: 'home',
        locale: 'ru',
        title: 'Custom home',
        description: 'Custom desc',
        h1: 'Custom h1',
      },
    ])

    expect(map.home.title).toBe('Custom home')
    expect(map.home.h1).toBe('Custom h1')
  })

  it('applies seo overrides in buildPageMeta', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/',
      siteUrl: 'https://renlo.local',
      seoOverrides: {
        home: {
          title: 'Override title',
          description: 'Override description',
          h1: 'Override h1',
        },
      },
    })

    expect(meta.title).toContain('Override title')
    expect(meta.description).toContain('Override description')
  })

  it('uses info page meta title and description when provided', () => {
    const meta = buildPageMeta({
      locale: 'ru',
      path: '/info/deal-safety',
      siteUrl: 'https://renlo.local',
      infoPage: {
        slug: 'deal-safety',
        title: 'Safety',
        body: 'Body',
        metaTitle: 'Custom SEO title',
        metaDescription: 'Custom SEO description',
      },
    })

    expect(meta.title).toContain('Custom SEO title')
    expect(meta.description).toContain('Custom SEO description')
  })
})
