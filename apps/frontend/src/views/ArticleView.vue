<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { fetchArticle } from '@/api/articles'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'
import { articleGalleryItems, formatArticleDate } from '@/modules/articles/lib/articlePage'
import InfoPageBody from '@/modules/info/components/InfoPageBody.vue'
import InfoShare from '@/modules/info/components/InfoShare.vue'
import SeoPageHeading from '@/modules/seo/components/SeoPageHeading.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import { clearPrerenderPayload, readPrerenderPayload } from '@/lib/readPrerenderPayload'
import type { PrerenderArticlePayload } from '@/modules/seo/buildPrerenderBody'
import type { ArticleDto, ArticleMediaItem, ArticleMediaType } from '@/types/article'

function isArticleMediaType(type: string): type is ArticleMediaType {
  return type === 'image' || type === 'video'
}

const route = useRoute()
const { t, locale } = useI18n()

const article = ref<ArticleDto | null>(null)
const loading = ref(true)
const error = ref(false)

const slug = computed(() => String(route.params.slug ?? ''))

const articleSeoContext = computed(() => {
  if (!article.value) {
    return null
  }

  return {
    slug: article.value.slug,
    title: article.value.title,
    excerpt: article.value.excerpt,
    body: article.value.body,
    metaTitle: article.value.metaTitle,
    metaDescription: article.value.metaDescription,
    coverImage: article.value.coverImage,
    publishedAt: article.value.publishedAt,
  }
})

const coverSrc = computed(() => {
  const cover = article.value?.coverImage
  return cover && isSafeMediaUrl(cover) ? cover : null
})

const gallery = computed(() =>
  articleGalleryItems(article.value?.media, article.value?.coverImage),
)

const publishedLabel = computed(() => {
  if (!article.value) {
    return ''
  }
  return formatArticleDate(article.value.publishedAt, locale.value)
})

useRoutePageSeo({ article: articleSeoContext })

async function loadArticle(targetSlug: string) {
  error.value = false

  const prerender = readPrerenderPayload<PrerenderArticlePayload>()
  if (prerender?.kind === 'article' && prerender.article.slug === targetSlug) {
    article.value = {
      id: 0,
      slug: prerender.article.slug,
      title: prerender.article.title,
      excerpt: prerender.article.excerpt,
      body: prerender.article.body,
      category: 'guides',
      coverImage: prerender.article.coverImage ?? null,
      media: (prerender.article.media ?? []).filter(
        (item): item is ArticleMediaItem => isArticleMediaType(item.type),
      ),
      isPublished: true,
      publishedAt: prerender.article.publishedAt,
      metaTitle: null,
      metaDescription: null,
      updatedAt: prerender.article.publishedAt,
    }
    loading.value = false
  } else {
    loading.value = true
  }

  try {
    article.value = await fetchArticle(targetSlug)
    clearPrerenderPayload()
  } catch {
    if (!article.value) {
      error.value = true
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadArticle(slug.value)
})

watch(slug, (value) => {
  void loadArticle(value)
})
</script>

<template>
  <div class="article-page">
    <SeoPageHeading :title="article?.title ?? t('articles.title')" />

    <main class="article-page__main page-shell">
      <div class="article-page__column">
        <a href="/articles" class="article-page__back">
          <span class="article-page__back-icon" aria-hidden="true">←</span>
          {{ t('articles.backToList') }}
        </a>

        <div v-if="loading && !article" class="article-page__state">{{ t('listing.loading') }}</div>
        <div v-else-if="error" class="article-page__state">{{ t('articles.error') }}</div>

        <article v-else-if="article" class="article-page__article">
          <header class="article-page__header">
            <div class="article-page__meta">
              <span class="article-page__category">{{ t(`articles.categories.${article.category}`) }}</span>
              <time class="article-page__date" :datetime="article.publishedAt">{{ publishedLabel }}</time>
            </div>
            <h1 class="article-page__title">{{ article.title }}</h1>
            <p v-if="article.excerpt" class="article-page__excerpt">{{ article.excerpt }}</p>
          </header>

          <figure v-if="coverSrc" class="article-page__cover">
            <img :src="coverSrc" :alt="article.title" />
          </figure>

          <div class="article-page__body">
            <InfoPageBody :body="article.body" />
          </div>

          <div v-if="gallery.length" class="article-page__gallery">
            <div
              v-for="(item, index) in gallery"
              :key="`${item.url}-${index}`"
              class="article-page__gallery-item"
            >
              <img
                v-if="item.type === 'image'"
                :src="item.url"
                :alt="article.title"
                loading="lazy"
              />
              <video
                v-else-if="item.type === 'video'"
                :src="item.url"
                controls
                playsinline
                preload="metadata"
              />
            </div>
          </div>

          <footer class="article-page__footer">
            <a href="/articles" class="article-page__footer-link">{{ t('articles.backToList') }}</a>
            <InfoShare :title="article.title" />
          </footer>
        </article>
      </div>
    </main>
  </div>
</template>

<style scoped>
.article-page {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  background: var(--figma-page-bg, #f6f6f6);
}

.article-page__main {
  flex: 1;
  padding-top: 16px;
  padding-bottom: max(32px, env(safe-area-inset-bottom, 0px));
}

.article-page__column {
  width: 100%;
  max-width: 720px;
  margin: 0 auto;
}

.article-page__back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.72);
  text-decoration: none;
  transition: color 0.2s ease;
}

.article-page__back:hover {
  color: var(--figma-accent);
}

.article-page__back-icon {
  font-size: 16px;
  line-height: 1;
}

.article-page__state {
  display: grid;
  place-items: center;
  min-height: 200px;
  padding: 24px;
  border-radius: 16px;
  background: #fff;
  color: var(--figma-text-muted, rgba(0, 0, 0, 0.55));
  font-size: 14px;
}

.article-page__article {
  padding: 20px 18px 24px;
  border-radius: 16px;
  background: #fff;
  animation: article-page-in 0.28s ease-out;
}

.article-page__header {
  margin-bottom: 16px;
}

.article-page__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
  margin-bottom: 10px;
}

.article-page__category {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(225, 69, 84, 0.1);
  background: color-mix(in srgb, var(--figma-accent) 12%, #fff);
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-accent);
}

.article-page__date {
  font-size: 13px;
  color: var(--figma-text-muted, rgba(0, 0, 0, 0.55));
}

.article-page__title {
  margin: 0 0 8px;
  font-size: 22px;
  font-weight: 600;
  line-height: 1.25;
  letter-spacing: -0.02em;
  color: #000;
}

.article-page__excerpt {
  margin: 0;
  font-size: 15px;
  line-height: 1.45;
  color: rgba(0, 0, 0, 0.62);
}

.article-page__cover {
  margin: 0 0 18px;
  overflow: hidden;
  border-radius: 12px;
  background: var(--figma-page-bg, #f0f0f0);
  aspect-ratio: 16 / 9;
  max-height: 280px;
}

.article-page__cover img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-page__body :deep(.info-page-body) {
  gap: 12px;
  margin-bottom: 0;
  font-size: 15px;
  line-height: 1.6;
  color: rgba(0, 0, 0, 0.82);
}

.article-page__body :deep(.info-page-body__heading) {
  margin: 18px 0 2px;
  font-size: 17px;
  line-height: 1.3;
}

.article-page__body :deep(.info-page-body__heading:first-child) {
  margin-top: 0;
}

.article-page__body :deep(.info-page-body__list) {
  gap: 6px;
  padding-left: 18px;
}

.article-page__gallery {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  margin-top: 20px;
}

.article-page__gallery-item {
  overflow: hidden;
  border-radius: 10px;
  background: var(--figma-page-bg, #f0f0f0);
  aspect-ratio: 4 / 3;
}

.article-page__gallery-item img,
.article-page__gallery-item video {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-page__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--figma-border, #e8e8e8);
}

.article-page__footer-link {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  font-size: 14px;
  font-weight: 600;
  color: #000;
  text-decoration: none;
  transition: color 0.2s ease;
}

.article-page__footer-link:hover {
  color: var(--figma-accent);
}

@keyframes article-page-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (min-width: 768px) {
  .article-page__main {
    padding-top: 24px;
    padding-bottom: 48px;
  }

  .article-page__article {
    padding: 28px 32px 32px;
  }

  .article-page__title {
    font-size: 26px;
  }

  .article-page__cover {
    max-height: 300px;
    border-radius: 14px;
  }

  .article-page__gallery {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (prefers-reduced-motion: reduce) {
  .article-page__article {
    animation: none;
  }
}
</style>
