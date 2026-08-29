<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const { message, visible } = storeToRefs(toast)
</script>

<template>
  <Teleport to="body">
    <Transition name="app-toast">
      <div
        v-if="visible"
        class="app-toast-wrap"
        role="status"
        aria-live="polite"
      >
        <div class="app-toast">
          {{ message }}
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.app-toast-wrap {
  position: fixed;
  left: 50%;
  bottom: max(24px, env(safe-area-inset-bottom, 0px));
  z-index: 5600;
  transform: translateX(-50%);
  pointer-events: none;
}

.app-toast {
  max-width: min(360px, calc(100vw - 32px));
  padding: 12px 18px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  line-height: 1.35;
  text-align: center;
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.18);
}

.app-toast-enter-active,
.app-toast-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.app-toast-enter-from,
.app-toast-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(10px);
}

@media (prefers-reduced-motion: reduce) {
  .app-toast-enter-active,
  .app-toast-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
