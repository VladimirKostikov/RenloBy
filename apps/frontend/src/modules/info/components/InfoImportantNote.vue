<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
  text: string
}>()

const { t } = useI18n()
</script>

<template>
  <aside class="info-important">
    <img src="/figma/warning.svg" alt="" class="info-important__icon" width="47" height="54" />
    <div class="info-important__content">
      <h3 class="info-important__title">{{ t('info.importantTitle') }}</h3>
      <p class="info-important__text">{{ text }}</p>
    </div>
  </aside>
</template>

<style scoped>
.info-important {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 28px;
  padding: 20px 24px;
  border-radius: var(--figma-radius-chip);
  background: rgba(225, 69, 84, 0.06);
  border: 1px solid rgba(225, 69, 84, 0.15);
}

.info-important__icon {
  flex-shrink: 0;
}

.info-important__title {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.info-important__text {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  color: rgba(0, 0, 0, 0.78);
}

@media (max-width: 767px) {
  .info-important {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
}
</style>
