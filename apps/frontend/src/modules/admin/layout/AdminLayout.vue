<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import ContainerPreloader from '@/components/ContainerPreloader.vue'
import { useRoutePathPending } from '@/composables/useRoutePathPending'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import AppLogomark from '@/components/layout/AppLogomark.vue'
import AdminNavIcon from '@/modules/admin/components/AdminNavIcon.vue'
import AdminTestModeToggle from '@/modules/admin/components/AdminTestModeToggle.vue'
import { adminNavItems } from '@/modules/admin/nav'
import '@/modules/admin/styles/admin-theme.css'
import '@/modules/admin/styles/admin-buttons.css'

const { t } = useI18n()
const route = useRoute()
const contentRef = ref<HTMLElement | null>(null)
const lockedMinHeight = ref<number | null>(null)
const routePending = useRoutePathPending((to, from) => (
  to.path.startsWith('/admin') || from.path.startsWith('/admin')
))

const contentStyle = computed(() => (
  lockedMinHeight.value
    ? { minHeight: `${lockedMinHeight.value}px` }
    : undefined
))

watch(routePending, async (pending) => {
  if (pending) {
    lockedMinHeight.value = contentRef.value?.offsetHeight ?? null
    return
  }

  await nextTick()
  window.setTimeout(() => {
    lockedMinHeight.value = null
  }, 240)
})

watch(
  () => route.path,
  async (path, previousPath) => {
    if (previousPath === undefined || path === previousPath) {
      return
    }

    await nextTick()
    contentRef.value?.scrollTo({ top: 0, left: 0 })
    window.scrollTo({ top: 0, left: 0 })
  },
)

useRoutePageSeo({ noindex: true })

function isActive(to: string, exact?: boolean) {
  if (exact) {
    return route.path === to
  }
  return route.path === to || route.path.startsWith(`${to}/`)
}
</script>

<template>
  <div class="admin-layout" data-theme="light">
    <aside class="admin-layout__sidebar">
      <div class="admin-layout__brand">
        <RouterLink to="/" class="admin-layout__brand-link">
          <AppLogomark :width="32" :height="32" image-class="admin-layout__logomark" />
          <span class="admin-layout__brand-name">{{ t('app.name') }}</span>
        </RouterLink>
        <span class="admin-layout__brand-label">{{ t('admin.title') }}</span>
      </div>
      <nav class="admin-layout__nav" :aria-label="t('admin.title')">
        <RouterLink
          v-for="item in adminNavItems"
          :key="item.to"
          :to="item.to"
          class="admin-layout__link"
          :class="{ 'admin-layout__link--active': isActive(item.to, item.exact) }"
        >
          <AdminNavIcon :name="item.icon" />
          <span>{{ t(item.labelKey) }}</span>
        </RouterLink>
      </nav>
      <div class="admin-layout__sidebar-footer">
        <AdminTestModeToggle />
        <RouterLink to="/" class="admin-layout__back">
          <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          {{ t('admin.backToSite') }}
        </RouterLink>
      </div>
    </aside>
    <main class="admin-layout__main">
      <header class="admin-layout__topbar">
        <p class="admin-layout__eyebrow">{{ t('admin.title') }}</p>
      </header>
      <div
        ref="contentRef"
        class="admin-layout__content"
        :style="contentStyle"
        :aria-busy="routePending"
      >
        <ContainerPreloader :show="routePending" :label="t('listing.loading')" />
        <RouterView />
      </div>
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  display: flex;
  height: 100vh;
  height: 100dvh;
  max-height: 100vh;
  max-height: 100dvh;
  overflow: hidden;
  background: #fff;
}

.admin-layout__sidebar {
  position: sticky;
  top: 0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  width: 260px;
  height: 100%;
  max-height: 100%;
  padding: 20px 14px;
  background: #fff;
  box-shadow: var(--admin-shadow-sidebar);
  z-index: 2;
  overflow: hidden;
}

.admin-layout__brand {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  gap: 4px;
  margin-bottom: 16px;
  padding: 0 8px;
}

.admin-layout__brand-link {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: inherit;
  text-decoration: none;
  transition: opacity 160ms ease;
}

.admin-layout__brand-link:hover {
  opacity: 0.82;
}

.admin-layout__logomark {
  width: 32px;
  height: 32px;
  object-fit: contain;
  flex-shrink: 0;
}

.admin-layout__brand-name {
  font-size: 16px;
  font-weight: 700;
  color: var(--admin-text);
}

.admin-layout__brand-label {
  font-size: 12px;
  color: var(--admin-text-muted);
  font-weight: 600;
}

.admin-layout__nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1 1 auto;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
  padding-right: 2px;
}

.admin-layout__link {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 42px;
  padding: 8px 12px;
  border-radius: 8px;
  color: var(--admin-text-muted);
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
}

.admin-layout__link:hover {
  background: var(--admin-row-hover);
  color: var(--admin-text);
}

.admin-layout__link--active {
  background: var(--admin-accent-muted);
  color: var(--admin-accent);
}

.admin-layout__link:active {
  transform: scale(0.99);
}

.admin-layout__sidebar-footer {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  gap: 4px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--admin-border, #e8eaef);
}

.admin-layout__back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 40px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--admin-text-muted);
  text-decoration: none;
  transition: background-color 160ms ease, color 160ms ease;
}

.admin-layout__back:hover {
  background: var(--admin-row-hover);
  color: var(--admin-accent);
}

.admin-layout__main {
  flex: 1;
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: #fff;
  overflow: hidden;
}

.admin-layout__topbar {
  padding: 24px 28px 0;
}

.admin-layout__eyebrow {
  margin: 0;
  font-size: 12px;
  font-weight: 600;
  color: var(--admin-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.admin-layout__content {
  position: relative;
  flex: 1;
  min-height: 0;
  padding: 12px 28px 32px;
  overflow: auto;
  -webkit-overflow-scrolling: touch;
}

@media (max-width: 1279px) {
  .admin-layout {
    flex-direction: column;
    height: auto;
    max-height: none;
    min-height: 100vh;
    min-height: 100dvh;
    overflow: visible;
  }

  .admin-layout__sidebar {
    position: sticky;
    top: 0;
    width: 100%;
    height: auto;
    max-height: min(70vh, 70dvh);
    box-shadow: 0 4px 18px rgba(26, 29, 38, 0.06);
  }

  .admin-layout__nav {
    flex-direction: row;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 6px;
    padding-bottom: 4px;
  }

  .admin-layout__link {
    flex-shrink: 0;
  }

  .admin-layout__sidebar-footer {
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
  }

  .admin-layout__main {
    overflow: visible;
  }

  .admin-layout__content {
    overflow: visible;
  }

  .admin-layout__topbar,
  .admin-layout__content {
    padding-left: 16px;
    padding-right: 16px;
  }
}

@media (max-width: 767px) {
  .admin-layout__topbar,
  .admin-layout__content {
    padding-left: 12px;
    padding-right: 12px;
  }

  .admin-layout__title {
    font-size: 18px;
  }

  .admin-layout__sidebar {
    max-height: none;
  }

  .admin-layout__nav {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
  }

  .admin-layout__link {
    min-height: var(--touch-target-min, 44px);
  }
}
</style>
