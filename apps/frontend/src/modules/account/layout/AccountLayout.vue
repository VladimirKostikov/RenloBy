<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import ContainerPreloader from '@/components/ContainerPreloader.vue'
import { useRoutePathPending } from '@/composables/useRoutePathPending'
import AccountSidebar from '@/modules/account/components/AccountSidebar.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

useRoutePageSeo({ noindex: true })

const { t } = useI18n()
const route = useRoute()
const contentRef = ref<HTMLElement | null>(null)
const lockedMinHeight = ref<number | null>(null)
const routePending = useRoutePathPending((to, from) => (
  to.path.startsWith('/account') || from.path.startsWith('/account')
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
    window.scrollTo({ top: 0, left: 0 })
  },
)
</script>

<template>
  <div class="account-layout">
    <main class="page-shell account-layout__main">
      <div class="account-layout__grid">
        <div class="account-layout__sidebar">
          <AccountSidebar />
        </div>
        <section
          ref="contentRef"
          class="account-layout__content"
          :style="contentStyle"
          :aria-busy="routePending"
        >
          <ContainerPreloader :show="routePending" :label="t('listing.loading')" />
          <RouterView v-slot="{ Component, route }">
            <Transition name="account-page" mode="out-in">
              <component :is="Component" :key="route.fullPath" />
            </Transition>
          </RouterView>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.account-layout {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  width: 100%;
  min-height: 100%;
  background: var(--color-bg-elevated);
}

.account-layout__main {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  width: 100%;
  min-height: 0;
  padding-top: 20px;
  padding-bottom: 20px;
  background: var(--figma-page-bg);
}

.account-layout__grid {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  gap: 16px;
  min-height: 0;
  width: 100%;
}

.account-layout__sidebar {
  min-width: 0;
}

.account-layout__content {
  position: relative;
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-width: 0;
  min-height: 0;
  max-width: 100%;
  width: 100%;
  overflow-x: auto;
  padding: 24px;
  border-radius: var(--figma-radius-chip);
  background: var(--color-bg-elevated);
  border: 1px solid var(--figma-border);
  color: var(--color-text);
}

.account-layout__content > :deep(*) {
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  min-height: 0;
}

.account-layout__content > :deep(.container-preloader) {
  flex: 0 0 auto;
}

@media (min-width: 1280px) {
  .account-layout__grid {
    flex-direction: row;
    align-items: flex-start;
    gap: 22px;
  }

  .account-layout__sidebar {
    position: sticky;
    top: calc(var(--figma-header-height, 88px) + 12px);
    z-index: 20;
    width: 349px;
    flex: 0 0 349px;
    align-self: flex-start;
    max-height: calc(100vh - var(--figma-header-height, 88px) - 24px);
    max-height: calc(100dvh - var(--figma-header-height, 88px) - 24px);
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    scrollbar-width: thin;
  }

  .account-layout__content {
    flex: 1 1 auto;
  }
}

@media (max-width: 767px) {
  .account-layout__main {
    padding-top: 12px;
    padding-bottom: 16px;
  }

  .account-layout__content {
    padding: 14px;
    border-radius: 12px;
  }
}
</style>

<style>
.account-page-enter-active,
.account-page-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.account-page-enter-from,
.account-page-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

@media (prefers-reduced-motion: reduce) {
  .account-page-enter-active,
  .account-page-leave-active {
    transition-duration: 0.01ms;
  }

  .account-page-enter-from,
  .account-page-leave-to {
    transform: none;
  }
}
</style>
