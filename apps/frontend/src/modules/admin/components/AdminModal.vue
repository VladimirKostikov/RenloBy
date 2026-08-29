<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'

const props = defineProps<{
  open: boolean
  title: string
  wide?: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.open) {
    emit('close')
  }
}

watch(
  () => props.open,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  },
)

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="admin-modal">
      <div
        v-if="open"
        class="admin-modal"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="emit('close')"
      >
        <div class="admin-modal__panel" :class="{ 'admin-modal__panel--wide': wide }">
          <header class="admin-modal__header">
            <h2 class="admin-modal__title">{{ title }}</h2>
            <button type="button" class="admin-modal__close" :aria-label="title" @click="emit('close')">
              <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          </header>
          <div class="admin-modal__body">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.admin-modal {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 48px 16px 24px;
  overflow: auto;
  background: rgba(26, 29, 38, 0.36);
}

.admin-modal__panel {
  width: min(520px, 100%);
  margin: auto 0;
  border-radius: var(--admin-radius, 10px);
  background: #fff;
  box-shadow: var(--admin-shadow-modal, 0 16px 48px rgba(26, 29, 38, 0.14));
  transform-origin: center top;
}

.admin-modal__panel--wide {
  width: min(760px, 100%);
}

.admin-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 20px 12px;
  border-bottom: 1px solid var(--admin-border, #e8eaef);
}

.admin-modal__title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--admin-text, #1a1d26);
}

.admin-modal__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--admin-text-muted, #6b7280);
  cursor: pointer;
  transition: background-color 180ms ease, color 180ms ease, transform 180ms ease;
}

.admin-modal__close:hover {
  background: var(--admin-row-hover, #f8f9fb);
  color: var(--admin-text, #1a1d26);
}

.admin-modal__close:active {
  transform: scale(0.96);
}

.admin-modal__body {
  padding: 16px 20px 20px;
}

.admin-modal-enter-active,
.admin-modal-leave-active {
  transition: opacity 240ms ease;
}

.admin-modal-enter-active .admin-modal__panel,
.admin-modal-leave-active .admin-modal__panel {
  transition: opacity 240ms ease, transform 240ms ease;
}

.admin-modal-enter-from,
.admin-modal-leave-to {
  opacity: 0;
}

.admin-modal-enter-from .admin-modal__panel,
.admin-modal-leave-to .admin-modal__panel {
  opacity: 0;
  transform: translateY(-12px) scale(0.98);
}
</style>
