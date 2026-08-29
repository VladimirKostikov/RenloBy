<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AppFooter from '@/components/layout/AppFooter.vue'
import HomeHeader from '@/components/layout/HomeHeader.vue'
import ContainerPreloader from '@/components/ContainerPreloader.vue'
import AppToast from '@/components/AppToast.vue'
import { useHeadSnippets } from '@/composables/useHeadSnippets'
import { useRoutePathPending } from '@/composables/useRoutePathPending'
import AuthModal from '@/modules/auth/components/AuthModal.vue'
import AuthSuccessModal from '@/modules/auth/components/AuthSuccessModal.vue'
import CookieConsentBanner from '@/modules/consent/CookieConsentBanner.vue'
import AiChatWidget from '@/modules/ai-chat/components/AiChatWidget.vue'
import AiAssistantPanel from '@/modules/consultant/components/AiAssistantPanel.vue'
import { useAiAssistantStore } from '@/stores/aiAssistant'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
useHeadSnippets()
const aiAssistant = useAiAssistantStore()

const mainRef = ref<HTMLElement | null>(null)
const lockedMinHeight = ref<number | null>(null)
const routePending = useRoutePathPending((to, from) => {
  if (to.path.startsWith('/account') && from.path.startsWith('/account')) {
    return false
  }
  if (to.path.startsWith('/admin') && from.path.startsWith('/admin')) {
    return false
  }
  return true
})

const mainStyle = computed(() => (
  lockedMinHeight.value
    ? { minHeight: `${lockedMinHeight.value}px` }
    : undefined
))

watch(routePending, async (pending) => {
  if (pending) {
    lockedMinHeight.value = mainRef.value?.offsetHeight ?? null
    return
  }

  await nextTick()
  window.setTimeout(() => {
    lockedMinHeight.value = null
  }, 240)
})

onMounted(() => {
  void aiAssistant.initialize()
  void router.isReady()
})

const isAdminRoute = computed(() => route.path.startsWith('/admin'))
const isSearchMapRoute = computed(() => (
  route.name === 'search-map' || route.name === 'search-listing-detail'
))
const showHeader = computed(() => !isAdminRoute.value)
const showFooter = computed(() => !isAdminRoute.value && !isSearchMapRoute.value)
const showMainPreloader = computed(() => routePending.value && !isSearchMapRoute.value)
</script>

<template>
  <div
    class="app-shell"
    :class="{
      'app-shell--public': showHeader,
      'app-shell--viewport': isSearchMapRoute,
    }"
  >
    <HomeHeader v-if="showHeader" sticky />
    <div
      ref="mainRef"
      class="app-shell__main"
      :style="mainStyle"
      :aria-busy="showMainPreloader"
    >
      <ContainerPreloader :show="showMainPreloader" :label="t('listing.loading')" />
      <RouterView />
    </div>
    <AppFooter v-if="showFooter" />
    <AuthModal />
    <AuthSuccessModal />
    <CookieConsentBanner />
    <AiAssistantPanel />
    <AiChatWidget />
    <AppToast />
  </div>
</template>

<style scoped>
.app-shell {
  display: flex;
  flex-direction: column;
  flex: 1 0 auto;
  width: 100%;
  min-height: 100%;
}

.app-shell--public {
  min-height: 100vh;
  min-height: 100dvh;
}

.app-shell--viewport {
  height: 100vh;
  height: 100dvh;
  max-height: 100vh;
  max-height: 100dvh;
  overflow: hidden;
}

.app-shell__main {
  position: relative;
  display: flex;
  flex-direction: column;
  flex: 1 0 auto;
  width: 100%;
  min-width: 0;
}

.app-shell--viewport .app-shell__main {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

.app-shell__main > :deep(*) {
  flex: 1 0 auto;
  width: 100%;
  min-width: 0;
}

.app-shell__main > :deep(.container-preloader) {
  flex: 0 0 auto;
}

.app-shell--viewport .app-shell__main > :deep(*) {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}
</style>
