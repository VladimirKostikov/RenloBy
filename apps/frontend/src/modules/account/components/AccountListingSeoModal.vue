<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { updateMyListing } from '@/api/account'
import { resolveApiError } from '@/lib/resolveApiError'
import type { ListingDto } from '@/types'

const props = defineProps<{
  listing: ListingDto
}>()

const emit = defineEmits<{
  close: []
  saved: [listing: ListingDto]
}>()

const { t } = useI18n()

const metaTitle = ref('')
const metaDescription = ref('')
const metaKeywords = ref('')
const saving = ref(false)
const error = ref(false)
const errorMessage = ref('')

watch(
  () => props.listing,
  (listing) => {
    metaTitle.value = listing.metaTitle ?? ''
    metaDescription.value = listing.metaDescription ?? ''
    metaKeywords.value = listing.metaKeywords ?? ''
    error.value = false
    errorMessage.value = ''
  },
  { immediate: true },
)

const titleCount = computed(() => metaTitle.value.trim().length)
const descriptionCount = computed(() => metaDescription.value.trim().length)

async function save() {
  saving.value = true
  error.value = false
  errorMessage.value = ''
  try {
    const updated = await updateMyListing(props.listing.id, {
      metaTitle: metaTitle.value.trim() || null,
      metaDescription: metaDescription.value.trim() || null,
      metaKeywords: metaKeywords.value.trim() || null,
    })
    emit('saved', updated)
    emit('close')
  } catch (err) {
    error.value = true
    errorMessage.value = resolveApiError(err, t, 'account.listings.seo.saveError').message
  } finally {
    saving.value = false
  }
}

function onOverlayClick(event: MouseEvent) {
  if (event.target === event.currentTarget && !saving.value) {
    emit('close')
  }
}
</script>

<template>
  <div
    class="listing-seo-modal"
    data-testid="listing-seo-modal"
    role="presentation"
    @click="onOverlayClick"
  >
    <div
      class="listing-seo-modal__dialog"
      role="dialog"
      aria-modal="true"
      :aria-label="t('account.listings.seo.title')"
      @click.stop
    >
      <div class="listing-seo-modal__head">
        <div>
          <h2 class="listing-seo-modal__title">{{ t('account.listings.seo.title') }}</h2>
          <p class="listing-seo-modal__subtitle">{{ listing.address }}</p>
        </div>
        <button
          type="button"
          class="listing-seo-modal__close"
          :aria-label="t('account.listings.seo.close')"
          :disabled="saving"
          @click="emit('close')"
        >
          ×
        </button>
      </div>

      <p class="listing-seo-modal__hint">{{ t('account.listings.seo.hint') }}</p>

      <form class="listing-seo-modal__form" @submit.prevent="save">
        <label class="listing-seo-modal__field">
          <span class="listing-seo-modal__label">
            {{ t('account.listings.seo.metaTitle') }}
            <span class="listing-seo-modal__counter">{{ titleCount }}/60</span>
          </span>
          <input
            v-model="metaTitle"
            type="text"
            class="listing-seo-modal__input"
            maxlength="255"
            :placeholder="t('account.listings.seo.metaTitlePlaceholder')"
            data-testid="listing-seo-title"
          />
        </label>

        <label class="listing-seo-modal__field">
          <span class="listing-seo-modal__label">
            {{ t('account.listings.seo.metaDescription') }}
            <span class="listing-seo-modal__counter">{{ descriptionCount }}/160</span>
          </span>
          <textarea
            v-model="metaDescription"
            class="listing-seo-modal__textarea"
            rows="4"
            maxlength="2000"
            :placeholder="t('account.listings.seo.metaDescriptionPlaceholder')"
            data-testid="listing-seo-description"
          />
        </label>

        <label class="listing-seo-modal__field">
          <span class="listing-seo-modal__label">{{ t('account.listings.seo.metaKeywords') }}</span>
          <input
            v-model="metaKeywords"
            type="text"
            class="listing-seo-modal__input"
            maxlength="512"
            :placeholder="t('account.listings.seo.metaKeywordsPlaceholder')"
            data-testid="listing-seo-keywords"
          />
        </label>

        <p v-if="error" class="listing-seo-modal__error" role="alert">
          {{ errorMessage || t('account.listings.seo.saveError') }}
        </p>

        <div class="listing-seo-modal__actions">
          <button
            type="button"
            class="listing-seo-modal__btn listing-seo-modal__btn--ghost"
            :disabled="saving"
            @click="emit('close')"
          >
            {{ t('account.listings.seo.cancel') }}
          </button>
          <button
            type="submit"
            class="listing-seo-modal__btn listing-seo-modal__btn--primary"
            :disabled="saving"
            data-testid="listing-seo-save"
          >
            {{ saving ? t('listing.loading') : t('account.listings.seo.save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.listing-seo-modal {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  overflow-y: auto;
  background: rgba(0, 0, 0, 0.5);
}

.listing-seo-modal__dialog {
  width: min(100%, 520px);
  padding: 20px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
}

.listing-seo-modal__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.listing-seo-modal__title {
  margin: 0 0 4px;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.3;
}

.listing-seo-modal__subtitle {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-muted, #6b7280);
  line-height: 1.4;
}

.listing-seo-modal__close {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  background: #f3f4f6;
  color: #111;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}

.listing-seo-modal__close:disabled {
  opacity: 0.6;
  cursor: default;
}

.listing-seo-modal__hint {
  margin: 0 0 16px;
  font-size: 13px;
  line-height: 1.45;
  color: var(--color-text-muted, #6b7280);
}

.listing-seo-modal__form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.listing-seo-modal__field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.listing-seo-modal__label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.78);
}

.listing-seo-modal__counter {
  font-weight: 500;
  color: var(--color-text-muted, #6b7280);
}

.listing-seo-modal__input,
.listing-seo-modal__textarea {
  box-sizing: border-box;
  width: 100%;
  border: 1px solid var(--figma-border);
  border-radius: 8px;
  background: #fff;
  color: var(--color-text, #000);
  font-family: inherit;
  font-size: 14px;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.listing-seo-modal__input {
  height: 44px;
  padding: 0 12px;
}

.listing-seo-modal__textarea {
  min-height: 96px;
  padding: 10px 12px;
  resize: vertical;
  line-height: 1.4;
}

.listing-seo-modal__input:focus,
.listing-seo-modal__textarea:focus {
  outline: none;
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 16%, transparent);
}

.listing-seo-modal__error {
  margin: 0;
  font-size: 13px;
  color: var(--figma-accent);
}

.listing-seo-modal__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 4px;
}

.listing-seo-modal__btn {
  min-height: 44px;
  padding: 0 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.listing-seo-modal__btn--ghost {
  border: 1px solid var(--figma-border);
  background: #fff;
  color: var(--color-text, #000);
}

.listing-seo-modal__btn--primary {
  border: none;
  background: var(--figma-accent);
  color: #fff;
}

.listing-seo-modal__btn:disabled {
  opacity: 0.7;
  cursor: default;
}
</style>
