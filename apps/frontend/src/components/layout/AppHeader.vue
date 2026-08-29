<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'
import { useAuthStore } from '@/stores/auth'
import { useListingsStore } from '@/stores/listings'
import { useThemeStore } from '@/stores/theme'
import { setLocale } from '@/modules/locale'
import type { DealType, PaletteId } from '@/types'
import AppLogomark from '@/components/layout/AppLogomark.vue'

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const authModal = useAuthModal()
const listings = useListingsStore()
const theme = useThemeStore()

const navItems = computed(() => [
  { key: 'sale' as DealType, label: t('nav.sale') },
  { key: 'rent' as DealType, label: t('nav.rent') },
  { key: 'commercial' as DealType, label: t('nav.commercial') },
])

function selectDealType(dealType: DealType) {
  listings.setDealType(dealType)
  listings.search()
}

function toggleLocale() {
  setLocale(locale.value === 'ru' ? 'en' : 'ru')
}

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <header class="header">
    <div class="header__inner">
      <RouterLink to="/" class="header__logo">
        <AppLogomark :width="32" :height="32" image-class="header__logo-mark" />
        <span class="header__logo-text">{{ t('app.name') }}</span>
      </RouterLink>

      <nav class="header__nav">
        <button
          v-for="item in navItems"
          :key="item.key"
          type="button"
          class="header__nav-item"
          :class="{ 'header__nav-item--active': listings.dealType === item.key }"
          @click="selectDealType(item.key)"
        >
          {{ item.label }}
        </button>
      </nav>

      <div class="header__search">
        <input
          v-model="listings.searchQuery"
          type="search"
          class="header__search-input"
          :placeholder="t('search.placeholder')"
        />
      </div>

      <div class="header__actions">
        <button type="button" class="header__icon-btn" :title="t('nav.favorites')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
          </svg>
        </button>
        <button type="button" class="header__icon-btn" :title="t('nav.compare')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5" />
          </svg>
        </button>

        <select
          class="header__select"
          :value="theme.palette"
          :aria-label="t('theme.palette')"
          @change="theme.setPalette(($event.target as HTMLSelectElement).value as PaletteId)"
        >
          <option value="default">{{ t('theme.palettes.default') }}</option>
          <option value="ocean">{{ t('theme.palettes.ocean') }}</option>
          <option value="forest">{{ t('theme.palettes.forest') }}</option>
          <option value="slate">{{ t('theme.palettes.slate') }}</option>
        </select>

        <button type="button" class="header__icon-btn" @click="theme.toggleMode()">
          {{ theme.mode === 'light' ? '🌙' : '☀️' }}
        </button>

        <button type="button" class="header__locale-btn" @click="toggleLocale()">
          {{ locale === 'ru' ? 'EN' : 'RU' }}
        </button>

        <RouterLink v-if="auth.isAdmin" to="/admin" class="header__link">
          {{ t('nav.admin') }}
        </RouterLink>

        <button
          v-if="!auth.isAuthenticated"
          type="button"
          class="header__link"
          @click="authModal.openLogin()"
        >
          {{ t('nav.login') }}
        </button>
        <button v-else type="button" class="header__link" @click="handleLogout">
          {{ t('nav.logout') }}
        </button>

        <button type="button" class="header__cta">
          {{ t('nav.postListing') }}
        </button>
      </div>
    </div>
  </header>
</template>

<style scoped>
.header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: var(--header-height);
  background: var(--color-bg-elevated);
  border-bottom: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.header__inner {
  display: flex;
  align-items: center;
  gap: 16px;
  max-width: 1600px;
  height: 100%;
  margin: 0 auto;
  padding: 0 20px;
}

.header__logo {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  font-weight: 700;
}

.header__logo-mark {
  width: 32px;
  height: 32px;
  object-fit: contain;
  flex-shrink: 0;
}

.header__logo-text {
  font-size: 18px;
}

.header__nav {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.header__nav-item {
  padding: 8px 14px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text-muted);
  font-weight: 600;
  transition: background 0.15s, color 0.15s;
}

.header__nav-item:hover,
.header__nav-item--active {
  background: var(--accent-muted);
  color: var(--accent);
}

.header__search {
  flex: 1;
  min-width: 180px;
}

.header__search-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-muted);
  color: var(--color-text);
}

.header__search-input:focus {
  outline: 2px solid var(--color-primary);
  border-color: transparent;
}

.header__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.header__icon-btn,
.header__locale-btn,
.header__link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 10px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text-muted);
  font-weight: 600;
  transition: background 0.15s, color 0.15s;
}

.header__icon-btn:hover,
.header__locale-btn:hover,
.header__link:hover {
  background: var(--color-bg-muted);
  color: var(--color-text);
}

.header__select {
  padding: 6px 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-bg-elevated);
  color: var(--color-text);
  font-size: 13px;
}

.header__cta {
  padding: 10px 18px;
  border: none;
  border-radius: var(--radius-md);
  background: var(--accent);
  color: var(--figma-on-accent);
  font-weight: 600;
  white-space: nowrap;
  transition: background 0.15s;
}

.header__cta:hover {
  background: var(--accent-hover);
}

@media (max-width: 1100px) {
  .header__nav {
    display: none;
  }
}
</style>
