import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ArticleCard from '@/modules/articles/components/ArticleCard.vue'
import { i18n } from '@/modules/locale'
import type { ArticleDto } from '@/types/article'

const article = {
  id: 1,
  slug: 'kak-vybrat-kvartiru-v-minske',
  title: 'Как выбрать квартиру в Минске',
  excerpt: 'Краткий гид',
  body: 'Текст',
  category: 'guides',
  coverImage: null,
  media: [],
  isPublished: true,
  publishedAt: '2026-06-01',
  metaTitle: null,
  metaDescription: null,
  updatedAt: '2026-06-01',
} as ArticleDto

describe('ArticleCard', () => {
  it('links to article detail page', () => {
    const wrapper = mount(ArticleCard, {
      props: { article },
      global: {
        plugins: [i18n],
        stubs: { RouterLink: { template: '<a :href="to"><slot /></a>', props: ['to'] } },
      },
    })

    expect(wrapper.attributes('href')).toBe('/articles/kak-vybrat-kvartiru-v-minske')
    expect(wrapper.find('.article-card__title').text()).toBe('Как выбрать квартиру в Минске')
  })
})
