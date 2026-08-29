<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import {
  resolveCharacteristicText,
  type ListingCharacteristicRow,
} from '@/lib/listingDetailExtras'

defineProps<{
  rows: ListingCharacteristicRow[]
}>()

const emit = defineEmits<{
  close: []
}>()

const { t } = useI18n()
</script>

<template>
  <div class="listing-chars-modal" role="dialog" aria-modal="true" @click.self="emit('close')">
    <div class="listing-chars-modal__card">
      <div class="listing-chars-modal__head">
        <h2>{{ t('listingDetail.characteristics') }}</h2>
        <button
          type="button"
          class="listing-chars-modal__close"
          :aria-label="t('listingDetail.close')"
          @click="emit('close')"
        >
          ×
        </button>
      </div>
      <dl class="listing-chars-modal__list">
        <div v-for="row in rows" :key="row.label" class="listing-chars-modal__row">
          <dt>{{ t(row.label) }}</dt>
          <dd>{{ resolveCharacteristicText(row.value, t) }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<style scoped>
.listing-chars-modal {
  position: fixed;
  inset: 0;
  z-index: 2500;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.45);
}

.listing-chars-modal__card {
  width: min(440px, 100%);
  max-height: min(80vh, 640px);
  display: flex;
  flex-direction: column;
  padding: 20px;
  border-radius: 16px;
  background: var(--figma-surface);
  box-shadow: 0 20px 48px rgba(0, 0, 0, 0.2);
}

.listing-chars-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  flex-shrink: 0;
}

.listing-chars-modal__head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--figma-ink);
}

.listing-chars-modal__close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: rgba(146, 146, 146, 0.12);
  color: var(--figma-ink);
  font-size: 20px;
  cursor: pointer;
}

.listing-chars-modal__list {
  margin: 0;
  overflow-y: auto;
  padding-right: 4px;
}

.listing-chars-modal__row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 16px;
  padding: 10px 0;
  border-bottom: 1px solid rgba(146, 146, 146, 0.16);
}

.listing-chars-modal__row:last-child {
  border-bottom: none;
}

.listing-chars-modal__row dt {
  margin: 0;
  font-size: 14px;
  color: var(--figma-text-muted);
}

.listing-chars-modal__row dd {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  text-align: right;
}
</style>
