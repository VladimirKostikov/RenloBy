<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import type { ListingContactRequestPayload, ListingStatusChangedPayload, UserNotificationDto } from '@/api/notifications'
import { useNotificationsStore } from '@/stores/notifications'
import type { ListingStatus } from '@/types'

const { t } = useI18n()
const router = useRouter()
const notifications = useNotificationsStore()

onMounted(() => {
  void notifications.load()
})

function isListingPayload(payload: UserNotificationDto['payload']): payload is ListingStatusChangedPayload {
  return typeof payload === 'object'
    && payload !== null
    && 'address' in payload
    && 'status' in payload
}

function isContactRequestPayload(payload: UserNotificationDto['payload']): payload is ListingContactRequestPayload {
  return typeof payload === 'object'
    && payload !== null
    && 'phone' in payload
    && 'requestId' in payload
}

function statusLabel(status: string) {
  const key = status as ListingStatus
  if (
    key === 'draft'
    || key === 'pending'
    || key === 'published'
    || key === 'rejected'
    || key === 'archived'
  ) {
    return t(`account.listings.statuses.${key}`)
  }
  return status
}

function titleFor(item: UserNotificationDto) {
  if (item.type === 'listing_contact_request_created') {
    return t('account.notifications.requestTitle')
  }
  if (item.type !== 'listing_status_changed' || !isListingPayload(item.payload)) {
    return t('account.notifications.genericTitle')
  }
  const status = String(item.payload.status)
  if (status === 'published' || status === 'rejected' || status === 'pending' || status === 'archived' || status === 'draft') {
    return t(`account.notifications.statusTitles.${status}`)
  }
  return t('account.notifications.genericTitle')
}

function bodyFor(item: UserNotificationDto) {
  if (item.type === 'listing_contact_request_created' && isContactRequestPayload(item.payload)) {
    return t('account.notifications.requestBody', {
      address: item.payload.address || t('account.notifications.unknownAddress'),
      phone: item.payload.phone || '-',
    })
  }
  if (item.type !== 'listing_status_changed' || !isListingPayload(item.payload)) {
    return ''
  }
  return t('account.notifications.statusBody', {
    address: item.payload.address || t('account.notifications.unknownAddress'),
    status: statusLabel(String(item.payload.status)),
  })
}

function formatDate(value: string) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

async function openItem(item: UserNotificationDto) {
  if (!item.isRead) {
    try {
      await notifications.markRead(item.id)
    } catch {
      // keep list usable even if mark-read fails
    }
  }

  if (item.type === 'listing_contact_request_created') {
    void router.push('/account/seller/requests')
    return
  }

  if (item.type === 'listing_status_changed' && isListingPayload(item.payload)) {
    void router.push('/account/seller/listings')
  }
}

async function markAll() {
  await notifications.markAllRead()
}
</script>

<template>
  <div class="account-notifications">
    <header class="account-notifications__header">
      <div>
        <h1 class="account-notifications__title">{{ t('account.notifications.title') }}</h1>
        <p class="account-notifications__subtitle">{{ t('account.notifications.subtitle') }}</p>
      </div>
      <button
        v-if="notifications.hasUnread"
        type="button"
        class="account-notifications__mark-all"
        @click="markAll"
      >
        {{ t('account.notifications.markAllRead') }}
      </button>
    </header>

    <div v-if="notifications.loading" class="account-notifications__state">
      {{ t('account.notifications.loading') }}
    </div>

    <div v-else-if="notifications.items.length === 0" class="account-notifications__empty">
      <img data-theme-ink src="/figma/account-notifications.svg" alt="" width="40" height="40" />
      <p>{{ t('account.notifications.empty') }}</p>
      <p class="account-notifications__hint">{{ t('account.notifications.emptyHint') }}</p>
      <div class="account-notifications__actions">
        <RouterLink to="/account/seller/listings" class="account-notifications__link account-notifications__link--accent">
          {{ t('account.notifications.openListings') }}
        </RouterLink>
      </div>
    </div>

    <ul v-else class="account-notifications__list">
      <li v-for="item in notifications.items" :key="item.id">
        <button
          type="button"
          class="account-notifications__item"
          :class="{ 'account-notifications__item--unread': !item.isRead }"
          @click="openItem(item)"
        >
          <span v-if="!item.isRead" class="account-notifications__dot" aria-hidden="true" />
          <span class="account-notifications__content">
            <span class="account-notifications__item-title">{{ titleFor(item) }}</span>
            <span class="account-notifications__item-body">{{ bodyFor(item) }}</span>
            <span class="account-notifications__item-date">{{ formatDate(item.createdAt) }}</span>
          </span>
        </button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.account-notifications__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 24px;
}

.account-notifications__title {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
  color: var(--color-text);
}

.account-notifications__subtitle {
  margin: 0;
  font-size: 15px;
  color: var(--color-text-muted);
}

.account-notifications__mark-all {
  min-height: 36px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: var(--radius-md, 8px);
  background: var(--figma-surface);
  color: var(--color-text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.account-notifications__state,
.account-notifications__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  min-height: 240px;
  padding: 32px 16px;
  text-align: center;
  color: var(--color-text);
}

.account-notifications__hint {
  margin: 0;
  max-width: 420px;
  font-size: 14px;
  color: var(--color-text-muted);
}

.account-notifications__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
  margin-top: 12px;
}

.account-notifications__link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 0 18px;
  border-radius: var(--radius-md, 8px);
  border: 1px solid var(--figma-border);
  background: var(--color-bg-elevated);
  color: var(--color-text);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}

.account-notifications__link--accent {
  border-color: transparent;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.account-notifications__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.account-notifications__item {
  position: relative;
  display: flex;
  width: 100%;
  gap: 12px;
  padding: 14px 16px;
  border: 1px solid var(--figma-border);
  border-radius: var(--radius-md, 8px);
  background: var(--color-bg-elevated);
  text-align: left;
  cursor: pointer;
}

.account-notifications__item--unread {
  background: color-mix(in srgb, var(--figma-accent) 6%, var(--figma-mix-base));
  border-color: color-mix(in srgb, var(--figma-accent) 28%, var(--figma-border));
}

.account-notifications__dot {
  flex-shrink: 0;
  width: 8px;
  height: 8px;
  margin-top: 6px;
  border-radius: 50%;
  background: var(--figma-accent);
}

.account-notifications__content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.account-notifications__item-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text);
}

.account-notifications__item-body {
  font-size: 14px;
  color: var(--color-text-muted);
}

.account-notifications__item-date {
  font-size: 12px;
  color: var(--color-text-muted);
}

@media (max-width: 767px) {
  .account-notifications__title {
    font-size: 22px;
  }

  .account-notifications__item {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
}
</style>
