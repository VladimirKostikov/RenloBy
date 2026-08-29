<script setup lang="ts">
defineOptions({ name: 'ListingShareModal' })

import { useI18n } from 'vue-i18n'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import { LISTING_NESTED_MODAL_Z_INDEX } from '@/lib/listingModalZIndex'

const props = withDefaults(
  defineProps<{
    open?: boolean
    url: string
    title: string
  }>(),
  {
    open: true,
  },
)

const emit = defineEmits<{
  close: []
}>()

const { t } = useI18n()

const networks = [
  {
    key: 'telegram' as const,
    label: 'Telegram',
    color: '#229ED9',
    href: () =>
      `https://t.me/share/url?url=${encodeURIComponent(props.url)}&text=${encodeURIComponent(props.title)}`,
  },
  {
    key: 'whatsapp' as const,
    label: 'WhatsApp',
    color: '#25D366',
    href: () => `https://wa.me/?text=${encodeURIComponent(`${props.title} ${props.url}`)}`,
  },
  {
    key: 'viber' as const,
    label: 'Viber',
    color: '#7360F2',
    href: () => `viber://forward?text=${encodeURIComponent(`${props.title} ${props.url}`)}`,
  },
  {
    key: 'vk' as const,
    label: 'VK',
    color: '#0077FF',
    href: () =>
      `https://vk.com/share.php?url=${encodeURIComponent(props.url)}&title=${encodeURIComponent(props.title)}`,
  },
  {
    key: 'ok' as const,
    label: 'OK',
    color: '#EE8208',
    href: () =>
      `https://connect.ok.ru/offer?url=${encodeURIComponent(props.url)}&title=${encodeURIComponent(props.title)}`,
  },
]

async function copyLink() {
  try {
    await navigator.clipboard.writeText(props.url)
  } catch {
    window.prompt(t('listingDetail.copyLink'), props.url)
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="listing-share-modal">
      <div
        v-if="open"
        class="listing-share-modal"
        role="dialog"
        aria-modal="true"
        :style="{ zIndex: LISTING_NESTED_MODAL_Z_INDEX }"
        @click.self="emit('close')"
      >
        <div class="listing-share-modal__card">
          <div class="listing-share-modal__head">
            <h2>{{ t('listingDetail.shareTitle') }}</h2>
            <button
              type="button"
              class="listing-share-modal__close"
              :aria-label="t('listingDetail.close')"
              @click="emit('close')"
            >
              ×
            </button>
          </div>

          <div class="listing-share-modal__list" role="list">
            <a
              v-for="network in networks"
              :key="network.key"
              class="listing-share-modal__item"
              role="listitem"
              :href="network.href()"
              target="_blank"
              rel="noopener noreferrer"
              :aria-label="network.label"
              :title="network.label"
              :style="{ '--network-color': network.color }"
            >
              <SocialBrandIcon :name="network.key" :size="28" />
            </a>
          </div>

          <button type="button" class="listing-share-modal__copy" @click="copyLink">
            {{ t('listingDetail.copyLink') }}
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.listing-share-modal {
  position: fixed;
  inset: 0;
  z-index: 2400;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.45);
}

.listing-share-modal__card {
  width: min(420px, 100%);
  padding: 20px;
  border-radius: 16px;
  background: var(--figma-surface);
}

.listing-share-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.listing-share-modal__head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.listing-share-modal__close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: rgba(146, 146, 146, 0.12);
  font-size: 20px;
  cursor: pointer;
}

.listing-share-modal__list {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.listing-share-modal__item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--network-color);
  color: var(--figma-on-accent);
  text-decoration: none;
  transition: transform 0.16s ease;
}

.listing-share-modal__item:hover {
  transform: translateY(-2px);
}

.listing-share-modal__copy {
  width: 100%;
  margin-top: 18px;
  height: 44px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: var(--figma-surface);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

@media (max-width: 767px) {
  .listing-share-modal {
    padding: 0;
    align-items: end;
    place-items: end stretch;
  }

  .listing-share-modal__card {
    width: 100%;
    border-radius: 20px 20px 0 0;
    padding: 18px 16px calc(18px + env(safe-area-inset-bottom, 0px));
  }

  .listing-share-modal__item {
    min-width: 72px;
    min-height: 72px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .listing-share-modal__item {
    transition: none;
  }

  .listing-share-modal__item:hover {
    transform: none;
  }
}
</style>

<style>
.listing-share-modal-enter-active,
.listing-share-modal-leave-active {
  transition: opacity 0.22s ease;
}

.listing-share-modal-enter-active .listing-share-modal__card,
.listing-share-modal-leave-active .listing-share-modal__card {
  transition:
    opacity 0.24s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-share-modal-enter-from,
.listing-share-modal-leave-to {
  opacity: 0;
}

.listing-share-modal-enter-from .listing-share-modal__card,
.listing-share-modal-leave-to .listing-share-modal__card {
  opacity: 0;
  transform: translateY(14px) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .listing-share-modal-enter-active,
  .listing-share-modal-leave-active,
  .listing-share-modal-enter-active .listing-share-modal__card,
  .listing-share-modal-leave-active .listing-share-modal__card {
    transition-duration: 0.01ms;
  }

  .listing-share-modal-enter-from .listing-share-modal__card,
  .listing-share-modal-leave-to .listing-share-modal__card {
    transform: none;
  }
}
</style>
