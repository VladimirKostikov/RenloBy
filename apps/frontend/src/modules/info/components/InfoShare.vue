<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  title: string
}>()

const { t } = useI18n()

async function sharePage() {
  const url = window.location.href

  if (navigator.share) {
    try {
      await navigator.share({ title: props.title, url })
      return
    } catch {
      return
    }
  }

  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(url)
  }
}
</script>

<template>
  <button type="button" class="info-share" @click="sharePage">
    <span>{{ t('info.shareLabel') }}</span>
    <img data-theme-ink src="/figma/share.svg" alt="" width="21" height="21" />
  </button>
</template>

<style scoped>
.info-share {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  background: transparent;
  padding: 4px;
  min-height: var(--touch-target-min);
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  cursor: pointer;
  transition: color 0.2s ease;
}

.info-share:hover {
  color: var(--figma-accent);
}
</style>
