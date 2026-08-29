<script setup lang="ts">
import FavoritesPanel from '@/modules/collections/components/FavoritesPanel.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

useRoutePageSeo({ noindex: true })
</script>

<template>
  <div class="favorites-page">
    <main class="favorites-page__main page-shell">

      <FavoritesPanel />
    </main>
  </div>
</template>

<style scoped>
.favorites-page {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  background: var(--figma-surface);
}

.favorites-page__main {
  flex: 1;
  padding-top: 20px;
  padding-bottom: 40px;
  background: var(--figma-page-bg);
}
</style>
