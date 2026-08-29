<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useCookieConsent } from '@/modules/consent/useCookieConsent'

const { t } = useI18n()
const { visible, accept } = useCookieConsent()
</script>

<template>
  <Transition name="cookie-consent">
    <div
      v-if="visible"
      class="cookie-consent"
      role="dialog"
      aria-live="polite"
      :aria-label="t('cookies.title')"
    >
      <div class="cookie-consent__inner">
        <p class="cookie-consent__text">{{ t('cookies.message') }}</p>
        <button type="button" class="cookie-consent__accept" @click="accept">
          {{ t('cookies.accept') }}
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.cookie-consent {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 3500;
  padding: 12px 16px;
  padding-bottom: max(12px, env(safe-area-inset-bottom, 0px));
  padding-left: max(16px, env(safe-area-inset-left, 0px));
  padding-right: max(16px, env(safe-area-inset-right, 0px));
  pointer-events: none;
}

.cookie-consent__inner {
  pointer-events: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  max-width: 1100px;
  margin: 0 auto;
  padding: 14px 16px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 14px;
  background: var(--figma-surface);
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.1);
}

.cookie-consent__text {
  margin: 0;
  flex: 1;
  min-width: 0;
  color: var(--color-text-muted);
  font-size: 13px;
  line-height: 1.45;
}

.cookie-consent__accept {
  flex-shrink: 0;
  min-height: 44px;
  min-width: 44px;
  padding: 0 18px;
  border: none;
  border-radius: 50px;
  background: #9ca3af;
  color: var(--figma-on-accent);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.15s ease;
}

.cookie-consent__accept:hover {
  background: #6b7280;
}

.cookie-consent__accept:active {
  transform: scale(0.98);
}

.cookie-consent-enter-active,
.cookie-consent-leave-active {
  transition: transform 0.28s ease, opacity 0.28s ease;
}

.cookie-consent-enter-from,
.cookie-consent-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

@media (max-width: 767px) {
  .cookie-consent {
    padding-left: max(12px, env(safe-area-inset-left, 0px));
    padding-right: max(12px, env(safe-area-inset-right, 0px));
  }

  .cookie-consent__inner {
    flex-direction: column;
    align-items: stretch;
  }

  .cookie-consent__accept {
    width: 100%;
  }
}
</style>
