<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { cityPath, regionPath } from '@/lib/fullPageNav'

defineProps<{
  regionSlug?: string
  regionName?: string
  citySlug?: string
  cityName?: string
  districtName?: string
}>()

const { t } = useI18n()
</script>

<template>
  <nav class="location-breadcrumbs" :aria-label="t('location.breadcrumbsLabel')">
    <a href="/" class="location-breadcrumbs__item">{{ t('location.breadcrumbHome') }}</a>

    <template v-if="regionSlug && regionName && !citySlug">
      <span class="location-breadcrumbs__sep" aria-hidden="true">/</span>
      <span class="location-breadcrumbs__item location-breadcrumbs__item--current">{{ regionName }}</span>
    </template>

    <template v-else-if="citySlug && cityName">
      <template v-if="regionSlug && regionName">
        <span class="location-breadcrumbs__sep" aria-hidden="true">/</span>
        <a :href="regionPath(regionSlug)" class="location-breadcrumbs__item">{{ regionName }}</a>
      </template>
      <span class="location-breadcrumbs__sep" aria-hidden="true">/</span>
      <a
        v-if="districtName"
        :href="cityPath(citySlug)"
        class="location-breadcrumbs__item"
      >
        {{ cityName }}
      </a>
      <span v-else class="location-breadcrumbs__item location-breadcrumbs__item--current">{{ cityName }}</span>
      <template v-if="districtName">
        <span class="location-breadcrumbs__sep" aria-hidden="true">/</span>
        <span class="location-breadcrumbs__item location-breadcrumbs__item--current">{{ districtName }}</span>
      </template>
    </template>
  </nav>
</template>

<style scoped>
.location-breadcrumbs {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 14px;
  line-height: 1.2;
}

.location-breadcrumbs__item {
  color: var(--figma-ink);
  text-decoration: none;
  transition: color 0.2s ease;
}

.location-breadcrumbs__item:hover {
  color: var(--figma-accent);
}

.location-breadcrumbs__item--current {
  font-weight: 600;
}

.location-breadcrumbs__sep {
  color: rgba(0, 0, 0, 0.35);
  font-weight: 400;
  user-select: none;
}
</style>
