<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import ComparisonTable from '@/modules/collections/components/ComparisonTable.vue'
import { COMPARISON_LIMIT } from '@/stores/comparisons'
import { useComparisonsStore } from '@/stores/comparisons'

withDefaults(
  defineProps<{
    embedded?: boolean
  }>(),
  { embedded: false },
)

const { t } = useI18n()
const comparisons = useComparisonsStore()
const { listings, loading, pageLoaded, limitReached } = storeToRefs(comparisons)

onMounted(async () => {
  await comparisons.loadPage()
})

async function removeListing(listingId: number) {
  await comparisons.removeByListingId(listingId)
}
</script>

<template>
  <div class="comparison-panel" :class="{ 'comparison-panel--embedded': embedded }">
    <header class="comparison-panel__header">
      <div class="comparison-panel__heading">
        <h1 class="comparison-panel__title">{{ t('collections.compare.title') }}</h1>
        <span v-if="listings.length > 0" class="comparison-panel__count">
          {{ listings.length }}/{{ COMPARISON_LIMIT }}
        </span>
      </div>
      <p class="comparison-panel__subtitle">
        {{ t('collections.compare.subtitle', { limit: COMPARISON_LIMIT }) }}
      </p>
      <p v-if="limitReached" class="comparison-panel__hint">{{ t('collections.compare.limitReached') }}</p>
    </header>

    <div v-if="loading && !pageLoaded" class="comparison-panel__state">
      {{ t('listing.loading') }}
    </div>

    <div v-else-if="listings.length === 0" class="comparison-panel__state">
      <p>{{ t('collections.compare.empty') }}</p>
      <a href="/" class="comparison-panel__cta">
        {{ t('collections.browseListings') }}
      </a>
    </div>

    <ComparisonTable
      v-else
      :listings="listings"
      @remove="removeListing"
    />
  </div>
</template>

<style scoped>
.comparison-panel {
  padding: 24px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  border: 1px solid var(--figma-border);
}

.comparison-panel--embedded {
  padding: 0;
  border: none;
  border-radius: 0;
  background: transparent;
}

.comparison-panel__header {
  margin-bottom: 24px;
}

.comparison-panel--embedded .comparison-panel__header {
  margin-bottom: 16px;
}

.comparison-panel__heading {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.comparison-panel__title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  line-height: 1.25;
  color: var(--figma-ink);
}

.comparison-panel--embedded .comparison-panel__title {
  font-size: 22px;
}

.comparison-panel__count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
  color: var(--figma-accent);
  font-size: 13px;
  font-weight: 600;
  line-height: 1;
}

.comparison-panel__subtitle,
.comparison-panel__hint {
  margin: 0;
  font-size: 14px;
  line-height: 1.4;
  color: var(--figma-text-muted);
}

.comparison-panel__hint {
  margin-top: 8px;
  color: var(--figma-accent);
  font-weight: 600;
}

.comparison-panel__state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  min-height: 220px;
  padding: 24px 8px;
  text-align: center;
  color: var(--figma-text-muted);
  font-size: 14px;
}

.comparison-panel--embedded .comparison-panel__state {
  min-height: 160px;
}

.comparison-panel__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 180px;
  height: 44px;
  padding: 0 18px;
  border: none;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.comparison-panel__cta:hover {
  background: var(--figma-accent-hover);
}

.comparison-panel__cta:active {
  transform: scale(0.98);
}

@media (max-width: 767px) {
  .comparison-panel:not(.comparison-panel--embedded) {
    padding: 16px;
  }

  .comparison-panel__title {
    font-size: 20px;
  }
}

@media (min-width: 1280px) {
  .comparison-panel__title {
    font-size: 28px;
  }
}
</style>
