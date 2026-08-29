<script setup lang="ts">
import type { InfoPageCategory } from '@/types/info'

defineProps<{
  name: InfoPageCategory | 'info'
}>()
</script>

<template>
  <svg
    class="info-nav-icon"
    width="20"
    height="20"
    viewBox="0 0 20 20"
    fill="none"
    aria-hidden="true"
  >
    <template v-if="name === 'info'">
      <circle cx="10" cy="10" r="7.25" stroke="currentColor" stroke-width="1.5" />
      <path d="M10 9V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      <circle cx="10" cy="6.5" r="1" fill="currentColor" />
    </template>

    <template v-else-if="name === 'buyers'">
      <path
        d="M4.5 6.5h11l-1 8.25a1.5 1.5 0 0 1-1.5 1.25H7a1.5 1.5 0 0 1-1.5-1.25L4.5 6.5Z"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linejoin="round"
      />
      <path d="M7.5 8.5V5.75a2.5 2.5 0 0 1 5 0V8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'sellers'">
      <path
        d="M3.5 8.5 10 3.75 16.5 8.5V15.5a1 1 0 0 1-1 1h-3.25v-4.25h-4.5V16.5H4.5a1 1 0 0 1-1-1V8.5Z"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linejoin="round"
      />
    </template>

    <template v-else-if="name === 'renters'">
      <path
        d="M7.5 10.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z"
        stroke="currentColor"
        stroke-width="1.5"
      />
      <path
        d="M9.5 9.5 14.75 4.25M13.25 4.25h1.5v1.5"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      <path d="M4.5 16.25v-1a3 3 0 0 1 3-3h1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    </template>

    <template v-else-if="name === 'deal_safety'">
      <path
        d="M10 2.75 15.25 5v4.25c0 3.5-2.2 5.9-5.25 7-3.05-1.1-5.25-3.5-5.25-7V5L10 2.75Z"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linejoin="round"
      />
      <path d="M7.75 9.75 9.25 11.25 12.5 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    </template>

    <template v-else-if="name === 'faq'">
      <circle cx="10" cy="10" r="7.25" stroke="currentColor" stroke-width="1.5" />
      <path
        d="M8.1 7.9a2 2 0 0 1 3.75.9c0 1.2-1.85 1.8-1.85 2.9"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linecap="round"
      />
      <circle cx="10" cy="14.25" r="0.9" fill="currentColor" />
    </template>

    <template v-else>
      <circle cx="10" cy="10" r="7.25" stroke="currentColor" stroke-width="1.5" />
      <path d="M10 6.5V11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      <path d="M7.5 11h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      <path d="M8.5 13.5h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    </template>
  </svg>
</template>

<style scoped>
.info-nav-icon {
  display: block;
  flex-shrink: 0;
}
</style>
