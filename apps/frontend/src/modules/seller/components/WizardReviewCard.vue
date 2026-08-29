<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import MetroIcon from '@/components/MetroIcon.vue'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'
import { formatListingRoomsShort } from '@/lib/listingRooms'
import { normalizeMetroLineColor } from '@/lib/metroLineColor'
import { composeListingAddress, type ListingWizardDraft } from '@/modules/seller/lib/listingWizard'

const props = defineProps<{
  draft: ListingWizardDraft
}>()

const { t } = useI18n()

const safeImages = computed(() => props.draft.images.filter((url) => isSafeMediaUrl(url)))
const addressLine = computed(() => composeListingAddress(props.draft))
const locationLine = computed(() => {
  return [props.draft.city, props.draft.district].filter((part) => part.trim()).join(' · ')
})
const metroColor = computed(() => normalizeMetroLineColor(props.draft.metroLineColor))

const priceBynLabel = computed(() => {
  if (props.draft.priceByn == null || props.draft.priceByn <= 0) {
    return ''
  }
  return `${props.draft.priceByn} BYN`
})

const priceUsdLabel = computed(() => {
  if (props.draft.price == null || props.draft.price <= 0) {
    return ''
  }
  return `${props.draft.price} $`
})

const hasPrice = computed(() => Boolean(priceBynLabel.value || priceUsdLabel.value))

const flagItems = computed(() => {
  const flags: string[] = []
  if (props.draft.fromOwner === true) {
    flags.push(t('account.wizard.sellerRoles.owner'))
  } else if (props.draft.fromOwner === false) {
    flags.push(t('account.wizard.sellerRoles.agent'))
  }
  if (props.draft.noCommission) flags.push(t('account.wizard.flags.noCommission'))
  if (props.draft.hasRenovation) flags.push(t('account.wizard.flags.hasRenovation'))
  if (props.draft.priceNegotiable) flags.push(t('account.wizard.flags.priceNegotiable'))
  if (props.draft.hasDeposit) flags.push(t('account.wizard.flags.hasDeposit'))
  if (props.draft.utilitiesIncluded) flags.push(t('account.wizard.flags.utilitiesIncluded'))
  return flags
})

const paramItems = computed(() => {
  const items: Array<{ label: string, value: string }> = [
    {
      label: t('account.wizard.rooms'),
      value: props.draft.rooms != null ? formatListingRoomsShort(props.draft.rooms, t) : '-',
    },
    { label: t('account.wizard.area'), value: props.draft.area != null ? String(props.draft.area) : '-' },
    {
      label: t('account.wizard.floor'),
      value: props.draft.floor != null && props.draft.totalFloors != null
        ? `${props.draft.floor}/${props.draft.totalFloors}`
        : String(props.draft.floor ?? '-'),
    },
  ]
  if (props.draft.dealType === 'rent' && props.draft.rentTerm) {
    items.push({
      label: t('account.wizard.rentTerm'),
      value: t(`account.wizard.rentTerms.${props.draft.rentTerm}`),
    })
  }
  if (props.draft.entrance.trim()) {
    items.push({ label: t('account.wizard.entrance'), value: props.draft.entrance.trim() })
  }
  if (props.draft.apartmentNumber.trim()) {
    items.push({ label: t('account.wizard.apartmentNumber'), value: props.draft.apartmentNumber.trim() })
  }
  return items
})
</script>

<template>
  <article class="wizard-review">
    <div class="wizard-review__media">
      <template v-if="safeImages.length">
        <div class="wizard-review__hero">
          <img :src="safeImages[0]" alt="" />
          <span class="wizard-review__count">
            {{ t('account.wizard.reviewPhotosCount', { n: safeImages.length }) }}
          </span>
        </div>
        <div v-if="safeImages.length > 1" class="wizard-review__thumbs">
          <div
            v-for="(url, index) in safeImages.slice(1, 5)"
            :key="`${url}-${index}`"
            class="wizard-review__thumb"
          >
            <img :src="url" alt="" />
            <span
              v-if="index === 3 && safeImages.length > 5"
              class="wizard-review__more"
            >
              +{{ safeImages.length - 5 }}
            </span>
          </div>
        </div>
      </template>
      <div v-else class="wizard-review__media-empty">
        <span>{{ t('account.wizard.noPhotos') }}</span>
      </div>
    </div>

    <div class="wizard-review__body">
      <div class="wizard-review__price-block">
        <div v-if="hasPrice" class="wizard-review__price">
          <span v-if="priceBynLabel">{{ priceBynLabel }}</span>
          <span v-if="priceUsdLabel" class="wizard-review__price-alt">{{ priceUsdLabel }}</span>
        </div>
        <p v-else class="wizard-review__price-empty">{{ t('account.wizard.reviewNoPrice') }}</p>
      </div>

      <div class="wizard-review__chips" role="list">
        <span class="wizard-review__chip" role="listitem">{{ t(`nav.${draft.dealType}`) }}</span>
        <span class="wizard-review__chip" role="listitem">
          {{ t(`account.wizard.listingTypes.${draft.listingType}`) }}
        </span>
        <span
          v-if="draft.dealType === 'rent' && draft.rentTerm"
          class="wizard-review__chip"
          role="listitem"
        >
          {{ t(`account.wizard.rentTerms.${draft.rentTerm}`) }}
        </span>
      </div>

      <div class="wizard-review__location">
        <p class="wizard-review__address">{{ addressLine || t('account.wizard.reviewNoAddress') }}</p>
        <p v-if="locationLine" class="wizard-review__meta">{{ locationLine }}</p>
        <p v-if="draft.metro.trim()" class="wizard-review__metro">
          <MetroIcon :color="metroColor" :size="10" />
          <span>{{ draft.metro }}</span>
        </p>
        <p v-if="draft.region.trim()" class="wizard-review__meta">{{ draft.region }}</p>
      </div>

      <section class="wizard-review__section" aria-labelledby="wizard-review-params">
        <h3 id="wizard-review-params" class="wizard-review__section-title">
          {{ t('account.wizard.steps.details') }}
        </h3>
        <dl class="wizard-review__params">
          <div v-for="item in paramItems" :key="item.label" class="wizard-review__param">
            <dt>{{ item.label }}</dt>
            <dd>{{ item.value }}</dd>
          </div>
        </dl>
      </section>

      <section v-if="flagItems.length" class="wizard-review__section" aria-labelledby="wizard-review-flags">
        <h3 id="wizard-review-flags" class="wizard-review__section-title">
          {{ t('account.wizard.reviewExtras') }}
        </h3>
        <div class="wizard-review__flags">
          <span v-for="flag in flagItems" :key="flag" class="wizard-review__flag">{{ flag }}</span>
        </div>
      </section>
    </div>
  </article>
</template>

<style scoped>
.wizard-review {
  display: flex;
  flex-direction: column;
  gap: 0;
  width: 100%;
  overflow: hidden;
  border: 1px solid var(--figma-border);
  border-radius: 16px;
  background: var(--color-bg-elevated);
  box-shadow: 0 8px 24px color-mix(in srgb, #000 4%, transparent);
}

.wizard-review__media {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 10px;
  background: color-mix(in srgb, var(--figma-accent) 4%, #f7f7f8);
}

.wizard-review__hero {
  position: relative;
  overflow: hidden;
  border-radius: 12px;
  aspect-ratio: 16 / 10;
  background: var(--color-bg-muted, #eee);
}

.wizard-review__hero img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.wizard-review__count {
  position: absolute;
  right: 10px;
  bottom: 10px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.62);
  color: var(--figma-on-accent);
  font-size: 12px;
  font-weight: 700;
}

.wizard-review__thumbs {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 6px;
}

.wizard-review__thumb {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
  aspect-ratio: 1;
  background: var(--color-bg-muted, #eee);
}

.wizard-review__thumb img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.wizard-review__more {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.55);
  color: var(--figma-on-accent);
  font-size: 16px;
  font-weight: 700;
}

.wizard-review__media-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 160px;
  border: 1px dashed var(--figma-border);
  border-radius: 12px;
  color: var(--figma-text-muted, #929292);
  font-size: 14px;
  font-weight: 600;
  background: var(--figma-surface);
}

.wizard-review__body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 16px 16px 18px;
}

.wizard-review__price {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
}

.wizard-review__price-alt {
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-text-muted, #929292);
}

.wizard-review__price-empty {
  margin: 0;
  color: var(--figma-text-muted, #929292);
  font-size: 15px;
  font-weight: 600;
}

.wizard-review__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.wizard-review__chip {
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

.wizard-review__location {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.wizard-review__address {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
}

.wizard-review__meta {
  margin: 0;
  color: var(--figma-text-muted, #929292);
  font-size: 13px;
  font-weight: 500;
}

.wizard-review__section {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 4px;
  border-top: 1px solid var(--figma-border);
}

.wizard-review__section-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--figma-text-muted, #929292);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.wizard-review__params {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin: 0;
}

.wizard-review__param {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  padding: 0;
  border-radius: 0;
  background: transparent;
}

.wizard-review__param dt {
  color: var(--figma-text-muted, #929292);
  font-size: 11px;
  font-weight: 600;
}

.wizard-review__param dd {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text);
  overflow-wrap: anywhere;
}

.wizard-review__flags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.wizard-review__flag {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 999px;
  background: var(--figma-surface);
  color: var(--color-text);
  font-size: 12px;
  font-weight: 600;
}

@media (max-width: 767px) {
  .wizard-review__params {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .wizard-review__price {
    font-size: 20px;
  }
}
</style>
