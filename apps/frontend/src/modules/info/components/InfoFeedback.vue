<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const feedback = ref<'yes' | 'no' | null>(null)

function setFeedback(value: 'yes' | 'no') {
  feedback.value = value
}
</script>

<template>
  <div class="info-feedback">
    <p class="info-feedback__label">{{ t('info.feedbackQuestion') }}</p>
    <div class="info-feedback__actions">
      <button
        type="button"
        class="info-feedback__btn"
        :class="{ 'info-feedback__btn--active': feedback === 'yes' }"
        @click="setFeedback('yes')"
      >
        <img data-theme-ink src="/figma/thumbs-up.svg" alt="" width="18" height="18" />
        <span>{{ t('info.feedbackYes') }}</span>
      </button>
      <button
        type="button"
        class="info-feedback__btn"
        :class="{ 'info-feedback__btn--active': feedback === 'no' }"
        @click="setFeedback('no')"
      >
        <img data-theme-ink src="/figma/thumbs-down.svg" alt="" width="18" height="18" />
        <span>{{ t('info.feedbackNo') }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.info-feedback {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.info-feedback__label {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

.info-feedback__actions {
  display: flex;
  gap: 8px;
}

.info-feedback__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 25px;
  padding: 4px 10px;
  border: 1px solid var(--figma-border);
  border-radius: 4px;
  background: var(--figma-surface);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    transform 0.2s ease;
}

.info-feedback__btn:hover {
  border-color: rgba(225, 69, 84, 0.35);
}

.info-feedback__btn--active {
  border-color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.08);
  color: var(--figma-accent);
}

.info-feedback__btn:active {
  transform: scale(0.97);
}
</style>
