<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatTariffPrice } from '@/modules/promotion/lib/promotionTariffs'
import type { PaymentCurrencyCode, PromotionTariff } from '@/types/promotion'

const props = withDefaults(
  defineProps<{
    tariff: PromotionTariff
    currency: PaymentCurrencyCode
    selected?: boolean
    compact?: boolean
    paying?: boolean
  }>(),
  {
    selected: false,
    compact: false,
    paying: false,
  },
)

const emit = defineEmits<{
  select: [id: PromotionTariff['id']]
}>()

const { t } = useI18n()

const priceLabel = computed(() => formatTariffPrice(props.tariff.priceUsd, props.currency, props.tariff))

function handleSelect() {
  if (props.paying) {
    return
  }
  emit('select', props.tariff.id)
}
</script>

<template>
  <article
    class="promotion-card"
    :class="{
      'promotion-card--popular': tariff.popular,
      'promotion-card--selected': selected,
      'promotion-card--compact': compact,
    }"
  >
    <div v-if="tariff.popular" class="promotion-card__badge">
      {{ t('promotion.popularBadge') }}
    </div>

    <div class="promotion-card__body">
      <div class="promotion-card__icon-wrap">
        <img :src="tariff.icon" alt="" class="promotion-card__icon" width="30" height="30" />
      </div>

      <h2 class="promotion-card__name">{{ t(tariff.nameKey) }}</h2>
      <p class="promotion-card__description">{{ t(tariff.descriptionKey) }}</p>

      <p class="promotion-card__price">{{ priceLabel }}</p>
      <p class="promotion-card__duration">{{ t(tariff.durationKey) }}</p>

      <ul class="promotion-card__features">
        <li
          v-for="featureKey in tariff.featureKeys"
          :key="featureKey"
          class="promotion-card__feature"
        >
          <img src="/figma/check-done.svg" alt="" class="promotion-card__check" width="15" height="15" />
          <span>{{ t(featureKey) }}</span>
        </li>
      </ul>

      <button
        type="button"
        class="promotion-card__cta"
        :class="{ 'promotion-card__cta--selected': selected }"
        :disabled="paying"
        @click="handleSelect"
      >
        {{ selected ? t('promotion.pay') : t('promotion.select') }}
      </button>
    </div>
  </article>
</template>

<style scoped>
.promotion-card {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 240px;
  min-width: 0;
  box-sizing: border-box;
}

.promotion-card--popular {
  z-index: 2;
  max-width: 285px;
  transform: scale(1.04);
  transform-origin: center center;
}

.promotion-card__badge {
  align-self: center;
  margin-bottom: -10px;
  z-index: 1;
  padding: 6px 14px;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  animation: promo-badge-in 0.35s ease-out;
}

.promotion-card__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 0;
  width: 100%;
  box-sizing: border-box;
  padding: 21px 15px 16px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.promotion-card--popular .promotion-card__body {
  padding: 33px 21px 22px;
  border-width: 2px;
  border-color: rgba(225, 69, 84, 0.45);
  box-shadow: 0 12px 30px rgba(225, 69, 84, 0.16);
  background: linear-gradient(180deg, var(--figma-surface) 0%, color-mix(in srgb, var(--figma-accent) 6%, var(--figma-mix-base)) 100%);
}

.promotion-card--selected .promotion-card__body {
  border-color: var(--figma-accent);
  box-shadow: 0 6px 18px rgba(225, 69, 84, 0.14);
}

.promotion-card__body:hover {
  transform: translateY(-2px);
}

.promotion-card--popular .promotion-card__body:hover {
  transform: translateY(-3px);
}

.promotion-card__icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  margin-bottom: 10px;
  border-radius: 9px;
  background: rgba(225, 69, 84, 0.08);
}

.promotion-card--popular .promotion-card__icon-wrap {
  width: 42px;
  height: 42px;
  margin-bottom: 14px;
  background: rgba(225, 69, 84, 0.14);
}

.promotion-card__icon {
  display: block;
  width: 21px;
  height: 21px;
}

.promotion-card--popular .promotion-card__icon {
  width: 24px;
  height: 24px;
}

.promotion-card__name {
  margin: 0 0 6px;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.promotion-card--popular .promotion-card__name {
  font-size: 18px;
}

.promotion-card__description {
  margin: 0 0 12px;
  max-width: 180px;
  font-size: 12px;
  line-height: 1.4;
  color: var(--figma-text-muted);
  overflow-wrap: anywhere;
}

.promotion-card--popular .promotion-card__description {
  margin-bottom: 15px;
  font-size: 13px;
}

.promotion-card__price {
  margin: 0 0 3px;
  font-size: 21px;
  font-weight: 700;
  line-height: 1.1;
  transition: opacity 0.2s ease;
  overflow-wrap: anywhere;
}

.promotion-card--popular .promotion-card__price {
  font-size: 27px;
}

.promotion-card__duration {
  margin: 0 0 14px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.promotion-card--popular .promotion-card__duration {
  margin-bottom: 16px;
  font-size: 13px;
}

.promotion-card__features {
  list-style: none;
  margin: 0 0 15px;
  padding: 0;
  width: 100%;
  max-width: 165px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  text-align: left;
}

.promotion-card--popular .promotion-card__features {
  max-width: 188px;
  gap: 8px;
  margin-bottom: 18px;
}

.promotion-card__feature {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 12px;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.promotion-card--popular .promotion-card__feature {
  font-size: 13px;
}

.promotion-card__check {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  margin-top: 1px;
}

.promotion-card__cta {
  width: 100%;
  max-width: 150px;
  min-height: 44px;
  margin-top: auto;
  border: none;
  border-radius: var(--figma-radius-btn);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease, opacity 0.2s ease;
}

.promotion-card--popular .promotion-card__cta {
  max-width: 180px;
  min-height: 44px;
  font-size: 14px;
}

.promotion-card__cta:hover:not(:disabled) {
  background: var(--figma-accent-hover);
}

.promotion-card__cta:active:not(:disabled) {
  transform: scale(0.98);
}

.promotion-card__cta:disabled {
  opacity: 0.65;
  cursor: default;
}

.promotion-card__cta--selected {
  background: #04832a;
}

.promotion-card__cta--selected:hover:not(:disabled) {
  background: #036b22;
}

.promotion-card--compact {
  max-width: none;
}

.promotion-card--compact.promotion-card--popular {
  transform: none;
}

.promotion-card--compact .promotion-card__body {
  padding: 15px 11px 12px;
}

.promotion-card--compact.promotion-card--popular .promotion-card__body {
  padding: 21px 14px 15px;
}

.promotion-card--compact .promotion-card__name {
  font-size: 14px;
}

.promotion-card--compact.promotion-card--popular .promotion-card__name {
  font-size: 15px;
}

.promotion-card--compact .promotion-card__description {
  display: none;
}

.promotion-card--compact .promotion-card__price {
  font-size: 17px;
}

.promotion-card--compact.promotion-card--popular .promotion-card__price {
  font-size: 20px;
}

.promotion-card--compact .promotion-card__features {
  display: none;
}

.promotion-card--compact .promotion-card__duration {
  margin-bottom: 10px;
}

.promotion-card--compact .promotion-card__cta {
  max-width: none;
  min-height: 44px;
  font-size: 12px;
}

.promotion-card--compact.promotion-card--popular .promotion-card__cta {
  min-height: 44px;
  font-size: 13px;
}

@keyframes promo-badge-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .promotion-card,
  .promotion-card--popular,
  .promotion-card--compact.promotion-card--popular {
    transform: none;
  }

  .promotion-card__body,
  .promotion-card__cta,
  .promotion-card__price,
  .promotion-card__badge {
    transition: none;
    animation: none;
  }

  .promotion-card__body:hover,
  .promotion-card--popular .promotion-card__body:hover {
    transform: none;
  }
}

@media (max-width: 1100px) {
  .promotion-card--popular {
    max-width: 240px;
    transform: none;
  }
}

@media (max-width: 767px) {
  .promotion-card,
  .promotion-card--popular {
    max-width: none;
    transform: none;
  }

  .promotion-card--compact.promotion-card--popular {
    transform: none;
  }

  .promotion-card__features,
  .promotion-card--popular .promotion-card__features {
    max-width: none;
  }

  .promotion-card__cta,
  .promotion-card--popular .promotion-card__cta {
    max-width: none;
  }
}

</style>
