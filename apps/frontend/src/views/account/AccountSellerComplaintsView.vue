<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchMyListingReports, type SellerListingReportDto } from '@/api/account'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

const { t, locale } = useI18n()
useRoutePageSeo({ noindex: true })

const items = ref<SellerListingReportDto[]>([])
const loading = ref(false)
const error = ref(false)

const isEmpty = computed(() => !loading.value && !error.value && items.value.length === 0)

function reasonLabel(reason: string): string {
  const key = `account.complaints.reasons.${reason}`
  return t(key) !== key ? t(key) : reason
}

function statusLabel(status: string): string {
  const key = `account.complaints.statuses.${status}`
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
    items.value = await fetchMyListingReports()
  } catch {
    error.value = true
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="seller-complaints">
    <header class="seller-complaints__header">
      <div>
        <h1 class="seller-complaints__title">{{ t('account.complaints.title') }}</h1>
        <p class="seller-complaints__subtitle">{{ t('account.complaints.subtitle') }}</p>
      </div>
    </header>

    <div v-if="loading" class="seller-complaints__state">{{ t('listing.loading') }}</div>
    <div v-else-if="error" class="seller-complaints__state">{{ t('account.error') }}</div>
    <div v-else-if="isEmpty" class="seller-complaints__state">{{ t('account.complaints.empty') }}</div>

    <div v-else class="seller-complaints__list">
      <article
        v-for="item in items"
        :key="item.id"
        class="seller-complaints__card"
      >
        <div class="seller-complaints__card-top">
          <div class="seller-complaints__card-main">
            <p class="seller-complaints__address">
              {{ item.listingAddress || t('account.complaints.unknownListing') }}
            </p>
            <p class="seller-complaints__meta">
              {{ t('account.complaints.listingId', { id: item.listingId }) }}
              ·
              {{ formatDate(item.createdAt) }}
            </p>
          </div>
          <span
            class="seller-complaints__status"
            :data-status="item.status"
          >
            {{ statusLabel(item.status) }}
          </span>
        </div>

        <div class="seller-complaints__reason">
          <span class="seller-complaints__reason-label">{{ t('account.complaints.reason') }}</span>
          <strong>{{ reasonLabel(item.reason) }}</strong>
        </div>

        <p v-if="item.comment" class="seller-complaints__comment">{{ item.comment }}</p>
      </article>
    </div>
  </div>
</template>

<style scoped>
.seller-complaints {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  min-width: 0;
}

.seller-complaints__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
}

.seller-complaints__title {
  margin: 0 0 4px;
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text);
}

.seller-complaints__subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-muted);
}

.seller-complaints__state {
  padding: 24px 16px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: var(--color-bg-elevated);
  color: var(--color-text-muted);
  font-size: 14px;
}

.seller-complaints__list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.seller-complaints__card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: var(--color-bg-elevated);
}

.seller-complaints__card-top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.seller-complaints__card-main {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.seller-complaints__address {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
}

.seller-complaints__meta {
  margin: 0;
  font-size: 13px;
  color: var(--figma-text-muted, #929292);
}

.seller-complaints__status {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--figma-accent) 10%, var(--figma-mix-base));
  color: var(--figma-accent);
  font-size: 12px;
  font-weight: 700;
}

.seller-complaints__status[data-status='reviewed'] {
  background: color-mix(in srgb, #2563eb 12%, var(--figma-mix-base));
  color: #2563eb;
}

.seller-complaints__status[data-status='closed'] {
  background: color-mix(in srgb, #16a34a 12%, var(--figma-mix-base));
  color: #15803d;
}

.seller-complaints__reason {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.seller-complaints__reason-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-text-muted, #929292);
}

.seller-complaints__comment {
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
