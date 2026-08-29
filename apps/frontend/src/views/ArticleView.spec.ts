import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { createRouter, createMemoryHistory } from 'vue-router'
import ArticleView from '@/views/ArticleView.vue'

vi.mock('@/api/articles', () => ({
  fetchArticle: vi.fn().mockResolvedValue({
    id: 1,
    slug: 'kak-vybrat-kvartiru-v-minske',
    title: 'Как выбрать квартиру в Минске',
    excerpt: 'Короткий анонс статьи',
    body: '## С чего начать\n\nТекст статьи.',
    category: 'guides',
    coverImage: '/uploads/articles/cover.jpg',
    media: [
      { url: '/uploads/articles/cover.jpg', type: 'image' },
      { url: '/uploads/articles/extra.jpg', type: 'image' },
    ],
    isPublished: true,
    publishedAt: '2026-06-01',
    metaTitle: null,
    metaDescription: null,
    updatedAt: '2026-06-01',
  }),
}))

vi.mock('@/modules/seo/useRoutePageSeo', () => ({
  useRoutePageSeo: vi.fn(),
}))

vi.mock('@/lib/readPrerenderPayload', () => ({
  readPrerenderPayload: vi.fn(() => null),
  clearPrerenderPayload: vi.fn(),
}))

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: {
    ru: {
      listing: { loading: 'Загрузка...' },
      articles: {
        title: 'Статьи',
        backToList: 'Все статьи',
        error: 'Ошибка',
        categories: { guides: 'Гайды' },
      },
      info: { shareLabel: 'Поделиться' },
    },
  },
})

describe('ArticleView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders compact article layout with cover and filtered gallery', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        {
          path: '/articles/:slug',
          name: 'article',
          component: ArticleView,
        },
      ],
    })
    await router.push('/articles/kak-vybrat-kvartiru-v-minske')
    await router.isReady()

    const wrapper = mount(ArticleView, {
      global: {
        plugins: [i18n, router],
        stubs: {
          SeoPageHeading: true,
          InfoPageBody: { template: '<div class="body-stub" />' },
          InfoShare: { template: '<button type="button">share</button>' },
        },
      },
    })

    await flushPromises()

    expect(wrapper.find('.article-page__title').text()).toBe('Как выбрать квартиру в Минске')
    expect(wrapper.find('.article-page__category').text()).toBe('Гайды')
    expect(wrapper.find('.article-page__excerpt').text()).toBe('Короткий анонс статьи')
    expect(wrapper.find('.article-page__cover img').attributes('src')).toBe(
      '/uploads/articles/cover.jpg',
    )
    expect(wrapper.findAll('.article-page__gallery-item')).toHaveLength(1)
    expect(wrapper.find('.article-page__back').attributes('href')).toBe('/articles')
  })
})
