<script setup lang="ts">
import ComparisonPanel from '@/modules/collections/components/ComparisonPanel.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

useRoutePageSeo({ noindex: true })
</script>

<template>
  <div class="compare-page">
    <main class="compare-page__main page-shell">
      <ComparisonPanel />
    </main>
  </div>
</template>

<style scoped>
.compare-page {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  background: var(--figma-surface);
}

.compare-page__main {
  flex: 1;
  padding-top: 20px;
  padding-bottom: 40px;
  background: var(--figma-page-bg);
}
</style>
