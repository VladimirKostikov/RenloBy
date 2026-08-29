import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import ArticlesAdminView from '@/views/admin/ArticlesAdminView.vue'

vi.mock('@/api/admin', () => ({
  adminArticles: {
    list: vi.fn().mockResolvedValue([
      {
        id: 1,
        slug: 'test-article',
        title: 'Тест',
        excerpt: 'e',
        body: 'b',
        category: 'guides',
        coverImage: null,
        media: [],
        isPublished: true,
        publishedAt: '2026-07-01',
        metaTitle: null,
        metaDescription: null,
        updatedAt: '2026-07-01',
        isTest: true,
      },
    ]),
    create: vi.fn().mockResolvedValue({}),
    update: vi.fn().mockResolvedValue({}),
    remove: vi.fn().mockResolvedValue(undefined),
  },
}))

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: {
    ru: {
      admin: {
        articles: 'Статьи',
        create: 'Создать',
        edit: 'Редактировать',
        confirmDelete: 'Удалить?',
        fields: {
          id: 'ID',
          slug: 'Slug',
          title: 'Заголовок',
          category: 'Категория',
          isPublished: 'Опубликовано',
          excerpt: 'Анонс',
          body: 'Текст',
          publishedAt: 'Дата',
          metaTitle: 'Meta title',
          metaDescription: 'Meta description',
          isTest: 'Тест',
        },
      },
    },
  },
})

describe('ArticlesAdminView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('opens create form with isTest enabled by default', async () => {
    const wrapper = mount(ArticlesAdminView, {
      global: {
        plugins: [i18n],
        stubs: {
          AdminPageHeader: { template: '<div><slot name="actions" /></div>' },
          AdminCrudTable: true,
          AdminModal: { props: ['open'], template: '<div v-if="open"><slot /></div>' },
          AdminCrudForm: true,
          AdminConfirmDialog: true,
          ArticleMediaEditor: true,
        },
      },
    })

    await wrapper.vm.$nextTick()
    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    const form = wrapper.findComponent({ name: 'AdminCrudForm' })
    expect(form.exists()).toBe(true)
    expect(form.props('modelValue').isTest).toBe(true)
  })
})
