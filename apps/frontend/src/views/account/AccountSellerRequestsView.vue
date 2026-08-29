<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  deleteMyListingRequest,
  fetchMyListingRequests,
  type SellerListingRequestDto,
} from '@/api/account'
import AccountListingActionButton from '@/modules/account/components/AccountListingActionButton.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

const { t, locale } = useI18n()
useRoutePageSeo({ noindex: true })

const items = ref<SellerListingRequestDto[]>([])
const loading = ref(false)
const error = ref(false)
const actionId = ref<number | null>(null)

const isEmpty = computed(() => !loading.value && !error.value && items.value.length === 0)

function statusLabel(status: string): string {
  const key = `account.requests.statuses.${status}`
  return t(key) !== key ? t(key) : status
}

function formatDate(value: string): string {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }
  return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-GB' : 'ru-RU', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

async function load() {
  loading.value = true
  error.value = false
  try {
    items.value = await fetchMyListingRequests()
  } catch {
    error.value = true
    items.value = []
  } finally {
    loading.value = false
  }
}

async function deleteRequest(item: SellerListingRequestDto) {
  if (!window.confirm(t('account.requests.deleteConfirm'))) {
    return
  }
  actionId.value = item.id
  error.value = false
  try {
    await deleteMyListingRequest(item.id)
    items.value = items.value.filter((row) => row.id !== item.id)
  } catch {
    error.value = true
  } finally {
    actionId.value = null
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="seller-requests">
    <header class="seller-requests__header">
      <div>
        <h1 class="seller-requests__title">{{ t('account.requests.title') }}</h1>
        <p class="seller-requests__subtitle">{{ t('account.requests.subtitle') }}</p>
      </div>
    </header>

    <div v-if="loading" class="seller-requests__state">{{ t('listing.loading') }}</div>
    <div v-else-if="error && items.length === 0" class="seller-requests__state">{{ t('account.error') }}</div>
    <div v-else-if="isEmpty" class="seller-requests__state">{{ t('account.requests.empty') }}</div>

    <div v-else class="seller-requests__list">
      <p v-if="error" class="seller-requests__inline-error" role="alert">{{ t('account.error') }}</p>
      <article
        v-for="item in items"
        :key="item.id"
        class="seller-requests__card"
      >
        <div class="seller-requests__card-top">
          <div class="seller-requests__card-main">
            <p class="seller-requests__address">
              {{ item.listingAddress || t('account.requests.unknownListing') }}
            </p>
            <p class="seller-requests__meta">
              {{ t('account.requests.listingId', { id: item.listingId }) }}
              ·
              {{ formatDate(item.createdAt) }}
            </p>
          </div>
          <div class="seller-requests__card-actions">
            <span
              class="seller-requests__status"
              :data-status="item.status"
            >
              {{ statusLabel(item.status) }}
            </span>
            <AccountListingActionButton
              variant="delete"
              :title="t('account.requests.delete')"
              :disabled="actionId === item.id"
              data-testid="request-delete"
              @click="deleteRequest(item)"
            />
          </div>
        </div>

        <div class="seller-requests__contact">
          <div v-if="item.name" class="seller-requests__row">
            <span class="seller-requests__row-label">{{ t('account.requests.name') }}</span>
            <strong>{{ item.name }}</strong>
          </div>
          <div class="seller-requests__row">
            <span class="seller-requests__row-label">{{ t('account.requests.phone') }}</span>
            <a class="seller-requests__phone" :href="`tel:${item.phone.replace(/[^\d+]/g, '')}`">
              {{ item.phone }}
            </a>
          </div>
        </div>

        <p class="seller-requests__message">{{ item.message }}</p>
      </article>
    </div>
  </div>
</template>

<style scoped>
.seller-requests {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  min-width: 0;
}

.seller-requests__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
}

.seller-requests__title {
  margin: 0 0 4px;
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text);
}

.seller-requests__subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-muted);
}

.seller-requests__state {
  padding: 24px 16px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: var(--color-bg-elevated);
  color: var(--color-text-muted);
  font-size: 14px;
}

.seller-requests__inline-error {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  background: #fdeeee;
  color: #c62828;
  font-size: 13px;
  font-weight: 600;
}

.seller-requests__list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.seller-requests__card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: var(--color-bg-elevated);
}

.seller-requests__card-top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.seller-requests__card-main {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.seller-requests__card-actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.seller-requests__address {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
}

.seller-requests__meta {
  margin: 0;
  font-size: 13px;
  color: var(--figma-text-muted, #929292);
}

.seller-requests__status {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 8px;
  background: color-mix(in srgb, var(--figma-accent) 10%, var(--figma-mix-base));
  color: var(--figma-accent);
  font-size: 12px;
  font-weight: 700;
}

.seller-requests__status[data-status='contacted'] {
  background: color-mix(in srgb, #2563eb 12%, var(--figma-mix-base));
  color: #2563eb;
}

.seller-requests__status[data-status='closed'] {
  background: color-mix(in srgb, #16a34a 12%, var(--figma-mix-base));
  color: #15803d;
}

.seller-requests__contact {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.seller-requests__row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.seller-requests__row-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-text-muted, #929292);
}

.seller-requests__phone {
  color: var(--figma-accent);
  font-weight: 700;
  text-decoration: none;
}

.seller-requests__message {
  margin: 0;
  padding: 12px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--figma-accent) 5%, #f7f7f8);
  font-size: 14px;
  line-height: 1.45;
  color: var(--color-text);
  overflow-wrap: anywhere;
}
</style>
