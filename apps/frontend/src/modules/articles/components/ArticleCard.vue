<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'
import type { ArticleDto } from '@/types/article'

const props = defineProps<{
  article: ArticleDto
}>()

const { t } = useI18n()

const categoryLabel = computed(() => t(`articles.categories.${props.article.category}`))
const coverSrc = computed(() => {
  const cover = props.article.coverImage
  return cover && isSafeMediaUrl(cover) ? cover : null
})
</script>

<template>
  <a :href="`/articles/${article.slug}`" class="article-card">
    <div class="article-card__cover">
      <img v-if="coverSrc" :src="coverSrc" :alt="article.title" />
    </div>
    <div class="article-card__body">
      <div class="article-card__meta">
        <span class="article-card__category">{{ categoryLabel }}</span>
        <time class="article-card__date" :datetime="article.publishedAt">{{ article.publishedAt }}</time>
      </div>
      <h2 class="article-card__title">{{ article.title }}</h2>
      <p class="article-card__excerpt">{{ article.excerpt }}</p>
      <span class="article-card__more">{{ t('articles.readMore') }}</span>
    </div>
  </a>
</template>

<style scoped>
.article-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-width: 0;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-catalog-card-radius, 20px);
  background: var(--figma-surface);
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition:
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.article-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

.article-card__cover {
  height: 180px;
  overflow: hidden;
  background: var(--figma-page-bg);
}

.article-card__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-card__body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
  padding: 20px 22px 22px;
}

.article-card__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.article-card__category {
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-accent);
}

.article-card__date {
  font-size: 12px;
  color: var(--figma-text-muted);
}

.article-card__title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  line-height: 1.3;
  color: var(--figma-ink);
}

.article-card__excerpt {
  margin: 0;
  flex: 1;
  font-size: 14px;
  line-height: 1.45;
  color: var(--figma-text-muted);
}

.article-card__more {
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
}
</style>
