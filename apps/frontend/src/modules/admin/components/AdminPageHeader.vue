<script setup lang="ts">
defineProps<{
  title: string
}>()
</script>

<template>
  <div class="admin-page-header">
    <h1 class="admin-page-header__title">{{ title }}</h1>
    <div class="admin-page-header__actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>
.admin-page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}

.admin-page-header__title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--admin-text, #1a1d26);
}

.admin-page-header__actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
