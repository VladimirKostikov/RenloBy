<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { resolveApiError } from '@/lib/resolveApiError'
import { useAuthModal, type AuthModalMode } from '@/modules/auth/composables/useAuthModal'
import { useAuthSuccessModal } from '@/modules/auth/composables/useAuthSuccessModal'
import { useAuthStore } from '@/stores/auth'
import AppLogomark from '@/components/layout/AppLogomark.vue'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const { isOpen, mode, setMode, close, consumeSuccessCallback } = useAuthModal()
const authSuccess = useAuthSuccessModal()

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const error = ref('')
const fieldErrors = ref<Record<string, string>>({})

const titleId = 'auth-modal-title'

const isLogin = computed(() => mode.value === 'login')

watch(
  () => isOpen.value,
  (open) => {
    if (open) {
      resetForm()
      void nextTick(() => {
        document.getElementById('auth-modal-email')?.focus()
      })
    }
  },
)

watch(
  () => mode.value,
  () => {
    error.value = ''
    fieldErrors.value = {}
    confirmPassword.value = ''
  },
)

function resetForm() {
  email.value = ''
  password.value = ''
  confirmPassword.value = ''
  error.value = ''
  fieldErrors.value = {}
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && isOpen.value && !auth.loading) {
    close()
  }
}

function setBodyScrollLocked(locked: boolean) {
  document.body.style.overflow = locked ? 'hidden' : ''
}

watch(
  () => isOpen.value,
  (open) => setBodyScrollLocked(open),
  { immediate: true },
)

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  setBodyScrollLocked(false)
})

function switchMode(nextMode: AuthModalMode) {
  setMode(nextMode)
}

function validateClient(): boolean {
  fieldErrors.value = {}

  if (!email.value.trim()) {
    fieldErrors.value.email = t('auth.emailRequired')
  }

  if (!password.value) {
    fieldErrors.value.password = t('auth.passwordRequired')
  } else if (password.value.length < 8) {
    fieldErrors.value.password = t('auth.passwordMin')
  }

  if (!isLogin.value && password.value !== confirmPassword.value) {
    fieldErrors.value.confirmPassword = t('auth.passwordMismatch')
  }

  return Object.keys(fieldErrors.value).length === 0
}

function applyApiError(err: unknown, fallbackKey: string) {
  const resolved = resolveApiError(err, t, fallbackKey)
  fieldErrors.value = {
    ...fieldErrors.value,
    ...resolved.fieldErrors,
  }
  error.value = resolved.message
}

async function finishSuccess() {
  const successKind = isLogin.value ? 'login' : 'register'
  const { callback, redirect } = consumeSuccessCallback()
  close()
  authSuccess.open(successKind)

  if (redirect) {
    await router.push(redirect)
  }

  if (callback) {
    await callback()
  }
}

async function submit() {
  error.value = ''
  fieldErrors.value = {}

  if (!validateClient()) {
    return
  }

  try {
    if (isLogin.value) {
      await auth.login({ email: email.value.trim(), password: password.value })
    } else {
      await auth.register({ email: email.value.trim(), password: password.value })
    }
    await finishSuccess()
  } catch (err) {
    applyApiError(err, isLogin.value ? 'auth.error' : 'auth.registerError')
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="auth-modal" appear>
      <div
        v-if="isOpen"
        class="auth-modal-overlay"
        @click.self="!auth.loading && close()"
      >
        <div
          class="auth-modal"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          @click.stop
        >
          <div class="auth-modal__accent" aria-hidden="true" />

          <button
            type="button"
            class="auth-modal__close"
            :aria-label="t('auth.close')"
            :disabled="auth.loading"
            @click="close()"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
          </button>

          <div class="auth-modal__header">
            <div class="auth-modal__brand">
              <AppLogomark :width="48" :height="48" image-class="auth-modal__logomark" />
              <span class="auth-modal__brand-text">
                <span class="auth-modal__brand-name">{{ t('app.name') }}</span>
                <span class="auth-modal__brand-tagline">{{ t('app.tagline') }}</span>
              </span>
            </div>

            <div class="auth-modal__tabs" role="tablist">
              <button
                type="button"
                role="tab"
                class="auth-modal__tab"
                :class="{ 'auth-modal__tab--active': isLogin }"
                :aria-selected="isLogin"
                @click="switchMode('login')"
              >
                <svg class="auth-modal__tab-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path
                    d="M10 17l5-5-5-5"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M15 12H3"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                  />
                  <path
                    d="M21 21V3"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                  />
                </svg>
                <span>{{ t('auth.loginTitle') }}</span>
              </button>
              <button
                type="button"
                role="tab"
                class="auth-modal__tab"
                :class="{ 'auth-modal__tab--active': !isLogin }"
                :aria-selected="!isLogin"
                @click="switchMode('register')"
              >
                <svg class="auth-modal__tab-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <circle cx="12" cy="8" r="3.25" stroke="currentColor" stroke-width="1.75" />
                  <path
                    d="M5.5 19.5c1.6-3 4-4.5 6.5-4.5s4.9 1.5 6.5 4.5"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                  />
                </svg>
                <span>{{ t('auth.registerTitle') }}</span>
              </button>
            </div>
          </div>

          <form class="auth-modal__form" @submit.prevent="submit">
            <h2 :id="titleId" class="auth-modal__title">
              {{ isLogin ? t('auth.loginTitle') : t('auth.registerTitle') }}
            </h2>
            <p class="auth-modal__subtitle">
              {{ isLogin ? t('auth.loginSubtitle') : t('auth.registerSubtitle') }}
            </p>

            <p v-if="error" class="auth-modal__error" role="alert">{{ error }}</p>

            <label class="auth-modal__field">
              <span>{{ t('auth.email') }}</span>
              <span class="auth-modal__control">
                <svg class="auth-modal__control-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.75" />
                  <path d="M4.5 7.5L12 13l7.5-5.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <input
                  id="auth-modal-email"
                  v-model="email"
                  type="email"
                  required
                  autocomplete="email"
                  :placeholder="t('auth.emailPlaceholder')"
                  :disabled="auth.loading"
                />
              </span>
              <span v-if="fieldErrors.email" class="auth-modal__field-error">{{ fieldErrors.email }}</span>
            </label>

            <label class="auth-modal__field">
              <span>{{ t('auth.password') }}</span>
              <span class="auth-modal__control">
                <svg class="auth-modal__control-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <rect x="5" y="10.5" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.75" />
                  <path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                </svg>
                <input
                  v-model="password"
                  type="password"
                  required
                  :autocomplete="isLogin ? 'current-password' : 'new-password'"
                  :placeholder="t('auth.passwordPlaceholder')"
                  :disabled="auth.loading"
                />
              </span>
              <span v-if="fieldErrors.password" class="auth-modal__field-error">{{ fieldErrors.password }}</span>
            </label>

            <label v-if="!isLogin" class="auth-modal__field">
              <span>{{ t('auth.confirmPassword') }}</span>
              <span class="auth-modal__control">
                <svg class="auth-modal__control-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path
                    d="M8.5 12.5l2.5 2.5 5-5"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <rect x="5" y="10.5" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.75" />
                  <path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                </svg>
                <input
                  v-model="confirmPassword"
                  type="password"
                  required
                  autocomplete="new-password"
                  :placeholder="t('auth.confirmPasswordPlaceholder')"
                  :disabled="auth.loading"
                />
              </span>
              <span v-if="fieldErrors.confirmPassword" class="auth-modal__field-error">
                {{ fieldErrors.confirmPassword }}
              </span>
            </label>

            <button type="submit" class="auth-modal__submit" :disabled="auth.loading">
              <span v-if="auth.loading" class="auth-modal__submit-spinner" aria-hidden="true" />
              <svg
                v-else
                class="auth-modal__submit-icon"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
              >
                <path
                  v-if="isLogin"
                  d="M10 17l5-5-5-5M15 12H3M21 21V3"
                  stroke="currentColor"
                  stroke-width="1.75"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  v-else
                  d="M12 5v14M5 12h14"
                  stroke="currentColor"
                  stroke-width="1.75"
                  stroke-linecap="round"
                />
              </svg>
              {{ isLogin ? t('auth.submit') : t('auth.registerSubmit') }}
            </button>

            <p class="auth-modal__switch">
              <template v-if="isLogin">
                {{ t('auth.noAccount') }}
                <button type="button" class="auth-modal__switch-btn" @click="switchMode('register')">
                  {{ t('auth.createAccount') }}
                </button>
              </template>
              <template v-else>
                {{ t('auth.hasAccount') }}
                <button type="button" class="auth-modal__switch-btn" @click="switchMode('login')">
                  {{ t('auth.signIn') }}
                </button>
              </template>
            </p>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.auth-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 4000;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.58);
  backdrop-filter: blur(2px);
}

.auth-modal {
  position: relative;
  width: min(100%, 440px);
  border: 1px solid var(--figma-border);
  border-radius: 20px;
  background: var(--figma-surface);
  color: var(--figma-ink);
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.16);
  overflow: hidden;
  transform: translateY(0) scale(1);
  will-change: transform, opacity;
}

.auth-modal__accent {
  height: 4px;
  background: linear-gradient(90deg, var(--figma-accent) 0%, #ff8a96 100%);
}

.auth-modal__close {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid var(--figma-border);
  border-radius: 50px;
  background: var(--figma-surface);
  color: #666;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.auth-modal__close:hover:not(:disabled) {
  border-color: #ccc;
  color: var(--figma-ink);
}

.auth-modal__close:active:not(:disabled) {
  transform: scale(0.96);
}

.auth-modal__close:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.auth-modal__header {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px 28px 0;
}

.auth-modal__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  align-self: stretch;
  width: 100%;
  margin-bottom: 20px;
  padding-right: 40px;
}

.auth-modal__logomark {
  width: 48px;
  height: 48px;
  object-fit: contain;
  flex-shrink: 0;
}

.auth-modal__brand-text {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.auth-modal__brand-name {
  font-size: 20px;
  font-weight: 600;
  line-height: 1;
  color: var(--figma-ink);
}

.auth-modal__brand-tagline {
  font-size: 10px;
  line-height: 1.2;
  color: var(--figma-text-muted);
}

.auth-modal__tabs {
  display: inline-flex;
  align-items: center;
  gap: 0;
  padding: 4px;
  border: 1px solid var(--figma-border);
  border-radius: 50px;
  background: var(--figma-surface);
}

.auth-modal__tab {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 120px;
  min-height: 36px;
  height: 36px;
  padding: 0 16px;
  border: none;
  border-radius: 50px;
  background: transparent;
  color: #666;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition:
    color 0.2s ease,
    background-color 0.2s ease,
    transform 0.2s ease;
}

.auth-modal__tab-icon {
  flex-shrink: 0;
}

.auth-modal__tab--active {
  color: var(--figma-on-accent);
  background: var(--figma-accent);
}

.auth-modal__tab--active:hover {
  background: var(--figma-accent-hover);
}

.auth-modal__tab:active {
  transform: scale(0.98);
}

.auth-modal__form {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 22px 28px 28px;
}

.auth-modal__title {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
  color: var(--figma-ink);
}

.auth-modal__subtitle {
  margin: -6px 0 0;
  font-size: 13px;
  line-height: 1.4;
  color: var(--figma-text-muted);
}

.auth-modal__error {
  margin: 0;
  padding: 10px 12px;
  border-radius: var(--figma-radius-chip);
  background: rgba(225, 69, 84, 0.08);
  font-size: 13px;
  color: var(--figma-accent);
}

.auth-modal__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-ink);
}

.auth-modal__control {
  position: relative;
  display: block;
}

.auth-modal__control-icon {
  position: absolute;
  top: 50%;
  left: 12px;
  z-index: 1;
  transform: translateY(-50%);
  color: #8a8a8a;
  pointer-events: none;
}

.auth-modal__control input {
  box-sizing: border-box;
  width: 100%;
  height: 44px;
  padding: 0 14px 0 40px;
  border: 1px solid var(--figma-search-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 14px;
  font-weight: 400;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.auth-modal__control input::placeholder {
  color: #999;
}

.auth-modal__control input:focus {
  outline: none;
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px rgba(225, 69, 84, 0.12);
}

.auth-modal__control:focus-within .auth-modal__control-icon {
  color: var(--figma-accent);
}

.auth-modal__control input:disabled {
  opacity: 0.65;
  background: #f8f8f8;
}

.auth-modal__field-error {
  font-size: 12px;
  font-weight: 400;
  color: var(--figma-accent);
}

.auth-modal__submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 4px;
  height: 44px;
  padding: 0 18px;
  border: none;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    transform 0.2s ease;
}

.auth-modal__submit:hover:not(:disabled) {
  background: var(--figma-accent-hover);
}

.auth-modal__submit:active:not(:disabled) {
  transform: scale(0.99);
}

.auth-modal__submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.auth-modal__submit-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: var(--figma-on-accent);
  border-radius: 50%;
  animation: auth-modal-spin 0.7s linear infinite;
}

.auth-modal__switch {
  margin: 0;
  text-align: center;
  font-size: 13px;
  color: var(--figma-text-muted);
}

.auth-modal__switch-btn {
  border: none;
  padding: 0;
  background: transparent;
  color: var(--figma-accent);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.auth-modal__switch-btn:hover {
  text-decoration: underline;
}

@keyframes auth-modal-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .auth-modal__submit-spinner {
    animation: none;
  }
}

@media (max-width: 767px) {
  .auth-modal__header,
  .auth-modal__form {
    padding-left: 20px;
    padding-right: 20px;
  }

  .auth-modal__tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-self: stretch;
    width: 100%;
    border-radius: var(--figma-radius-chip);
  }

  .auth-modal__tab {
    min-width: 0;
    width: 100%;
    border-radius: 8px;
  }
}
</style>

<style>
.auth-modal-enter-active,
.auth-modal-leave-active {
  transition: opacity 0.28s ease;
}

.auth-modal-enter-active .auth-modal,
.auth-modal-leave-active .auth-modal {
  transition:
    opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.auth-modal-enter-from,
.auth-modal-leave-to {
  opacity: 0;
}

.auth-modal-enter-from .auth-modal,
.auth-modal-leave-to .auth-modal {
  opacity: 0;
  transform: translateY(18px) scale(0.985);
}

@media (prefers-reduced-motion: reduce) {
  .auth-modal-enter-active,
  .auth-modal-leave-active,
  .auth-modal-enter-active .auth-modal,
  .auth-modal-leave-active .auth-modal {
    transition-duration: 0.01ms;
  }

  .auth-modal-enter-from .auth-modal,
  .auth-modal-leave-to .auth-modal {
    transform: none;
  }
}
</style>
