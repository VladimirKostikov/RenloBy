<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import { LISTING_NESTED_MODAL_Z_INDEX } from '@/lib/listingModalZIndex'

const props = withDefaults(
  defineProps<{
    phone?: string
    text: string
    open?: boolean
  }>(),
  {
    open: true,
  },
)

const emit = defineEmits<{
  close: []
}>()

const { t } = useI18n()
const phone = (props.phone ?? '').replace(/[^\d+]/g, '')
const encoded = encodeURIComponent(props.text)

const messengers = [
  {
    key: 'whatsapp' as const,
    label: 'WhatsApp',
    color: '#25D366',
    href: phone
      ? `https://wa.me/${phone.replace(/\D/g, '')}?text=${encoded}`
      : `https://wa.me/?text=${encoded}`,
  },
  {
    key: 'telegram' as const,
    label: 'Telegram',
    color: '#229ED9',
    href: `https://t.me/share/url?url=${encoded}&text=${encoded}`,
  },
  {
    key: 'viber' as const,
    label: 'Viber',
    color: '#7360F2',
    href: phone
      ? `viber://chat?number=${encodeURIComponent(phone)}`
      : `viber://forward?text=${encoded}`,
  },
]
</script>

<template>
  <Teleport to="body">
    <Transition name="listing-chat-modal">
      <div
        v-if="open"
        class="listing-chat-modal"
        role="dialog"
        aria-modal="true"
        :style="{ zIndex: LISTING_NESTED_MODAL_Z_INDEX }"
        @click.self="emit('close')"
      >
        <div class="listing-chat-modal__card">
          <div class="listing-chat-modal__head">
            <h2>{{ t('listingDetail.contactSellerTitle') }}</h2>
            <button type="button" class="listing-chat-modal__close" :aria-label="t('listingDetail.close')" @click="emit('close')">
              ×
            </button>
          </div>
          <p class="listing-chat-modal__hint">{{ t('listingDetail.contactSellerHint') }}</p>
          <div class="listing-chat-modal__list" role="list">
            <a
              v-for="item in messengers"
              :key="item.key"
              class="listing-chat-modal__item"
              role="listitem"
              :href="item.href"
              target="_blank"
              rel="noopener noreferrer"
              :aria-label="item.label"
              :title="item.label"
              :style="{ '--messenger-color': item.color }"
            >
              <SocialBrandIcon :name="item.key" :size="28" />
            </a>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.listing-chat-modal {
  position: fixed;
  inset: 0;
  z-index: 2400;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.45);
}

.listing-chat-modal__card {
  width: min(400px, 100%);
  padding: 20px;
  border-radius: 16px;
  background: var(--figma-surface);
}

.listing-chat-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.listing-chat-modal__head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.listing-chat-modal__close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: rgba(146, 146, 146, 0.12);
  font-size: 20px;
  cursor: pointer;
}

.listing-chat-modal__hint {
  margin: 0 0 18px;
  font-size: 13px;
  color: var(--figma-text-muted);
  text-align: center;
}

.listing-chat-modal__list {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.listing-chat-modal__item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--messenger-color);
  color: var(--figma-on-accent);
  text-decoration: none;
  transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.listing-chat-modal__item:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 18px color-mix(in srgb, var(--messenger-color) 35%, transparent);
}

@media (prefers-reduced-motion: reduce) {
  .listing-chat-modal__item {
    transition: none;
  }

  .listing-chat-modal__item:hover {
    transform: none;
  }
}

@media (max-width: 767px) {
  .listing-chat-modal {
    padding: 0;
    align-items: flex-end;
  }

  .listing-chat-modal__card {
    width: 100%;
    max-width: none;
    border-radius: 20px 20px 0 0;
    padding: 20px 16px 24px;
  }

  .listing-chat-modal__item {
    width: 60px;
    height: 60px;
  }
}
</style>

<style>
.listing-chat-modal-enter-active,
.listing-chat-modal-leave-active {
  transition: opacity 0.22s ease;
}

.listing-chat-modal-enter-active .listing-chat-modal__card,
.listing-chat-modal-leave-active .listing-chat-modal__card {
  transition:
    opacity 0.24s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-chat-modal-enter-from,
.listing-chat-modal-leave-to {
  opacity: 0;
}

.listing-chat-modal-enter-from .listing-chat-modal__card,
.listing-chat-modal-leave-to .listing-chat-modal__card {
  opacity: 0;
  transform: translateY(14px) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .listing-chat-modal-enter-active,
  .listing-chat-modal-leave-active,
  .listing-chat-modal-enter-active .listing-chat-modal__card,
  .listing-chat-modal-leave-active .listing-chat-modal__card {
    transition-duration: 0.01ms;
  }

  .listing-chat-modal-enter-from .listing-chat-modal__card,
  .listing-chat-modal-leave-to .listing-chat-modal__card {
    transform: none;
  }
}
</style>
