<script setup lang="ts">
import PromotionPanel from '@/modules/promotion/components/PromotionPanel.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

useRoutePageSeo({ noindex: true })
</script>

<template>
  <div class="promotion-payment">
    <main class="promotion-payment__main page-shell">
      <PromotionPanel />
    </main>
  </div>
</template>

<style scoped>
.promotion-payment {
  min-height: 100%;
  background: var(--figma-page-bg);
}

.promotion-payment__main {
  padding-top: 20px;
  padding-bottom: 40px;
}
</style>
