<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import CatalogGridCard from '@/components/CatalogGridCard.vue'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import { fetchSellerListings, fetchSellerProfile, type SellerProfileDto } from '@/api/sellers'
import { formatBelarusPhone } from '@/lib/formatBelarusPhone'
import { formatRegisteredAt, resolveLastSeen } from '@/lib/resolveLastSeen'
import { LISTING_NESTED_MODAL_Z_INDEX } from '@/lib/listingModalZIndex'
import {
  instagramProfileHref,
  telegramProfileHref,
  viberProfileHref,
  whatsappProfileHref,
} from '@/lib/sellerSocialLinks'
import { useComparisonsStore } from '@/stores/comparisons'
import { useFavoritesStore } from '@/stores/favorites'
import { useListingsStore } from '@/stores/listings'
import type { ListingDto, ListingSellerDto, MetroStationDto } from '@/types'

const props = withDefaults(
  defineProps<{
    open?: boolean
    sellerId: number
    fromOwner?: boolean
    initialSeller?: ListingSellerDto | null
  }>(),
  {
    open: true,
    fromOwner: false,
    initialSeller: null,
  },
)

const emit = defineEmits<{
  close: []
  openListing: [id: number]
}>()

const { t, locale } = useI18n()
const listingsStore = useListingsStore()
const favorites = useFavoritesStore()
const comparisons = useComparisonsStore()

const profile = ref<SellerProfileDto | null>(null)
const listings = ref<ListingDto[]>([])
const loading = ref(false)
const error = ref(false)

const displaySeller = computed(() => profile.value ?? props.initialSeller)

const initials = computed(() => {
  const name = displaySeller.value?.name?.trim() ?? ''
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 0) {
    return '?'
  }
  if (parts.length === 1) {
    return parts[0].slice(0, 1).toUpperCase()
  }
  return `${parts[0].slice(0, 1)}${parts[1].slice(0, 1)}`.toUpperCase()
})

const roleLabel = computed(() =>
  props.fromOwner ? t('listingDetail.sellerOwner') : t('listingDetail.sellerAgent'),
)

const lastSeenInfo = computed(() => resolveLastSeen(profile.value?.lastSeenAt))

const lastSeenLabel = computed(() => {
  const info = lastSeenInfo.value
  if (info.kind === 'online') {
    return t('listingDetail.sellerLastSeenOnline')
  }
  if (info.kind === 'minutes' && info.value) {
    return t('listingDetail.sellerLastSeenMinutes', { n: info.value })
  }
  if (info.kind === 'hours' && info.value) {
    return t('listingDetail.sellerLastSeenHours', { n: info.value })
  }
  if (info.kind === 'days' && info.value) {
    return t('listingDetail.sellerLastSeenDays', { n: info.value })
  }
  return t('listingDetail.sellerLastSeenUnknown')
})

const registeredAtLabel = computed(() => {
  if (lastSeenInfo.value.kind !== 'unknown') {
    return null
  }
  const formatted = formatRegisteredAt(profile.value?.registeredAt, locale.value === 'en' ? 'en' : 'ru')
  if (!formatted) {
    return null
  }
  return t('listingDetail.sellerRegisteredAt', { date: formatted })
})

const phoneHref = computed(() => {
  const phone = displaySeller.value?.phone
  if (!phone) {
    return null
  }
  const digits = phone.replace(/[^\d+]/g, '')
  return digits ? `tel:${digits}` : null
})

const phoneLabel = computed(() => formatBelarusPhone(displaySeller.value?.phone))

const telegramHref = computed(() => telegramProfileHref(displaySeller.value?.telegram))
const instagramHref = computed(() => instagramProfileHref(displaySeller.value?.instagram))
const whatsappHref = computed(() => whatsappProfileHref(displaySeller.value?.whatsapp))
const viberHref = computed(() => viberProfileHref(displaySeller.value?.viber))

const hasMessengers = computed(
  () => Boolean(telegramHref.value || instagramHref.value || whatsappHref.value || viberHref.value),
)

watch(
  () => [props.open, props.sellerId] as const,
  ([open]) => {
    if (!open) {
      return
    }
    void load()
  },
  { immediate: true },
)

async function load() {
  loading.value = true
  error.value = false
  try {
    const [seller, page] = await Promise.all([
      fetchSellerProfile(props.sellerId),
      fetchSellerListings(props.sellerId, { page: 1, limit: 12 }),
    ])
    profile.value = seller
    listings.value = page.items
  } catch {
    error.value = true
    profile.value = null
    listings.value = []
  } finally {
    loading.value = false
  }
}

function metroFor(listing: ListingDto): MetroStationDto | undefined {
  if (!listing.metroStationId) {
    return undefined
  }
  return listingsStore.metroStations.find((station) => station.id === listing.metroStationId)
}

function districtFor(listing: ListingDto): string | undefined {
  const district = listingsStore.districts.find((item) => item.id === listing.districtId)
  const city = listingsStore.cities.find((item) => item.id === listing.cityId)
  if (!district || !city) {
    return listing.districtName ?? undefined
  }
  return `${district.name}, ${city.name}`
}

function openListing(id: number) {
  emit('openListing', id)
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="listing-seller-modal">
      <div
        v-if="open"
        class="listing-seller-modal"
        role="dialog"
        aria-modal="true"
        :style="{ zIndex: LISTING_NESTED_MODAL_Z_INDEX }"
        @click.self="emit('close')"
      >
        <div class="listing-seller-modal__card">
          <div class="listing-seller-modal__head">
            <h2>{{ t('listingDetail.sellerProfileTitle') }}</h2>
            <button
              type="button"
              class="listing-seller-modal__close"
              :aria-label="t('listingDetail.close')"
              @click="emit('close')"
            >
              ×
            </button>
          </div>

          <div v-if="loading && !displaySeller" class="listing-seller-modal__state">
            {{ t('listing.loading') }}
          </div>
          <div v-else-if="error && !displaySeller" class="listing-seller-modal__state">
            {{ t('listingDetail.sellerProfileError') }}
          </div>

          <template v-else-if="displaySeller">
            <div class="listing-seller-modal__profile">
              <div class="listing-seller-modal__avatar" aria-hidden="true">
                <img
                  v-if="displaySeller.photo"
                  :src="displaySeller.photo"
                  :alt="displaySeller.name"
                  class="listing-seller-modal__photo"
                />
                <span v-else>{{ initials }}</span>
              </div>
              <div class="listing-seller-modal__meta">
                <h3 class="listing-seller-modal__name">{{ displaySeller.name }}</h3>
                <span
                  class="listing-seller-modal__role"
                  :class="fromOwner ? 'listing-seller-modal__role--owner' : 'listing-seller-modal__role--agent'"
                >
                  {{ roleLabel }}
                </span>
                <p class="listing-seller-modal__seen">{{ lastSeenLabel }}</p>
                <p v-if="registeredAtLabel" class="listing-seller-modal__registered">{{ registeredAtLabel }}</p>
                <p v-if="profile" class="listing-seller-modal__count">
                  {{ t('listingDetail.sellerListingsCount', { n: profile.listingsCount }) }}
                </p>
              </div>
            </div>

            <div v-if="phoneHref || hasMessengers" class="listing-seller-modal__contacts">
              <a
                v-if="phoneHref"
                class="listing-seller-modal__phone"
                :href="phoneHref"
              >
                {{ phoneLabel }}
              </a>
              <div v-if="hasMessengers" class="listing-seller-modal__messengers">
                <a
                  v-if="instagramHref"
                  class="listing-seller-modal__messenger listing-seller-modal__messenger--instagram"
                  :href="instagramHref"
                  target="_blank"
                  rel="noopener noreferrer"
                  :aria-label="t('topBar.instagram')"
                >
                  <SocialBrandIcon name="instagram" :size="16" />
                </a>
                <a
                  v-if="telegramHref"
                  class="listing-seller-modal__messenger listing-seller-modal__messenger--telegram"
                  :href="telegramHref"
                  target="_blank"
                  rel="noopener noreferrer"
                  :aria-label="t('topBar.telegram')"
                >
                  <SocialBrandIcon name="telegram" :size="16" />
                </a>
                <a
                  v-if="whatsappHref"
                  class="listing-seller-modal__messenger listing-seller-modal__messenger--whatsapp"
                  :href="whatsappHref"
                  target="_blank"
                  rel="noopener noreferrer"
                  :aria-label="t('topBar.whatsapp')"
                >
                  <SocialBrandIcon name="whatsapp" :size="16" />
                </a>
                <a
                  v-if="viberHref"
                  class="listing-seller-modal__messenger listing-seller-modal__messenger--viber"
                  :href="viberHref"
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Viber"
                >
                  <SocialBrandIcon name="viber" :size="16" />
                </a>
              </div>
            </div>

            <section class="listing-seller-modal__listings">
              <h3 class="listing-seller-modal__section-title">
                {{ t('listingDetail.sellerListingsTitle') }}
              </h3>
              <div v-if="loading" class="listing-seller-modal__state listing-seller-modal__state--soft">
                {{ t('listing.loading') }}
              </div>
              <div v-else-if="listings.length === 0" class="listing-seller-modal__state listing-seller-modal__state--soft">
                {{ t('listingDetail.sellerListingsEmpty') }}
              </div>
              <div v-else class="listing-seller-modal__grid">
                <CatalogGridCard
                  v-for="item in listings"
                  :key="item.id"
                  :listing="item"
                  :metro-station="metroFor(item)"
                  :district-name="districtFor(item)"
                  :favorited="favorites.isFavorite(item.id)"
                  :compared="comparisons.isCompared(item.id)"
                  compact
                  @open="openListing"
                  @favorite="favorites.toggle($event, listings.find((row) => row.id === $event))"
                  @compare="comparisons.toggle($event, listings.find((row) => row.id === $event))"
                />
              </div>
            </section>
          </template>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.listing-seller-modal {
  position: fixed;
  inset: 0;
  z-index: 2500;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.45);
}

.listing-seller-modal__card {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: min(560px, 100%);
  max-height: min(88vh, 760px);
  overflow: auto;
  padding: 18px;
  border-radius: 18px;
  background: var(--figma-surface);
}

.listing-seller-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.listing-seller-modal__head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.listing-seller-modal__close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: rgba(146, 146, 146, 0.12);
  font-size: 20px;
  cursor: pointer;
}

.listing-seller-modal__profile {
  display: flex;
  gap: 12px;
  align-items: center;
  min-width: 0;
}

.listing-seller-modal__avatar {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--figma-accent) 10%, #f6f7f8);
  color: var(--figma-accent);
  font-size: 18px;
  font-weight: 700;
  overflow: hidden;
  flex-shrink: 0;
}

.listing-seller-modal__photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.listing-seller-modal__meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.listing-seller-modal__name {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listing-seller-modal__role {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}

.listing-seller-modal__role--owner {
  color: #0f7a3a;
  background: color-mix(in srgb, #0f7a3a 12%, var(--figma-mix-base));
}

.listing-seller-modal__role--agent {
  color: #5b6472;
  background: color-mix(in srgb, #5b6472 12%, var(--figma-mix-base));
}

.listing-seller-modal__seen,
.listing-seller-modal__count {
  margin: 0;
  font-size: 13px;
  color: var(--figma-text-muted);
}

.listing-seller-modal__contacts {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.listing-seller-modal__phone {
  font-size: 15px;
  font-weight: 700;
  color: var(--figma-accent);
  text-decoration: none;
}

.listing-seller-modal__messengers {
  display: flex;
  gap: 6px;
}

.listing-seller-modal__messenger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  color: var(--figma-on-accent);
}

.listing-seller-modal__messenger--instagram {
  background: #e4405f;
}

.listing-seller-modal__messenger--whatsapp {
  background: #25d366;
}

.listing-seller-modal__messenger--telegram {
  background: #229ed9;
}

.listing-seller-modal__messenger--viber {
  background: #7360f2;
}

.listing-seller-modal__section-title {
  margin: 0 0 10px;
  font-size: 15px;
  font-weight: 700;
}

.listing-seller-modal__state {
  padding: 18px 12px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  text-align: center;
  color: var(--figma-text-muted);
  font-size: 14px;
}

.listing-seller-modal__state--soft {
  background: #f8f8f9;
}

.listing-seller-modal__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.listing-seller-modal__grid > :deep(.catalog-card) {
  min-width: 0;
}

@media (max-width: 767px) {
  .listing-seller-modal__grid {
    grid-template-columns: 1fr;
  }

  .listing-seller-modal__card {
    padding: 14px;
    border-radius: 16px;
  }
}
</style>

<style>
.listing-seller-modal-enter-active,
.listing-seller-modal-leave-active {
  transition: opacity 0.22s ease;
}

.listing-seller-modal-enter-active .listing-seller-modal__card,
.listing-seller-modal-leave-active .listing-seller-modal__card {
  transition:
    opacity 0.24s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-seller-modal-enter-from,
.listing-seller-modal-leave-to {
  opacity: 0;
}

.listing-seller-modal-enter-from .listing-seller-modal__card,
.listing-seller-modal-leave-to .listing-seller-modal__card {
  opacity: 0;
  transform: translateY(16px) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .listing-seller-modal-enter-active,
  .listing-seller-modal-leave-active,
  .listing-seller-modal-enter-active .listing-seller-modal__card,
  .listing-seller-modal-leave-active .listing-seller-modal__card {
    transition-duration: 0.01ms;
  }

  .listing-seller-modal-enter-from .listing-seller-modal__card,
  .listing-seller-modal-leave-to .listing-seller-modal__card {
    transform: none;
  }
}
</style>
