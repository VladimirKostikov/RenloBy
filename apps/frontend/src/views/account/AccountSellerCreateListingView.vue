<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import ListingWizardPanel from '@/modules/seller/components/ListingWizardPanel.vue'
import { getSellerProfileMissing, isSellerProfileComplete } from '@/lib/sellerProfileGate'
import { useAuthStore } from '@/stores/auth'
import * as authApi from '@/api/auth'

const { t } = useI18n()
const auth = useAuthStore()

const profileReady = computed(() => isSellerProfileComplete(auth.user))
const missingKeys = computed(() => getSellerProfileMissing(auth.user))

onMounted(async () => {
  try {
    auth.user = await authApi.fetchMe()
  } catch {
    // keep current store user
  }
})
</script>

<template>
  <div class="account-create-listing">
    <div v-if="!profileReady" class="account-create-listing__gate">
      <h1 class="account-create-listing__gate-title">{{ t('account.sellerGate.title') }}</h1>
      <p class="account-create-listing__gate-text">{{ t('account.sellerGate.subtitle') }}</p>
      <ul class="account-create-listing__gate-list">
        <li v-for="key in missingKeys" :key="key">
          {{ t(`account.sellerGate.missing.${key}`) }}
        </li>
      </ul>
      <RouterLink
        class="account-create-listing__gate-cta"
        :to="{ path: '/account/user/profile', query: { next: '/account/seller/create' } }"
      >
        {{ t('account.sellerGate.openProfile') }}
      </RouterLink>
    </div>
    <ListingWizardPanel v-else />
  </div>
</template>

<style scoped>
.account-create-listing {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  min-height: 100%;
}

.account-create-listing :deep(.listing-wizard) {
  flex: 1 1 auto;
  width: 100%;
  min-height: 100%;
}

.account-create-listing__gate {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 560px;
  padding: 8px 0;
}

.account-create-listing__gate-title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text);
}

.account-create-listing__gate-text {
  margin: 0;
  font-size: 15px;
  color: var(--color-text-muted);
}

.account-create-listing__gate-list {
  margin: 0;
  padding-left: 18px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 14px;
  color: var(--color-text);
}

.account-create-listing__gate-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  align-self: flex-start;
  min-height: 44px;
  padding: 0 18px;
  border-radius: 10px;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
}
</style>
