<script setup lang="ts">
defineProps<{
  title: string
  variant: 'view' | 'publish' | 'archive' | 'delete' | 'seo'
  disabled?: boolean
}>()
</script>

<template>
  <button
    type="button"
    class="account-action-btn"
    :class="`account-action-btn--${variant}`"
    :title="title"
    :aria-label="title"
    :disabled="disabled"
  >
    <svg
      v-if="variant === 'view'"
      class="account-action-btn__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
      <circle cx="12" cy="12" r="3" />
    </svg>
    <svg
      v-else-if="variant === 'publish'"
      class="account-action-btn__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M12 19V5" />
      <path d="M5 12l7-7 7 7" />
    </svg>
    <svg
      v-else-if="variant === 'archive'"
      class="account-action-btn__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M3 7h18v3H3z" />
      <path d="M5 10v9h14v-9" />
      <path d="M10 14h4" />
    </svg>
    <svg
      v-else-if="variant === 'seo'"
      class="account-action-btn__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <circle cx="11" cy="11" r="7" />
      <path d="M21 21l-3.5-3.5" />
      <path d="M8 11h6" />
      <path d="M11 8v6" />
    </svg>
    <svg
      v-else
      class="account-action-btn__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M3 6h18" />
      <path d="M8 6V4h8v2" />
      <path d="M19 6l-1 14H6L5 6" />
      <path d="M10 11v6" />
      <path d="M14 11v6" />
    </svg>
  </button>
</template>

<style scoped>
.account-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  min-width: 36px;
  height: 36px;
  min-height: 36px;
  padding: 0;
  border: none;
  border-radius: 8px;
  color: var(--figma-on-accent);
  cursor: pointer;
  transition:
    transform 0.2s ease,
    filter 0.2s ease,
    box-shadow 0.2s ease,
    opacity 0.2s ease;
}

.account-action-btn__icon {
  width: 16px;
  height: 16px;
  display: block;
}

.account-action-btn--view {
  background: #2563eb;
}

.account-action-btn--seo {
  background: #0f766e;
}

.account-action-btn--publish {
  background: var(--figma-accent, #e14554);
}

.account-action-btn--archive {
  background: var(--figma-border-strong);
  color: var(--figma-ink-secondary);
}

.account-action-btn--delete {
  background: #fee2e2;
  color: var(--color-danger);
}

.account-action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.06);
  box-shadow: 0 4px 10px rgba(26, 29, 38, 0.14);
}

.account-action-btn:active:not(:disabled) {
  transform: scale(0.96);
}

.account-action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.account-action-btn:focus-visible {
  outline: 2px solid var(--figma-accent, #e14554);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .account-action-btn {
    transition: none;
  }

  .account-action-btn:hover:not(:disabled),
  .account-action-btn:active:not(:disabled) {
    transform: none;
  }
}
</style>
