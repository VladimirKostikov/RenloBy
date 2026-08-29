<script setup lang="ts">
defineProps<{
  text: string
}>()
</script>

<template>
  <span class="currency-text">
    <Transition name="currency-text" mode="out-in">
      <span :key="text" class="currency-text__value">{{ text }}</span>
    </Transition>
  </span>
</template>

<style scoped>
.currency-text {
  display: inline-block;
  vertical-align: baseline;
}

.currency-text__value {
  display: inline-block;
}

.currency-text-enter-active,
.currency-text-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.currency-text-enter-from {
  opacity: 0;
  transform: translateY(4px);
}

.currency-text-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (prefers-reduced-motion: reduce) {
  .currency-text-enter-active,
  .currency-text-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
