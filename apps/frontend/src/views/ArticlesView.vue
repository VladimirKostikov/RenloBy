<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchArticles } from '@/api/articles'
import ArticleCard from '@/modules/articles/components/ArticleCard.vue'
import SeoPageHeading from '@/modules/seo/components/SeoPageHeading.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import type { ArticleDto } from '@/types/article'

const { t } = useI18n()
const articles = ref<ArticleDto[]>([])
const loading = ref(true)
const error = ref(false)

useRoutePageSeo()

onMounted(async () => {
  try {
    articles.value = await fetchArticles()
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="articles-page">
    <SeoPageHeading :title="t('articles.title')" />

    <main class="articles-page__main page-shell">
      <header class="articles-page__header">
        <h1 class="articles-page__title">{{ t('articles.title') }}</h1>
        <p class="articles-page__subtitle">{{ t('articles.subtitle') }}</p>
      </header>

      <div v-if="loading" class="articles-page__state">{{ t('listing.loading') }}</div>
      <div v-else-if="error" class="articles-page__state">{{ t('articles.error') }}</div>
      <div v-else-if="articles.length === 0" class="articles-page__state">{{ t('articles.empty') }}</div>
      <div v-else class="articles-page__grid">
        <ArticleCard v-for="article in articles" :key="article.id" :article="article" />
      </div>
    </main>
  </div>
</template>

<style scoped>
.articles-page {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  background: var(--figma-surface);
}

.articles-page__main {
  flex: 1;
  padding-top: 20px;
  padding-bottom: 40px;
  background: var(--figma-page-bg);
}

.articles-page__header {
  margin-bottom: 24px;
  padding: 24px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
}

.articles-page__title {
  margin: 0 0 8px;
  font-size: 24px;
  font-weight: 600;
  line-height: 1.25;
  color: var(--figma-ink);
}

.articles-page__subtitle {
  margin: 0;
  font-size: 14px;
  line-height: 1.4;
  color: var(--figma-text-muted);
}

.articles-page__state {
  display: grid;
  place-items: center;
  min-height: 220px;
  padding: 24px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  color: var(--figma-text-muted);
  font-size: 14px;
}

.articles-page__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

@media (min-width: 768px) {
  .articles-page__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
  }
}

@media (min-width: 1280px) {
  .articles-page__title {
    font-size: 28px;
  }

  .articles-page__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
  }
}
</style>
