<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { createMyPayment } from '@/api/payments'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'
import PromotionTariffCard from '@/modules/promotion/components/PromotionTariffCard.vue'
import {
  PAYMENT_CURRENCIES,
  loadPromotionTariffs,
  paymentCurrencyApiCode,
} from '@/modules/promotion/lib/promotionTariffs'
import { useAuthStore } from '@/stores/auth'
import type { PaymentCurrencyCode, PromotionTariff, PromotionTariffId } from '@/types/promotion'

const props = withDefaults(
  defineProps<{
    embedded?: boolean
    compact?: boolean
    returnPath?: string
  }>(),
  {
    embedded: false,
    compact: false,
    returnPath: '/account/seller/payments',
  },
)

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const authModal = useAuthModal()

const tariffs = ref<PromotionTariff[]>([])
const currency = ref<PaymentCurrencyCode>('byn')
const payingId = ref<PromotionTariffId | null>(null)
const statusMessage = ref('')
const errorMessage = ref('')

function isSafePaymentUrl(url: string): boolean {
  if (url.startsWith('/') && !url.startsWith('//')) {
    return true
  }
  try {
    const parsed = new URL(url, window.location.origin)
    return parsed.protocol === 'https:' || parsed.origin === window.location.origin
  } catch {
    return false
  }
}

async function handleSelect(tariffId: PromotionTariffId) {
  errorMessage.value = ''
  statusMessage.value = ''

  if (!auth.isAuthenticated) {
    statusMessage.value = t('promotion.loginRequired')
    authModal.openLogin()
    return
  }

  const tariff = tariffs.value.find((item) => item.id === tariffId)
  if (!tariff) {
    return
  }

  payingId.value = tariffId
  try {
    const tx = await createMyPayment({
      amount: String(tariff.priceUsd),
      currency: paymentCurrencyApiCode(currency.value),
      description: t(tariff.nameKey),
      returnUrl: `${window.location.origin}${props.returnPath}`,
      metadata: { tariffId },
      isTest: false,
    })
    if (tx.confirmationUrl && isSafePaymentUrl(tx.confirmationUrl)) {
      window.location.assign(tx.confirmationUrl)
      return
    }
    statusMessage.value = t('promotion.paymentCreated')
    await router.push(props.returnPath)
  } catch {
    errorMessage.value = t('promotion.payError')
  } finally {
    payingId.value = null
  }
}

onMounted(() => {
  void loadPromotionTariffs().then((items) => {
    tariffs.value = items
  })
})
</script>

<template>
  <div
    class="promotion-panel"
    :class="{
      'promotion-panel--embedded': embedded,
      'promotion-panel--compact': compact,
    }"
  >
    <header v-if="!compact" class="promotion-panel__header">
      <h1 class="promotion-panel__title">{{ t('promotion.title') }}</h1>
      <p class="promotion-panel__subtitle">{{ t('promotion.subtitle') }}</p>
    </header>

    <div class="promotion-panel__currency" role="group" :aria-label="t('promotion.currencyLabel')">
      <button
        v-for="code in PAYMENT_CURRENCIES"
        :key="code"
        type="button"
        class="promotion-panel__currency-btn"
        :class="{ 'promotion-panel__currency-btn--active': currency === code }"
        :aria-pressed="currency === code"
        @click="currency = code"
      >
        {{ t(`promotion.currency.${code}`) }}
      </button>
    </div>

    <p v-if="statusMessage" class="promotion-panel__status" role="status">
      {{ statusMessage }}
    </p>
    <p v-if="errorMessage" class="promotion-panel__error" role="alert">
      {{ errorMessage }}
    </p>

    <div class="promotion-panel__grid">
      <PromotionTariffCard
        v-for="tariff in tariffs"
        :key="tariff.id"
        :tariff="tariff"
        :currency="currency"
        :compact="compact"
        :paying="payingId === tariff.id"
        :selected="payingId === tariff.id"
        @select="handleSelect"
      />
    </div>
  </div>
</template>

<style scoped>
.promotion-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.promotion-panel__header {
  margin-bottom: 15px;
  text-align: center;
  max-width: 560px;
}

.promotion-panel__title {
  margin: 0 0 6px;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
}

.promotion-panel--embedded .promotion-panel__title {
  font-size: 18px;
}

.promotion-panel__subtitle {
  margin: 0;
  font-size: 14px;
  line-height: 1.4;
  color: var(--figma-text-muted);
}

.promotion-panel__currency {
  display: inline-flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 6px;
  margin-bottom: 18px;
  padding: 4px;
  border-radius: 10px;
  background: rgba(0, 0, 0, 0.04);
}

.promotion-panel__currency-btn {
  min-height: 36px;
  min-width: 64px;
  padding: 0 12px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--figma-text-muted);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease, transform 0.15s ease;
}

.promotion-panel__currency-btn:hover {
  color: var(--figma-text);
}

.promotion-panel__currency-btn--active {
  background: var(--figma-surface);
  color: var(--figma-accent);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.promotion-panel__currency-btn:active {
  transform: scale(0.97);
}

.promotion-panel__status,
.promotion-panel__error {
  margin: 0 0 16px;
  padding: 10px 14px;
  border-radius: var(--figma-radius-chip);
  font-size: 14px;
  text-align: center;
  max-width: 480px;
  width: 100%;
  box-sizing: border-box;
}

.promotion-panel__status {
  border: 1px solid var(--figma-border);
  background: var(--figma-surface);
  color: var(--figma-text-muted);
}

.promotion-panel__error {
  border: 1px solid rgba(225, 69, 84, 0.35);
  background: color-mix(in srgb, var(--figma-accent) 6%, var(--figma-mix-base));
  color: var(--figma-accent);
}

.promotion-panel__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 180px), 1fr));
  gap: 9px;
  justify-content: center;
  align-items: stretch;
  width: 100%;
  max-width: 960px;
  padding: 8px 0;
  box-sizing: border-box;
}

.promotion-panel--embedded {
  align-items: stretch;
}

.promotion-panel--embedded .promotion-panel__header {
  text-align: center;
  max-width: 560px;
  align-self: center;
  width: 100%;
}

.promotion-panel--embedded .promotion-panel__currency {
  align-self: center;
}

.promotion-panel--embedded .promotion-panel__status,
.promotion-panel--embedded .promotion-panel__error {
  text-align: center;
  align-self: center;
}

.promotion-panel--embedded .promotion-panel__grid {
  max-width: none;
  justify-content: stretch;
  padding: 0;
}

.promotion-panel--compact .promotion-panel__grid,
.promotion-panel--embedded.promotion-panel--compact .promotion-panel__grid {
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
  gap: 8px;
  max-width: none;
}

.promotion-panel--compact {
  align-items: stretch;
}

.promotion-panel--embedded :deep(.promotion-card),
.promotion-panel--embedded :deep(.promotion-card--popular),
.promotion-panel--compact :deep(.promotion-card),
.promotion-panel--compact :deep(.promotion-card--popular) {
  max-width: none;
  width: 100%;
  transform: none;
}

.promotion-panel--embedded :deep(.promotion-card__body:hover),
.promotion-panel--embedded :deep(.promotion-card--popular .promotion-card__body:hover),
.promotion-panel--compact :deep(.promotion-card__body:hover),
.promotion-panel--compact :deep(.promotion-card--popular .promotion-card__body:hover) {
  transform: none;
}

@media (max-width: 767px) {
  .promotion-panel__title {
    font-size: 20px;
  }

  .promotion-panel__subtitle {
    font-size: 14px;
  }

  .promotion-panel__grid {
    grid-template-columns: 1fr;
    max-width: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .promotion-panel__currency-btn {
    transition: none;
  }
}

</style>
