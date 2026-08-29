<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { buildSearchMapLocation } from '@/modules/search/lib/buildSearchRoute'

const { t } = useI18n()
const href = buildSearchMapLocation({ panel: 'extended' })
</script>

<template>
  <a :href="href" class="catalog-view-on-map">
    <svg
      class="catalog-view-on-map__icon"
      width="16"
      height="16"
      viewBox="0 0 16 16"
      fill="none"
      aria-hidden="true"
    >
      <path
        d="M8 1.75C5.652 1.75 3.75 3.652 3.75 6c0 3.188 3.438 6.938 3.875 7.4a.5.5 0 0 0 .75 0C8.812 12.938 12.25 9.188 12.25 6c0-2.348-1.902-4.25-4.25-4.25Z"
        stroke="currentColor"
        stroke-width="1.25"
      />
      <circle cx="8" cy="6" r="1.5" stroke="currentColor" stroke-width="1.25" />
    </svg>
    <span>{{ t('catalog.viewOnMap') }}</span>
  </a>
</template>

<style scoped>
.catalog-view-on-map {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  align-self: flex-start;
  gap: 8px;
  height: 40px;
  padding: 0 16px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-btn);
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background-color 0.2s ease;
}

.catalog-view-on-map:hover {
  border-color: var(--figma-accent);
  color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.06);
}

.catalog-view-on-map__icon {
  flex-shrink: 0;
  display: block;
}
</style>
