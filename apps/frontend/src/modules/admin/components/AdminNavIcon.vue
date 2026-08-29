<script setup lang="ts">
import type { AdminNavIcon } from '@/modules/admin/nav'

defineProps<{
  name: AdminNavIcon
}>()
</script>

<template>
  <svg class="admin-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <template v-if="name === 'dashboard'">
      <rect x="3" y="3" width="7" height="9" rx="1.5" />
      <rect x="14" y="3" width="7" height="5" rx="1.5" />
      <rect x="14" y="12" width="7" height="9" rx="1.5" />
      <rect x="3" y="16" width="7" height="5" rx="1.5" />
    </template>
    <template v-else-if="name === 'users'">
      <circle cx="9" cy="8" r="3" />
      <path d="M3 19c0-3 2.5-5 6-5s6 2 6 5" />
      <circle cx="17" cy="9" r="2.5" />
      <path d="M21 19c0-2.2-1.5-3.8-3.5-4.4" />
    </template>
    <template v-else-if="name === 'listings'">
      <rect x="4" y="4" width="16" height="16" rx="2" />
      <path d="M8 9h8" />
      <path d="M8 13h8" />
      <path d="M8 17h5" />
    </template>
    <template v-else-if="name === 'articles'">
      <path d="M6 4h9l3 3v13H6z" />
      <path d="M15 4v3h3" />
      <path d="M9 11h6" />
      <path d="M9 15h6" />
    </template>
    <template v-else-if="name === 'seo'">
      <path d="M4 18V6" />
      <path d="M4 18h16" />
      <path d="M8 14l3-5 3 3 4-6" />
    </template>
    <template v-else-if="name === 'telegram'">
      <path d="M21 5L3 12.5l6 2 2 6 3.5-4.5L18 17z" />
      <path d="M9 14.5L18 7" />
    </template>
    <template v-else-if="name === 'complaints'">
      <path d="M12 3l8 4v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V7l8-4z" />
      <path d="M12 8v5" />
      <path d="M12 16h.01" />
    </template>
    <template v-else-if="name === 'notifications'">
      <path d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9" />
      <path d="M10 20a2 2 0 0 0 4 0" />
    </template>
    <template v-else-if="name === 'tariffs'">
      <path d="M12 3v18" />
      <path d="M7 8h8a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h8" />
    </template>
    <template v-else-if="name === 'payments'">
      <rect x="3" y="6" width="18" height="12" rx="2" />
      <path d="M3 10h18" />
      <path d="M7 15h3" />
    </template>
    <template v-else-if="name === 'site'">
      <circle cx="12" cy="12" r="8" />
      <path d="M4 12h16" />
      <path d="M12 4c2.5 2.8 2.5 12.2 0 16" />
      <path d="M12 4c-2.5 2.8-2.5 12.2 0 16" />
    </template>
    <template v-else-if="name === 'info'">
      <circle cx="12" cy="12" r="8" />
      <path d="M12 10v6" />
      <path d="M12 7.5h.01" />
    </template>
    <template v-else-if="name === 'code'">
      <path d="M9 7L4 12l5 5" />
      <path d="M15 7l5 5-5 5" />
    </template>
    <template v-else-if="name === 'media'">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <circle cx="9" cy="11" r="2" />
      <path d="M3 16l5-4 3 2 4-5 6 7" />
    </template>
  </svg>
</template>

<style scoped>
.admin-icon {
  flex-shrink: 0;
  display: block;
}
</style>
