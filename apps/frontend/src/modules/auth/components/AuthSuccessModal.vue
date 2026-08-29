<script setup lang="ts">
import { computed, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthSuccessModal } from '@/modules/auth/composables/useAuthSuccessModal'

const AUTO_CLOSE_MS = 2800

const { t } = useI18n()
const { isOpen, kind, close } = useAuthSuccessModal()

const title = computed(() =>
  kind.value === 'register' ? t('auth.successRegisterTitle') : t('auth.successLoginTitle'),
)

const message = computed(() =>
  kind.value === 'register' ? t('auth.successRegisterMessage') : t('auth.successLoginMessage'),
)

let autoCloseTimer: ReturnType<typeof setTimeout> | null = null

function clearAutoClose() {
  if (autoCloseTimer !== null) {
    clearTimeout(autoCloseTimer)
    autoCloseTimer = null
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && isOpen.value) {
    close()
  }
}

watch(
  () => isOpen.value,
  (open) => {
    clearAutoClose()
    window.removeEventListener('keydown', handleKeydown)

    if (!open) {
      return
    }

    window.addEventListener('keydown', handleKeydown)
    autoCloseTimer = setTimeout(() => {
      close()
    }, AUTO_CLOSE_MS)
  },
)

onUnmounted(() => {
  clearAutoClose()
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="auth-success">
      <div
        v-if="isOpen"
        class="auth-success-overlay"
        @click.self="close()"
      >
        <div
          class="auth-success"
          role="dialog"
          aria-modal="true"
          aria-labelledby="auth-success-title"
          @click.stop
        >
          <div class="auth-success__icon" aria-hidden="true">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none">
              <path
                d="M5 12.5l4.5 4.5L19 7.5"
                stroke="currentColor"
                stroke-width="2.4"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>

          <h2 id="auth-success-title" class="auth-success__title">{{ title }}</h2>
          <p class="auth-success__message">{{ message }}</p>

          <button type="button" class="auth-success__ok" @click="close()">
            {{ t('auth.successOk') }}
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.auth-success-overlay {
  position: fixed;
  inset: 0;
  z-index: 4100;
  display: grid;
  place-items: center;
  padding: 16px;
  padding-bottom: max(16px, env(safe-area-inset-bottom, 0px));
  background: rgba(0, 0, 0, 0.28);
}

.auth-success {
  width: min(100%, 360px);
  padding: 28px 24px 22px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 20px;
  background: var(--figma-surface);
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.14);
}

.auth-success__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  margin: 0 auto 16px;
  border-radius: 50%;
  background: rgba(225, 69, 84, 0.12);
  color: var(--figma-accent, #e14554);
  animation: auth-success-pop 0.35s ease;
}

@supports (color: color-mix(in srgb, red 50%, blue)) {
  .auth-success__icon {
    background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
  }
}

.auth-success__title {
  margin: 0 0 8px;
  color: var(--figma-ink-secondary);
  font-size: 20px;
  font-weight: 700;
  line-height: 1.25;
}

.auth-success__message {
  margin: 0 0 20px;
  color: var(--color-text-muted);
  font-size: 14px;
  line-height: 1.45;
}

.auth-success__ok {
  min-height: 44px;
  min-width: 120px;
  padding: 0 22px;
  border: none;
  border-radius: 50px;
  background: var(--figma-accent, #e14554);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.15s ease;
}

.auth-success__ok:hover {
  background: #c93a48;
}

.auth-success__ok:active {
  transform: scale(0.98);
}

.auth-success-enter-active,
.auth-success-leave-active {
  transition: opacity 0.22s ease;
}

.auth-success-enter-active .auth-success,
.auth-success-leave-active .auth-success {
  transition: transform 0.22s ease, opacity 0.22s ease;
}

.auth-success-enter-from,
.auth-success-leave-to {
  opacity: 0;
}

.auth-success-enter-from .auth-success,
.auth-success-leave-to .auth-success {
  opacity: 0;
  transform: translateY(10px) scale(0.96);
}

@keyframes auth-success-pop {
  0% {
    transform: scale(0.7);
    opacity: 0;
  }

  100% {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
