<script lang="ts">
export const REQUEST_MESSAGE_MIN_LENGTH = 10
</script>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { createListingRequest } from '@/api/listingRequests'
import { LISTING_NESTED_MODAL_Z_INDEX } from '@/lib/listingModalZIndex'
import {
  formatPhoneMask,
  isPhoneComplete,
  phoneE164,
  phonePlaceholder,
  type PhoneCountry,
} from '@/lib/phoneMask'

const props = withDefaults(
  defineProps<{
    open?: boolean
    listingId: number
  }>(),
  {
    open: true,
  },
)

const emit = defineEmits<{
  close: []
}>()

const { t } = useI18n()
const name = ref('')
const phoneCountry = ref<PhoneCountry>('by')
const phone = ref('')
const message = ref('')
const sent = ref(false)
const submitting = ref(false)
const error = ref(false)
const validationError = ref(false)

const trimmedMessage = computed(() => message.value.trim())
const messageLength = computed(() => trimmedMessage.value.length)
const phoneValid = computed(() => isPhoneComplete(phoneCountry.value, phone.value))
const messageValid = computed(() => messageLength.value >= REQUEST_MESSAGE_MIN_LENGTH)
const canSubmit = computed(() => phoneValid.value && messageValid.value && !submitting.value)
const phoneFieldPlaceholder = computed(() =>
  phoneCountry.value === 'by'
    ? t('listingDetail.leaveRequestPhonePlaceholder')
    : t('listingDetail.leaveRequestPhonePlaceholderRu'),
)

function resetForm() {
  name.value = ''
  phoneCountry.value = 'by'
  phone.value = ''
  message.value = ''
  sent.value = false
  error.value = false
  validationError.value = false
  submitting.value = false
}

watch(
  () => props.open,
  (open) => {
    if (!open) {
      return
    }
    resetForm()
  },
)

function setPhoneCountry(country: PhoneCountry) {
  if (phoneCountry.value === country) {
    return
  }
  phoneCountry.value = country
  phone.value = ''
  validationError.value = false
}

function onPhoneInput(event: Event) {
  const target = event.target as HTMLInputElement
  phone.value = formatPhoneMask(phoneCountry.value, target.value)
  validationError.value = false
}

async function submit() {
  if (submitting.value) {
    return
  }
  if (!phoneValid.value || !messageValid.value) {
    validationError.value = true
    return
  }

  submitting.value = true
  error.value = false
  validationError.value = false
  try {
    await createListingRequest(props.listingId, {
      phone: phoneE164(phoneCountry.value, phone.value),
      message: trimmedMessage.value,
      name: name.value.trim() || null,
    })
    sent.value = true
  } catch {
    error.value = true
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="listing-request-modal">
      <div
        v-if="open"
        class="listing-request-modal"
        role="dialog"
        aria-modal="true"
        :style="{ zIndex: LISTING_NESTED_MODAL_Z_INDEX }"
        @click.self="emit('close')"
      >
        <div class="listing-request-modal__card">
          <div class="listing-request-modal__head">
            <h2>{{ t('listingDetail.leaveRequestTitle') }}</h2>
            <button
              type="button"
              class="listing-request-modal__close"
              :aria-label="t('listingDetail.close')"
              @click="emit('close')"
            >
              ×
            </button>
          </div>

          <Transition name="listing-request-content" mode="out-in">
            <div v-if="!sent" key="form" class="listing-request-modal__body">
              <label class="listing-request-modal__label" for="listing-request-name">
                {{ t('listingDetail.leaveRequestName') }}
              </label>
              <input
                id="listing-request-name"
                v-model="name"
                class="listing-request-modal__input"
                type="text"
                maxlength="120"
                :placeholder="t('listingDetail.leaveRequestNamePlaceholder')"
              />

              <label class="listing-request-modal__label" for="listing-request-phone">
                {{ t('listingDetail.leaveRequestPhone') }}
              </label>
              <div class="listing-request-modal__phone-row">
                <div class="listing-request-modal__countries" role="group">
                  <button
                    type="button"
                    class="listing-request-modal__country"
                    :class="{ 'listing-request-modal__country--active': phoneCountry === 'by' }"
                    @click="setPhoneCountry('by')"
                  >
                    {{ t('listingDetail.leaveRequestPhoneBy') }}
                  </button>
                  <button
                    type="button"
                    class="listing-request-modal__country"
                    :class="{ 'listing-request-modal__country--active': phoneCountry === 'ru' }"
                    @click="setPhoneCountry('ru')"
                  >
                    {{ t('listingDetail.leaveRequestPhoneRu') }}
                  </button>
                </div>
                <input
                  id="listing-request-phone"
                  class="listing-request-modal__input listing-request-modal__input--phone"
                  type="tel"
                  inputmode="tel"
                  autocomplete="tel"
                  maxlength="22"
                  :value="phone"
                  :placeholder="phoneFieldPlaceholder || phonePlaceholder(phoneCountry)"
                  @input="onPhoneInput"
                />
              </div>

              <label class="listing-request-modal__label" for="listing-request-message">
                {{ t('listingDetail.leaveRequestMessage') }}
              </label>
              <textarea
                id="listing-request-message"
                v-model="message"
                class="listing-request-modal__comment"
                rows="4"
                :minlength="REQUEST_MESSAGE_MIN_LENGTH"
                :placeholder="t('listingDetail.leaveRequestMessagePlaceholder')"
                @input="validationError = false"
              />
              <p
                class="listing-request-modal__counter"
                :class="{ 'listing-request-modal__counter--invalid': !messageValid }"
              >
                {{ t('listingDetail.leaveRequestMessageCounter', { n: messageLength, min: REQUEST_MESSAGE_MIN_LENGTH }) }}
              </p>
              <p v-if="validationError" class="listing-request-modal__error">
                {{ t('listingDetail.leaveRequestValidation') }}
              </p>
              <p v-else-if="error" class="listing-request-modal__error">{{ t('listingDetail.leaveRequestError') }}</p>
              <button
                type="button"
                class="listing-request-modal__submit"
                :disabled="!canSubmit"
                @click="submit"
              >
                {{ submitting ? t('listingDetail.leaveRequestSending') : t('listingDetail.leaveRequestSubmit') }}
              </button>
            </div>
            <p v-else key="done" class="listing-request-modal__done">
              {{ t('listingDetail.leaveRequestThanks') }}
            </p>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.listing-request-modal {
  position: fixed;
  inset: 0;
  z-index: 2400;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.45);
}

.listing-request-modal__card {
  width: min(420px, 100%);
  padding: 20px;
  border-radius: 16px;
  background: var(--figma-surface);
}

.listing-request-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.listing-request-modal__head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.listing-request-modal__close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: rgba(146, 146, 146, 0.12);
  font-size: 20px;
  cursor: pointer;
}

.listing-request-modal__label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 600;
}

.listing-request-modal__phone-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.listing-request-modal__countries {
  display: inline-flex;
  gap: 6px;
}

.listing-request-modal__country {
  min-width: 44px;
  min-height: 36px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--figma-surface);
  color: var(--figma-text);
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.listing-request-modal__country--active {
  border-color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-surface));
  color: var(--figma-accent);
}

.listing-request-modal__input,
.listing-request-modal__comment {
  width: 100%;
  margin-bottom: 12px;
  padding: 10px 12px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  font: inherit;
}

.listing-request-modal__input--phone {
  margin-bottom: 0;
}

.listing-request-modal__comment {
  resize: vertical;
  margin-bottom: 6px;
}

.listing-request-modal__counter {
  margin: 0 0 12px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-request-modal__counter--invalid {
  color: #c62828;
}

.listing-request-modal__submit {
  width: 100%;
  height: 44px;
  border: none;
  border-radius: 12px;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.listing-request-modal__submit:disabled {
  opacity: 0.7;
  cursor: default;
}

.listing-request-modal__error {
  margin: 0 0 10px;
  font-size: 13px;
  color: #c62828;
}

.listing-request-modal__done {
  margin: 8px 0 4px;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.45;
}
</style>

<style>
.listing-request-modal-enter-active,
.listing-request-modal-leave-active {
  transition: opacity 0.28s ease;
}

.listing-request-modal-enter-active .listing-request-modal__card,
.listing-request-modal-leave-active .listing-request-modal__card {
  transition:
    opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-request-modal-enter-from,
.listing-request-modal-leave-to {
  opacity: 0;
}

.listing-request-modal-enter-from .listing-request-modal__card,
.listing-request-modal-leave-to .listing-request-modal__card {
  opacity: 0;
  transform: translateY(18px) scale(0.97);
}

.listing-request-content-enter-active,
.listing-request-content-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-request-content-enter-from,
.listing-request-content-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

@media (prefers-reduced-motion: reduce) {
  .listing-request-modal-enter-active,
  .listing-request-modal-leave-active,
  .listing-request-modal-enter-active .listing-request-modal__card,
  .listing-request-modal-leave-active .listing-request-modal__card,
  .listing-request-content-enter-active,
  .listing-request-content-leave-active {
    transition-duration: 0.01ms;
  }

  .listing-request-modal-enter-from .listing-request-modal__card,
  .listing-request-modal-leave-to .listing-request-modal__card,
  .listing-request-content-enter-from,
  .listing-request-content-leave-to {
    transform: none;
  }
}
</style>
