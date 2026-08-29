<script setup lang="ts">
defineProps<{
  title: string
}>()
</script>

<template>
  <h1 class="seo-page-heading">{{ title }}</h1>
</template>

<style scoped>
.seo-page-heading {
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
</style>
