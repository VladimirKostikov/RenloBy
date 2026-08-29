<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import { formatBelarusPhone } from '@/lib/formatBelarusPhone'
import {
  instagramProfileHref,
  telegramProfileHref,
  viberProfileHref,
  whatsappProfileHref,
} from '@/lib/sellerSocialLinks'
import type { ListingSellerDto } from '@/types'

const props = withDefaults(
  defineProps<{
    seller: ListingSellerDto
    fromOwner?: boolean
    interactive?: boolean
  }>(),
  {
    fromOwner: false,
    interactive: true,
  },
)

const emit = defineEmits<{
  open: []
  contact: []
}>()

const { t } = useI18n()

const initials = computed(() => {
  const parts = props.seller.name.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) {
    return '?'
  }
  if (parts.length === 1) {
    return parts[0].slice(0, 1).toUpperCase()
  }
  return `${parts[0].slice(0, 1)}${parts[1].slice(0, 1)}`.toUpperCase()
})

const phoneHref = computed(() => {
  if (!props.seller.phone) {
    return null
  }
  const digits = props.seller.phone.replace(/[^\d+]/g, '')
  return digits ? `tel:${digits}` : null
})

const phoneLabel = computed(() => formatBelarusPhone(props.seller.phone))

function openProfile() {
  if (!props.interactive) {
    return
  }
  emit('open')
}

function emitContact() {
  emit('contact')
}

function onKeydown(event: KeyboardEvent) {
  if (!props.interactive) {
    return
  }
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    openProfile()
  }
}

const telegramHref = computed(() => telegramProfileHref(props.seller.telegram))
const instagramHref = computed(() => instagramProfileHref(props.seller.instagram))
const whatsappHref = computed(() => whatsappProfileHref(props.seller.whatsapp))
const viberHref = computed(() => viberProfileHref(props.seller.viber))

const hasMessengers = computed(
  () => Boolean(telegramHref.value || instagramHref.value || whatsappHref.value || viberHref.value),
)
</script>

<template>
  <section
    class="listing-seller"
    :class="{ 'listing-seller--static': !interactive }"
    :aria-label="t('listingDetail.sellerTitle')"
    :role="interactive ? 'button' : 'region'"
    :tabindex="interactive ? 0 : undefined"
    @click="openProfile"
    @keydown="onKeydown"
  >
    <div class="listing-seller__head">
      <div class="listing-seller__avatar" aria-hidden="true">
        <img v-if="seller.photo" :src="seller.photo" :alt="seller.name" class="listing-seller__photo" />
        <span v-else>{{ initials }}</span>
      </div>
      <div class="listing-seller__meta">
        <h3 class="listing-seller__name">{{ seller.name }}</h3>
        <p
          class="listing-seller__role"
          :class="fromOwner ? 'listing-seller__role--owner' : 'listing-seller__role--agent'"
        >
          {{ fromOwner ? t('listingDetail.sellerOwner') : t('listingDetail.sellerAgent') }}
        </p>
      </div>
    </div>

    <a
      v-if="phoneHref"
      class="listing-seller__phone"
      :href="phoneHref"
      @click.stop="emitContact"
    >
      {{ phoneLabel }}
    </a>

    <div v-if="hasMessengers" class="listing-seller__messengers">
      <a
        v-if="instagramHref"
        class="listing-seller__messenger listing-seller__messenger--instagram"
        :href="instagramHref"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="t('topBar.instagram')"
        :title="t('topBar.instagram')"
        :style="{ '--messenger-color': '#E4405F' }"
        @click.stop="emitContact"
      >
        <SocialBrandIcon name="instagram" :size="18" />
      </a>
      <a
        v-if="telegramHref"
        class="listing-seller__messenger listing-seller__messenger--telegram"
        :href="telegramHref"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="t('topBar.telegram')"
        :title="t('topBar.telegram')"
        :style="{ '--messenger-color': '#229ED9' }"
        @click.stop="emitContact"
      >
        <SocialBrandIcon name="telegram" :size="18" />
      </a>
      <a
        v-if="whatsappHref"
        class="listing-seller__messenger listing-seller__messenger--whatsapp"
        :href="whatsappHref"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="t('topBar.whatsapp')"
        :title="t('topBar.whatsapp')"
        :style="{ '--messenger-color': '#25D366' }"
        @click.stop="emitContact"
      >
        <SocialBrandIcon name="whatsapp" :size="18" />
      </a>
      <a
        v-if="viberHref"
        class="listing-seller__messenger listing-seller__messenger--viber"
        :href="viberHref"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="'Viber'"
        title="Viber"
        :style="{ '--messenger-color': '#7360F2' }"
        @click.stop="emitContact"
      >
        <SocialBrandIcon name="viber" :size="18" />
      </a>
    </div>
  </section>
</template>

<style scoped>
.listing-seller {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid rgba(146, 146, 146, 0.19);
  border-radius: 14px;
  background: var(--figma-surface);
  cursor: pointer;
  transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.listing-seller:hover,
.listing-seller:focus-visible {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(17, 24, 39, 0.06);
}

.listing-seller:focus-visible {
  outline: 2px solid var(--figma-accent);
  outline-offset: 2px;
}

.listing-seller--static {
  margin-top: 0;
  cursor: default;
}

.listing-seller--static:hover,
.listing-seller--static:focus-visible {
  transform: none;
  box-shadow: none;
  outline: none;
}

.listing-seller__head {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.listing-seller__avatar {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(225, 69, 84, 0.1);
  color: var(--figma-accent);
  font-size: 15px;
  font-weight: 700;
  overflow: hidden;
  flex-shrink: 0;
}

.listing-seller__photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.listing-seller__meta {
  min-width: 0;
}

.listing-seller__name {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--figma-ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listing-seller__role {
  margin: 2px 0 0;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-seller__phone {
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  text-decoration: none;
}

.listing-seller__phone:hover {
  color: var(--figma-accent);
}

.listing-seller__messengers {
  display: flex;
  align-items: center;
  gap: 8px;
}

.listing-seller__messenger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--messenger-color);
  color: var(--figma-on-accent);
  text-decoration: none;
  transition: transform 0.16s ease;
}

.listing-seller__messenger:hover {
  transform: translateY(-1px);
}

@media (prefers-reduced-motion: reduce) {
  .listing-seller {
    transition: none;
  }

  .listing-seller__messenger {
    transition: none;
  }
}

@media (max-width: 767px) {
  .listing-seller__phone {
    min-height: 44px;
    padding: 0 14px;
    font-size: 15px;
  }

  .listing-seller__messenger {
    width: 44px;
    height: 44px;
  }
}
</style>
