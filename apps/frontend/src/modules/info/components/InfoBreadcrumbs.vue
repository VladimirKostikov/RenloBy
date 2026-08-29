<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
  currentLabel: string
}>()

const { t } = useI18n()
</script>

<template>
  <nav class="info-breadcrumbs" :aria-label="t('info.breadcrumbsLabel')">
    <a href="/" class="info-breadcrumbs__item">{{ t('info.breadcrumbHome') }}</a>
    <span class="info-breadcrumbs__sep" aria-hidden="true">/</span>
    <span class="info-breadcrumbs__item info-breadcrumbs__item--muted">{{ t('info.breadcrumbInfo') }}</span>
    <span class="info-breadcrumbs__sep" aria-hidden="true">/</span>
    <span class="info-breadcrumbs__item info-breadcrumbs__item--current">{{ currentLabel }}</span>
  </nav>
</template>

<style scoped>
.info-breadcrumbs {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 14px;
  line-height: 1.2;
}

.info-breadcrumbs__item {
  color: var(--figma-ink);
  text-decoration: none;
  transition: color 0.2s ease;
}

.info-breadcrumbs__item:hover {
  color: var(--figma-accent);
}

.info-breadcrumbs__item--muted {
  color: rgba(0, 0, 0, 0.72);
}

.info-breadcrumbs__item--current {
  font-weight: 600;
}

.info-breadcrumbs__sep {
  color: rgba(0, 0, 0, 0.35);
  font-weight: 400;
  user-select: none;
}
</style>
