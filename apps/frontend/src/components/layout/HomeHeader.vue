<script setup lang="ts">
import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'
import { catalogPath, type PublicCatalogKind } from '@/lib/fullPageNav'
import { goToCreateListing } from '@/lib/goToCreateListing'
import { useListingsStore } from '@/stores/listings'
import { useAuthStore } from '@/stores/auth'
import { useComparisonsStore } from '@/stores/comparisons'
import { useFavoritesStore } from '@/stores/favorites'
import { useNotificationsStore } from '@/stores/notifications'
import AppLogomark from '@/components/layout/AppLogomark.vue'
import HeaderSearchField from '@/components/layout/HeaderSearchField.vue'
import HeaderTopBar from '@/components/layout/HeaderTopBar.vue'
import ThemeIcon from '@/components/ThemeIcon.vue'

const props = defineProps<{
  activeCatalogKind?: PublicCatalogKind
  sticky?: boolean
}>()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const listings = useListingsStore()
const auth = useAuthStore()
const authModal = useAuthModal()
const favorites = useFavoritesStore()
const comparisons = useComparisonsStore()
const notifications = useNotificationsStore()

watch(
  () => auth.isAuthenticated,
  (isAuthenticated) => {
    if (isAuthenticated) {
      void notifications.loadUnreadCount()
    } else {
      notifications.reset()
    }
  },
  { immediate: true },
)
const navItems = computed(() => [
  { key: 'sale' as PublicCatalogKind, label: t('nav.sale'), href: catalogPath('sale'), icon: '/figma/nav-sale.svg' },
  { key: 'rent' as PublicCatalogKind, label: t('nav.rent'), href: catalogPath('rent'), icon: '/figma/nav-rent.svg' },
  { key: 'commercial' as PublicCatalogKind, label: t('nav.commercial'), href: catalogPath('commercial'), icon: '/figma/nav-commercial.svg' },
])

const currentCatalogKind = computed((): PublicCatalogKind => {
  if (props.activeCatalogKind) {
    return props.activeCatalogKind
  }

  if (route.meta.listingType === 'commercial') {
    return 'commercial'
  }

  const metaDealType = route.meta.dealType
  if (metaDealType === 'sale' || metaDealType === 'rent') {
    return metaDealType
  }

  if (route.name === 'sale-catalog' || route.name === 'sale-listing-detail') {
    return 'sale'
  }
  if (route.name === 'rent-catalog' || route.name === 'rent-listing-detail') {
    return 'rent'
  }
  if (route.name === 'commercial-catalog' || route.name === 'commercial-listing-detail') {
    return 'commercial'
  }
  if (listings.listingType === 'commercial') {
    return 'commercial'
  }
  return listings.dealType
})

function openLogin() {
  authModal.openLogin()
}

async function openPostListing() {
  await goToCreateListing({
    isAuthenticated: auth.isAuthenticated,
    router,
    openRegister: (options) => authModal.openRegister(options),
  })
}
</script>

<template>
  <header class="home-header" :class="{ 'home-header--sticky': sticky }">
    <HeaderTopBar />
    <div class="page-shell home-header__inner">
      <div class="home-header__start">
        <a
          href="/"
          class="home-header__logo"
          :aria-label="t('app.name')"
        >
          <AppLogomark :width="51" :height="51" image-class="home-header__logomark" />
          <span class="home-header__brand">
            <span class="home-header__name">{{ t('app.name') }}</span>
            <span class="home-header__tagline">{{ t('app.tagline') }}</span>
          </span>
        </a>

        <nav class="home-header__nav">
          <a
            v-for="item in navItems"
            :key="item.key"
            :href="item.href"
            class="home-header__nav-item"
            :class="{ 'home-header__nav-item--active': currentCatalogKind === item.key }"
          >
            <span
              class="home-header__nav-icon"
              :style="{ maskImage: `url(${item.icon})`, WebkitMaskImage: `url(${item.icon})` }"
              aria-hidden="true"
            />
            <span>{{ item.label }}</span>
          </a>
        </nav>
      </div>

      <div class="home-header__center">
        <HeaderSearchField />

        <div class="home-header__quick-actions">
          <a href="/favorites" class="home-header__action" :aria-label="t('nav.favorites')" :title="t('nav.favorites')">
            <span class="home-header__action-icon">
              <ThemeIcon src="/figma/heart.svg" :width="17" :height="14" />
              <span v-if="favorites.count > 0" class="home-header__badge">{{ favorites.count }}</span>
            </span>
            <span class="home-header__action-label">{{ t('nav.favorites') }}</span>
          </a>
          <a href="/compare" class="home-header__action" :aria-label="t('nav.compare')" :title="t('nav.compare')">
            <span class="home-header__action-icon">
              <ThemeIcon src="/figma/compare.svg" :width="17" :height="17" />
              <span v-if="comparisons.count > 0" class="home-header__badge">{{ comparisons.count }}</span>
            </span>
            <span class="home-header__action-label">{{ t('nav.compare') }}</span>
          </a>
        </div>
      </div>

      <div class="home-header__actions">
        <button
          v-if="!auth.isAuthenticated"
          type="button"
          class="home-header__login"
          @click="openLogin"
        >
          <span class="home-header__login-icon-wrap" aria-hidden="true">
            <svg class="home-header__login-icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path
                d="M10 17l5-5-5-5"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path d="M15 12H3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
              <path d="M21 21V3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
            </svg>
          </span>
          <span>{{ t('nav.login') }}</span>
        </button>
        <a
          v-else
          href="/account/user/profile"
          class="home-header__user"
          :title="auth.user?.email"
          :aria-label="notifications.hasUnread
            ? t('account.notifications.unreadAria', { count: notifications.unreadCount })
            : t('account.shortTitle')"
        >
          <span class="home-header__user-icon-wrap">
            <ThemeIcon
              src="/figma/account-profile.svg"
              image-class="home-header__user-icon"
              :width="16"
              :height="16"
            />
            <span
              v-if="notifications.hasUnread"
              class="home-header__notify-dot"
              aria-hidden="true"
            />
          </span>
          <span>{{ t('account.shortTitle') }}</span>
        </a>
        <button type="button" class="home-header__cta" @click="openPostListing">
          <svg class="home-header__cta-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
          </svg>
          <span>{{ t('nav.postListing') }}</span>
        </button>
      </div>
    </div>
    <div class="page-shell home-header__divider-wrap">
      <div class="home-header__divider" />
    </div>
  </header>
</template>

<style scoped>
.home-header {
  position: relative;
  z-index: 100;
  background: var(--figma-surface);
  height: auto;
}

.home-header--sticky {
  position: sticky;
  top: 0;
  z-index: 100;
}

.home-header__inner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  height: auto;
  padding-top: 12px;
  padding-bottom: 12px;
}

.home-header__center {
  display: flex;
  align-items: center;
  gap: 16px;
  width: 100%;
  order: 3;
  min-width: 0;
}

.home-header__quick-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.home-header__start {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  flex: 1 1 auto;
  order: 1;
}

.home-header__logo {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  margin-right: 0;
  padding: 4px 0;
  cursor: pointer;
  text-decoration: none;
  transition: opacity 0.2s ease;
}

.home-header__logo:hover {
  opacity: 0.82;
}

.home-header__logo:focus-visible {
  outline: 2px solid var(--figma-accent);
  outline-offset: 4px;
  border-radius: 8px;
}

.home-header__logomark {
  width: 51px;
  height: 51px;
  object-fit: contain;
}

.home-header__brand {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.home-header__name {
  font-size: 20px;
  font-weight: 600;
  line-height: 1;
  color: var(--figma-ink);
}

.home-header__tagline {
  font-size: 10px;
  font-weight: 400;
  line-height: 1;
  color: var(--figma-ink);
}

.home-header__nav {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 16px;
  min-width: 0;
  margin-right: 0;
  padding-left: 0;
  padding-bottom: 0;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.home-header__nav::-webkit-scrollbar {
  display: none;
}

.home-header__nav-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  background: transparent;
  padding: 8px 0;
  min-height: var(--touch-target-min);
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  line-height: 1;
  flex-shrink: 0;
  text-decoration: none;
  transition: color 0.2s ease;
}

.home-header__nav-icon {
  display: inline-block;
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  background: currentColor;
  mask-size: contain;
  mask-repeat: no-repeat;
  mask-position: center;
  -webkit-mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  -webkit-mask-position: center;
}

.home-header__nav-item:hover {
  color: var(--figma-accent);
}

.home-header__nav-item--active {
  color: var(--figma-accent);
}

.home-header__nav-item--active:hover {
  color: var(--figma-accent-hover);
}

.home-header__center :deep(.header-search) {
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
}

.home-header__actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  order: 2;
  margin-left: auto;
}

.home-header__action {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: none;
  background: transparent;
  padding: 4px;
  min-width: var(--touch-target-min);
  min-height: var(--touch-target-min);
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  white-space: nowrap;
  text-decoration: none;
  cursor: pointer;
  flex-shrink: 0;
  border-radius: var(--figma-radius-btn);
  transition: color 0.2s ease, background-color 0.2s ease;
}

.home-header__action:hover {
  color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.06);
}

.home-header__action-icon {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 20px;
  height: 20px;
}

.home-header__badge {
  position: absolute;
  top: -6px;
  right: -8px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 50px;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 10px;
  font-weight: 700;
  line-height: 16px;
  text-align: center;
  pointer-events: none;
}

.home-header__action-label {
  display: none;
}

.home-header__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 40px;
  padding: 0 14px;
  border: none;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.home-header__cta-icon {
  flex-shrink: 0;
  display: block;
}

.home-header__cta:hover {
  background: var(--figma-accent-hover);
}

.home-header__cta:active {
  transform: scale(0.98);
}

.home-header__login {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 40px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-btn);
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  transition: border-color 0.2s ease, color 0.2s ease;
}

.home-header__login-icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.home-header__login-icon {
  display: block;
}

.home-header__login:hover {
  border-color: var(--figma-accent);
  color: var(--figma-accent);
}

.home-header__user {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-btn);
  background: var(--figma-surface);
  color: var(--figma-ink);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: border-color 0.2s ease, color 0.2s ease, background-color 0.2s ease;
}

.home-header__user:hover {
  border-color: var(--figma-accent);
  color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.04);
}

.home-header__user-icon-wrap {
  position: relative;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.home-header__user-icon {
  flex-shrink: 0;
  display: block;
}

.home-header__notify-dot {
  position: absolute;
  top: -3px;
  right: -4px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--figma-accent);
  box-shadow: 0 0 0 2px var(--figma-surface);
  pointer-events: none;
}

.home-header__divider-wrap {
  padding-top: 0;
  padding-bottom: 0;
  line-height: 0;
}

.home-header__divider {
  height: 1px;
  background: var(--figma-border);
}

@media (max-width: 767px) {
  .home-header__inner {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    grid-template-areas:
      'logo nav actions'
      'center center center';
    gap: 6px 8px;
    padding-top: 6px;
    padding-bottom: 6px;
  }

  .home-header__start {
    display: contents;
  }

  .home-header__logo {
    grid-area: logo;
    min-width: 0;
    gap: 8px;
  }

  .home-header__brand {
    display: none;
  }

  .home-header__nav {
    grid-area: nav;
    width: auto;
    min-width: 0;
    gap: 2px;
    padding-bottom: 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }

  .home-header__nav::-webkit-scrollbar {
    display: none;
  }

  .home-header__nav-item {
    gap: 0;
    padding: 0 8px;
    min-height: 32px;
    font-size: 12px;
    flex-shrink: 0;
  }

  .home-header__nav-icon {
    display: none;
  }

  .home-header__actions {
    grid-area: actions;
    order: unset;
    margin-left: 0;
    gap: 4px;
    justify-self: end;
  }

  .home-header__center {
    grid-area: center;
    order: unset;
    gap: 6px;
  }

  .home-header__tagline {
    display: none;
  }

  .home-header__logomark {
    width: 32px;
    height: 32px;
  }

  .home-header__name {
    font-size: 15px;
  }

  .home-header__login > span:not(.home-header__login-icon-wrap),
  .home-header__user > span:not(.home-header__user-icon-wrap) {
    display: none;
  }

  .home-header__login,
  .home-header__user {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    min-width: 36px;
    height: 36px;
    min-height: 36px;
    padding: 0;
  }

  .home-header__login-icon-wrap,
  .home-header__user-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .home-header__login-icon,
  .home-header__user-icon {
    display: block;
    width: 16px;
    height: 16px;
  }

  .home-header__cta {
    width: 36px;
    min-width: 36px;
    height: 36px;
    min-height: 36px;
    padding: 0;
    border-radius: 10px;
  }

  .home-header__cta span {
    display: none;
  }

  .home-header__cta-icon {
    display: block;
  }

  .home-header__quick-actions {
    gap: 2px;
  }

  .home-header__action {
    width: 36px;
    min-width: 36px;
    height: 36px;
    min-height: 36px;
    padding: 0;
  }

  .home-header__divider-wrap {
    padding-top: 0;
    padding-bottom: 0;
  }
}

@media (min-width: 768px) and (max-width: 1279px) {
  .home-header__inner {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas:
      'start actions'
      'center center';
    gap: 12px 16px;
  }

  .home-header__start {
    grid-area: start;
    flex: unset;
  }

  .home-header__center {
    grid-area: center;
    width: 100%;
    order: unset;
    gap: 16px;
  }

  .home-header__quick-actions {
    gap: 12px;
  }

  .home-header__actions {
    grid-area: actions;
    order: unset;
    margin-left: 0;
    justify-self: end;
  }
}

@media (min-width: 1280px) {
  .home-header {
    height: auto;
  }

  .home-header__inner {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px;
    flex-wrap: nowrap;
    height: var(--figma-header-height);
    padding-top: 4px;
    padding-bottom: 4px;
  }

  .home-header__start {
    order: unset;
    justify-self: start;
    flex: unset;
    gap: 16px;
    margin-right: 0;
  }

  .home-header__logo {
    order: unset;
    margin-right: 0;
  }

  .home-header__nav {
    order: unset;
    width: auto;
    margin-right: 0;
    padding-left: 0;
    padding-bottom: 6px;
    gap: 24px;
    overflow: visible;
  }

  .home-header__center {
    order: unset;
    justify-self: stretch;
    width: 100%;
    gap: 20px;
  }

  .home-header__quick-actions {
    gap: 20px;
  }

  .home-header__center :deep(.header-search) {
    flex: 1 1 auto;
    width: auto;
    min-width: 160px;
    max-width: none;
  }

  .home-header__actions {
    order: unset;
    justify-self: end;
    margin-left: 0;
    gap: 16px;
    flex-shrink: 0;
  }

  .home-header__nav-item {
    padding: 0;
    min-height: 0;
  }

  .home-header__action {
    padding: 0;
    min-width: auto;
    min-height: auto;
    flex-shrink: 0;
  }

  .home-header__action-label {
    display: inline;
  }

  .home-header__cta {
    height: 40px;
    padding: 11px 18px 12px 19px;
    font-size: 14px;
  }
}
</style>
