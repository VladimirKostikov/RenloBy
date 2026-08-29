<script setup lang="ts">
defineProps<{
  status: string
  label: string
}>()
</script>

<template>
  <span class="listing-status-chip" :data-status="status">
    <span class="listing-status-chip__mark" aria-hidden="true">
      <svg
        v-if="status === 'verified'"
        class="listing-status-chip__icon"
        width="12"
        height="12"
        viewBox="0 0 12 12"
        fill="none"
      >
        <path
          d="M2.2 6.2 4.8 8.8 9.8 3.2"
          stroke="currentColor"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </span>
    <span class="listing-status-chip__label">{{ label }}</span>
  </span>
</template>

<style scoped>
.listing-status-chip {
  --status-fg: #4b5563;
  --status-bg: #f3f4f6;
  --status-mark: #9ca3af;

  display: inline-flex;
  align-items: center;
  gap: 7px;
  flex-shrink: 0;
  width: max-content;
  max-width: none;
  min-height: 28px;
  padding: 0 10px 0 8px;
  border: none;
  border-radius: 8px;
  background: var(--status-bg);
  color: var(--status-fg);
  font-family: 'Open Sans', sans-serif;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.01em;
  line-height: 1;
  white-space: nowrap;
}

.listing-status-chip__mark {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 8px;
  height: 8px;
}

.listing-status-chip__mark::before {
  content: '';
  display: block;
  width: 6px;
  height: 6px;
  border-radius: 2px;
  background: var(--status-mark);
}

.listing-status-chip[data-status='verified'] .listing-status-chip__mark::before {
  display: none;
}

.listing-status-chip__icon {
  display: block;
}

.listing-status-chip__label {
  flex-shrink: 0;
  white-space: nowrap;
}

.listing-status-chip[data-status='published'] {
  --status-fg: #157a3c;
  --status-bg: #eaf7ef;
  --status-mark: #22a354;
}

.listing-status-chip[data-status='pending'] {
  --status-fg: #9a5b00;
  --status-bg: #fff4e5;
  --status-mark: #e09a2b;
}

.listing-status-chip[data-status='draft'] {
  --status-fg: #5b6472;
  --status-bg: #eef1f5;
  --status-mark: #8a94a3;
}

.listing-status-chip[data-status='archived'] {
  --status-fg: #6b7280;
  --status-bg: #f0f1f3;
  --status-mark: #9ca3af;
}

.listing-status-chip[data-status='rejected'] {
  --status-fg: #c62828;
  --status-bg: #fdeeee;
  --status-mark: #e14554;
}

.listing-status-chip[data-status='verified'] {
  --status-fg: #1f4f8a;
  --status-bg: #ebf2fb;
  --status-mark: #3b7dd8;
}

.listing-status-chip[data-status='test'] {
  --status-fg: #526074;
  --status-bg: #eef2f6;
  --status-mark: #7b8a9c;
}
</style>
