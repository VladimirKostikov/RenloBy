<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { setLocale } from '@/modules/locale'
import ThemeAppearanceSettings from '@/modules/theme/components/ThemeAppearanceSettings.vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()

function selectLocale(next: 'ru' | 'en') {
  if (locale.value !== next) {
    setLocale(next)
  }
}

async function logout() {
  await auth.logout()
  await router.push('/')
}
</script>

<template>
  <div class="account-settings">
    <header class="account-settings__header">
      <h1 class="account-settings__title">{{ t('account.settings.title') }}</h1>
      <p class="account-settings__subtitle">{{ t('account.settings.subtitle') }}</p>
    </header>

    <ThemeAppearanceSettings />

    <section class="account-settings__section">
      <h2 class="account-settings__heading">{{ t('account.settings.language') }}</h2>
      <p class="account-settings__hint">{{ t('account.settings.languageHint') }}</p>
      <div class="account-settings__mode" role="group" :aria-label="t('account.settings.language')">
        <button
          type="button"
          class="account-settings__mode-btn"
          :class="{ 'account-settings__mode-btn--active': locale === 'ru' }"
          :aria-pressed="locale === 'ru'"
          @click="selectLocale('ru')"
        >
          {{ t('locale.ru') }}
        </button>
        <button
          type="button"
          class="account-settings__mode-btn"
          :class="{ 'account-settings__mode-btn--active': locale === 'en' }"
          :aria-pressed="locale === 'en'"
          @click="selectLocale('en')"
        >
          {{ t('locale.en') }}
        </button>
      </div>
    </section>

    <section class="account-settings__section">
      <h2 class="account-settings__heading">{{ t('account.settings.session') }}</h2>
      <p class="account-settings__hint">{{ t('account.settings.sessionHint') }}</p>
      <div class="account-settings__actions">
        <RouterLink to="/account/user/profile" class="account-settings__link">
          {{ t('account.settings.openProfile') }}
        </RouterLink>
        <button type="button" class="account-settings__logout" @click="logout">
          {{ t('nav.logout') }}
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.account-settings {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.account-settings__header {
  margin-bottom: 0;
}

.account-settings__title {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
  color: var(--color-text);
}

.account-settings__subtitle,
.account-settings__hint {
  margin: 0;
  font-size: 15px;
  color: var(--color-text-muted);
}

.account-settings__hint {
  font-size: 14px;
}

.account-settings__section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-top: 8px;
  border-top: 1px solid var(--figma-border);
}

.account-settings__heading {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text);
}

.account-settings__mode {
  display: inline-flex;
  gap: 0;
  padding: 3px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-btn);
  background: var(--color-bg-elevated);
}

.account-settings__mode-btn {
  min-height: 34px;
  min-width: 96px;
  padding: 0 14px;
  border: none;
  border-radius: calc(var(--figma-radius-btn) - 2px);
  background: transparent;
  color: var(--color-text-muted);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.account-settings__mode-btn--active {
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.account-settings__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.account-settings__link,
.account-settings__logout {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 0 18px;
  border-radius: var(--radius-md, 8px);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}

.account-settings__link {
  border: 1px solid var(--figma-border);
  background: var(--color-bg-elevated);
  color: var(--color-text);
}

.account-settings__logout {
  border: none;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

@media (max-width: 767px) {
  .account-settings__title {
    font-size: 22px;
  }
}
</style>
