<script setup lang="ts">
defineProps<{
  title: string
  variant: 'view' | 'edit' | 'delete'
}>()
</script>

<template>
  <button
    type="button"
    class="admin-action-btn"
    :class="`admin-action-btn--${variant}`"
    :title="title"
    :aria-label="title"
  >
    <svg
      v-if="variant === 'view'"
      class="admin-action-btn__icon"
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
      v-else-if="variant === 'edit'"
      class="admin-action-btn__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M12 20h9" />
      <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
    </svg>
    <svg
      v-else
      class="admin-action-btn__icon"
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
    <span class="admin-action-btn__text">{{ title }}</span>
  </button>
</template>

<style scoped>
.admin-action-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  padding: 0;
  border: 1px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  transition:
    transform 160ms ease,
    filter 160ms ease,
    box-shadow 160ms ease,
    background-color 160ms ease,
    border-color 160ms ease;
}

.admin-action-btn__icon {
  width: 16px;
  height: 16px;
  display: block;
}

.admin-action-btn--view {
  color: #334155;
  background: #edf2f7;
  border-color: #d7dee8;
}

.admin-action-btn--edit {
  color: #0f766e;
  background: #e6f4f1;
  border-color: #b7ddd6;
}

.admin-action-btn--delete {
  color: var(--admin-accent, #e14554);
  background: #fdecee;
  border-color: #f3c5cb;
}

.admin-action-btn:hover {
  transform: translateY(-1px);
  filter: brightness(0.98);
  box-shadow: 0 4px 10px rgba(26, 29, 38, 0.12);
}

.admin-action-btn--view:hover {
  background: #e2e8f0;
}

.admin-action-btn--edit:hover {
  background: #d7eee9;
}

.admin-action-btn--delete:hover {
  background: #f8dde1;
}

.admin-action-btn:active {
  transform: scale(0.96);
  filter: brightness(0.96);
}

.admin-action-btn:focus-visible {
  outline: 2px solid #1a1d26;
  outline-offset: 2px;
}

.admin-action-btn__text {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

@media (prefers-reduced-motion: reduce) {
  .admin-action-btn {
    transition: none;
  }

  .admin-action-btn:hover,
  .admin-action-btn:active {
    transform: none;
  }
}
</style>
