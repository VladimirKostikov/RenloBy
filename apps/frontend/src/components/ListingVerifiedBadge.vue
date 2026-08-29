<script setup lang="ts">
import { useI18n } from 'vue-i18n'

withDefaults(
  defineProps<{
    compact?: boolean
  }>(),
  { compact: false },
)

const { t } = useI18n()
</script>

<template>
  <span
    class="listing-verified-badge"
    :class="{ 'listing-verified-badge--compact': compact }"
  >
    <img src="/figma/verified.svg" alt="" width="10" height="10" />
    {{ t('listing.verified') }}
  </span>
</template>

<style scoped>
.listing-verified-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 24px;
  padding: 0 10px;
  border-radius: var(--figma-radius-chip, 50px);
  background-color: #c6f5d4;
  background-color: var(--figma-verified-bg, #c6f5d4);
  color: var(--figma-verified-text, #04832a);
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
  opacity: 1;
}

.listing-verified-badge img {
  display: block;
  flex-shrink: 0;
}

.listing-verified-badge--compact {
  height: 22px;
  padding: 0 8px;
  font-size: 11px;
}
</style>
