<script setup lang="ts">
withDefaults(
  defineProps<{
    show?: boolean
    label?: string
  }>(),
  {
    show: false,
    label: '',
  },
)
</script>

<template>
  <Transition name="container-preloader">
    <div
      v-if="show"
      class="container-preloader"
      aria-busy="true"
      aria-live="polite"
    >
      <div class="container-preloader__spinner" aria-hidden="true" />
      <span v-if="label" class="container-preloader__label">{{ label }}</span>
    </div>
  </Transition>
</template>

<style scoped>
.container-preloader {
  position: absolute;
  inset: 0;
  z-index: 5;
  display: grid;
  place-items: center;
  gap: 12px;
  padding: 24px;
  background: var(--figma-surface-glass-soft);
  backdrop-filter: blur(2px);
}

.container-preloader__spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(225, 69, 84, 0.18);
  border-top-color: var(--figma-accent, #e14554);
  border-radius: 50%;
  animation: container-preloader-spin 0.8s linear infinite;
}

.container-preloader__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--figma-ink);
}

@keyframes container-preloader-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<style>
.container-preloader-enter-active,
.container-preloader-leave-active {
  transition: opacity 0.18s ease;
}

.container-preloader-enter-from,
.container-preloader-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .container-preloader-enter-active,
  .container-preloader-leave-active {
    transition-duration: 0.01ms;
  }

  .container-preloader__spinner {
    animation: none;
    border-top-color: rgba(225, 69, 84, 0.18);
    border-right-color: var(--figma-accent, #e14554);
  }
}
</style>
