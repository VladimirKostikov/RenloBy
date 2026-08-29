<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  disconnectSellerTelegram,
  fetchSellerTelegramStatus,
  type SellerTelegramStatusDto,
} from '@/api/account'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

const { t } = useI18n()
useRoutePageSeo({ noindex: true })

const status = ref<SellerTelegramStatusDto | null>(null)
const loading = ref(true)
const busy = ref(false)
const error = ref(false)

let pollTimer: ReturnType<typeof setInterval> | null = null

async function load() {
  loading.value = true
  error.value = false
  try {
    status.value = await fetchSellerTelegramStatus()
  } catch {
    error.value = true
    status.value = null
  } finally {
    loading.value = false
  }
}

async function refreshQuiet() {
  try {
    status.value = await fetchSellerTelegramStatus()
  } catch {
    // keep last known status while polling
  }
}

async function disconnect() {
  if (!status.value?.connected || busy.value) {
    return
  }
  if (!window.confirm(t('account.sellerTelegram.disconnectConfirm'))) {
    return
  }
  busy.value = true
  error.value = false
  try {
    status.value = await disconnectSellerTelegram()
  } catch {
    error.value = true
  } finally {
    busy.value = false
  }
}

function startPolling() {
  stopPolling()
  pollTimer = setInterval(() => {
    if (status.value && !status.value.connected) {
      void refreshQuiet()
    }
  }, 4000)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(async () => {
  await load()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <div class="seller-telegram">
    <header class="seller-telegram__header">
      <h1 class="seller-telegram__title">{{ t('account.sellerTelegram.title') }}</h1>
      <p class="seller-telegram__subtitle">{{ t('account.sellerTelegram.subtitle') }}</p>
    </header>

    <div v-if="loading" class="seller-telegram__state">{{ t('listing.loading') }}</div>
    <div v-else-if="error && !status" class="seller-telegram__state">{{ t('account.error') }}</div>

    <template v-else-if="status">
      <section class="seller-telegram__card" data-testid="telegram-status-card">
        <div class="seller-telegram__status-row">
          <span
            class="seller-telegram__badge"
            :data-connected="status.connected ? 'true' : 'false'"
          >
            {{ status.connected
              ? t('account.sellerTelegram.connected')
              : t('account.sellerTelegram.disconnected') }}
          </span>
          <span v-if="status.username" class="seller-telegram__username">
            @{{ status.username }}
          </span>
        </div>

        <p class="seller-telegram__bot">
          {{ t('account.sellerTelegram.bot') }}: @{{ status.botUsername }}
        </p>

        <p v-if="!status.configured" class="seller-telegram__warn">
          {{ t('account.sellerTelegram.notConfigured') }}
        </p>

        <ol v-if="!status.connected" class="seller-telegram__steps">
          <li>{{ t('account.sellerTelegram.stepOpen') }}</li>
          <li>{{ t('account.sellerTelegram.stepStart') }}</li>
          <li>{{ t('account.sellerTelegram.stepReturn') }}</li>
        </ol>

        <div class="seller-telegram__actions">
          <a
            v-if="!status.connected"
            class="seller-telegram__cta"
            :href="status.connectUrl"
            target="_blank"
            rel="noopener noreferrer"
            data-testid="telegram-connect"
          >
            {{ t('account.sellerTelegram.connect') }}
          </a>
          <button
            v-else
            type="button"
            class="seller-telegram__disconnect"
            data-testid="telegram-disconnect"
            :disabled="busy"
            @click="disconnect"
          >
            {{ t('account.sellerTelegram.disconnect') }}
          </button>
          <button
            type="button"
            class="seller-telegram__refresh"
            :disabled="loading || busy"
            @click="load"
          >
            {{ t('account.sellerTelegram.refresh') }}
          </button>
        </div>

        <p v-if="error" class="seller-telegram__error">{{ t('account.error') }}</p>
      </section>
    </template>
  </div>
</template>

<style scoped>
.seller-telegram {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-width: 0;
}

.seller-telegram__header {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.seller-telegram__title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  line-height: 1.25;
}

.seller-telegram__subtitle {
  margin: 0;
  color: var(--color-text-muted, #6b7280);
  font-size: 14px;
  line-height: 1.4;
}

.seller-telegram__state {
  color: var(--color-text-muted, #6b7280);
  font-size: 14px;
}

.seller-telegram__card {
  display: flex;
  flex-direction: column;
  gap: 14px;
  max-width: 560px;
  padding: 18px;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: var(--figma-surface);
}

.seller-telegram__status-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.seller-telegram__badge {
  display: inline-flex;
  align-items: center;
  min-height: 32px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
}

.seller-telegram__badge[data-connected='true'] {
  background: var(--figma-success-bg);
  color: #047857;
}

.seller-telegram__badge[data-connected='false'] {
  background: var(--color-bg-muted);
  color: var(--color-text-muted);
}

.seller-telegram__username {
  color: var(--color-text-muted, #6b7280);
  font-size: 13px;
}

.seller-telegram__bot {
  margin: 0;
  font-size: 14px;
  line-height: 1.4;
}

.seller-telegram__warn {
  margin: 0;
  color: #b45309;
  font-size: 13px;
  line-height: 1.4;
}

.seller-telegram__steps {
  margin: 0;
  padding-left: 18px;
  color: var(--color-text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.seller-telegram__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.seller-telegram__cta,
.seller-telegram__disconnect,
.seller-telegram__refresh {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 0 16px;
  border-radius: var(--figma-radius-btn, 10px);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}

.seller-telegram__cta {
  border: none;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.seller-telegram__disconnect {
  border: 1px solid #fecaca;
  background: var(--color-danger-bg);
  color: var(--color-danger);
}

.seller-telegram__refresh {
  border: 1px solid var(--figma-border);
  background: var(--figma-surface);
  color: var(--figma-ink-secondary);
}

.seller-telegram__cta:hover,
.seller-telegram__disconnect:hover,
.seller-telegram__refresh:hover {
  filter: brightness(0.98);
}

.seller-telegram__disconnect:disabled,
.seller-telegram__refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.seller-telegram__error {
  margin: 0;
  color: var(--color-danger);
  font-size: 13px;
}
</style>
