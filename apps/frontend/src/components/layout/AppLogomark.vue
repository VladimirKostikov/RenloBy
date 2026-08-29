<script setup lang="ts">
defineProps<{
  width?: number
  height?: number
  imageClass?: string
}>()
</script>

<template>
  <svg
    class="app-logomark"
    :class="imageClass"
    :width="width ?? 48"
    :height="height ?? 48"
    viewBox="0 0 48 48"
    fill="none"
    role="img"
    aria-hidden="true"
  >
    <rect width="48" height="48" rx="11" fill="currentColor" />
    <path
      fill="#FFFFFF"
      d="M13 11.5h8.1c5.4 0 8.7 2.8 8.7 7.2 0 3.4-1.7 5.6-4.5 6.6l6.1 11.2h-7.2l-5.5-10.1H16.8v10.1H13V11.5zm3.6 3.6v7.4h3.4c2.6 0 4.1-1.3 4.1-3.7 0-2.5-1.5-3.7-4.1-3.7h-3.4z"
    />
    <circle cx="35.5" cy="35.5" r="3.25" fill="#FFFFFF" fill-opacity="0.95" />
    <circle cx="35.5" cy="35.5" r="1.5" fill="currentColor" />
  </svg>
</template>

<style scoped>
.app-logomark {
  color: var(--figma-accent, #e14554);
  flex-shrink: 0;
}
</style>
